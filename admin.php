<?php
// admin.php - Admin panel for managing comments
session_start();
require_once 'db.php';

// CSRF helpers
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf_token'];
}
function csrf_check($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// sanitize helper
function sanitize_comment($text){
    $t = trim($text);
    // strip control characters except newlines and basic whitespace
    $t = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $t);
    // limit length
    if (mb_strlen($t) > 2000) $t = mb_substr($t, 0, 2000);
    return $t;
}

// Login handling
if (!isset($_SESSION['admin'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        // prepared statement to fetch user row
        $stmt = $conn->prepare('SELECT id, username, password FROM admin_users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows === 1) {
            $row = $res->fetch_assoc();
            $stored = $row['password'];
            $ok = false;
            // First try password_verify (bcrypt/modern hashes)
            if (password_verify($password, $stored)) {
                $ok = true;
            } else {
                // Fallback: legacy MD5 check and migrate to password_hash
                if (md5($password) === $stored) {
                    $ok = true;
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $ustmt = $conn->prepare('UPDATE admin_users SET password = ? WHERE id = ?');
                    $ustmt->bind_param('si', $newHash, $row['id']);
                    $ustmt->execute();
                }
            }
            if ($ok) {
                $_SESSION['admin'] = $row['username'];
                header('Location: admin.php');
                exit;
            }
        }
        $msg = 'Invalid login.'; // Show iziToast on login failure
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - One Page Blog</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">
        <style>
            :root{
                --bg: #f8f9fa;
                --card-bg: #ffffff;
                --text: #212529;
                --muted: #6c757d;
                --accent: #198754;
                --input-bg: #fff;
                --border: rgba(0,0,0,0.08);
            }
            .dark-mode{ --bg:#0f1720; --card-bg:#0b1220; --text:#e6eef8; --muted:#9aa6b2; --accent:#22c55e; --input-bg:#071122; --border: rgba(255,255,255,0.06);} 
            body{ background:var(--bg); color:var(--text); }
            .card, .card-body{ background:var(--card-bg); color:var(--text); }
            .form-control{ background:var(--input-bg); color:var(--text); border-color:var(--border); }
            .theme-toggle{ position: fixed; right: 18px; bottom: 18px; z-index:1050; }
        </style>
    </head>
    <body class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="theme-toggle">
                        <button id="themeToggle" class="btn btn-outline-secondary" title="Toggle dark mode">🌙</button>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h3 class="mb-3">Admin Login</h3>
                            <?php if(isset($msg)) echo '<div class="alert alert-danger">'.htmlspecialchars($msg).'</div>'; ?>
                            <form method="post">
                                <div class="mb-2">
                                    <input class="form-control" name="username" placeholder="Username" required>
                                </div>
                                <div class="mb-3">
                                    <input class="form-control" name="password" type="password" placeholder="Password" required>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button class="btn btn-primary">Login</button>
                                </div>
                            </form>
                            <script>
                            (function(){
                                <?php if(isset($msg)): ?>
                                    document.addEventListener('DOMContentLoaded', function(){
                                        iziToast.error({ title: 'Login Failed', message: <?= json_encode($msg) ?>, position: 'topRight' });
                                    });
                                <?php endif; ?>
                            })();
                            </script>
                        </div>
                    </div>
                    <p class="text-center text-muted mt-3 small">Use admin credentials to manage comments.</p>
                </div>
            </div>
        </div>
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
        <script>
        (function(){
            const body = document.body;
            const btn = document.getElementById('themeToggle');
            const KEY = 'onepageblog:theme';
            function applyTheme(mode){ if(mode === 'dark') body.classList.add('dark-mode'); else body.classList.remove('dark-mode'); if(btn) btn.textContent = mode === 'dark' ? '☀️' : '🌙'; }
            let saved = null; try{ saved = localStorage.getItem(KEY);}catch(e){}
            if(!saved){ const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches; saved = prefersDark ? 'dark' : 'light'; }
            applyTheme(saved);
            if(btn){ btn.addEventListener('click', function(){ const isDark = body.classList.contains('dark-mode'); const next = isDark ? 'light' : 'dark'; applyTheme(next); try{ localStorage.setItem(KEY, next);}catch(e){} }); }
        })();
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Handle edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Require authentication for actions
    if (!isset($_SESSION['admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Not authorized']);
        exit;
    }

    // Validate CSRF
    $csrf = $_POST['csrf_token'] ?? '';
    if (!csrf_check($csrf)) {
        if (!empty($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
            exit;
        }
        die('Invalid CSRF token');
    }

    // Handle delete first to avoid ambiguity when both edit_id and delete_id are present
    if (isset($_POST['delete_id'])) {
        $id = (int)$_POST['delete_id'];
        $ok = $conn->query("DELETE FROM comments WHERE id=$id");
        if (!empty($_POST['ajax'])) {
            header('Content-Type: application/json');
            if ($ok) echo json_encode(['success' => true, 'message' => 'Comment deleted']); else echo json_encode(['success' => false, 'message' => 'Delete failed']);
            exit;
        }
        header('Location: admin.php?notice=' . ($ok ? 'deleted' : 'error'));
        exit;
    }

    // Handle edit
    if (isset($_POST['edit_id'])) {
        $id = (int)$_POST['edit_id'];
        $raw = $_POST['comment'] ?? '';
        $clean = sanitize_comment($raw);
        $comment = $conn->real_escape_string($clean);
        $ok = $conn->query("UPDATE comments SET comment='$comment' WHERE id=$id");
        if (!empty($_POST['ajax'])) {
            header('Content-Type: application/json');
            if ($ok) echo json_encode(['success' => true, 'message' => 'Comment updated']); else echo json_encode(['success' => false, 'message' => 'Update failed']);
            exit;
        }
        header('Location: admin.php?notice=' . ($ok ? 'edited' : 'error'));
        exit;
    }

    // Default fallback
    if (!empty($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        exit;
    }
    header('Location: admin.php');
    exit;
}
    // List comments
    $res = $conn->query("SELECT * FROM comments ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - One Page Blog</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">
    <style>
        :root{
            --bg: #f8f9fa;
            --card-bg: #ffffff;
            --text: #212529;
            --muted: #6c757d;
            --accent: #198754;
            --input-bg: #fff;
            --border: rgba(0,0,0,0.08);
        }
        .dark-mode{ --bg:#0f1720; --card-bg:#0b1220; --text:#e6eef8; --muted:#9aa6b2; --accent:#22c55e; --input-bg:#071122; --border: rgba(255,255,255,0.06);} 
        body{ background:var(--bg); color:var(--text); }
        .card, .card-body{ background:var(--card-bg); color:var(--text); }
        .form-control{ background:var(--input-bg); color:var(--text); border-color:var(--border); }
        .theme-toggle{ position: fixed; right: 18px; bottom: 18px; z-index:1050; }
        .comment-box{ word-wrap:break-word; word-break:break-word; }
    </style>
</head>
<body class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="theme-toggle">
                    <button id="themeToggle" class="btn btn-outline-secondary" title="Toggle dark mode">🌙</button>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="m-0">Admin Panel</h2>
                    <div>
                        <a href="index.php" class="btn btn-outline-secondary btn-sm me-2">View Site</a>
                        <a href="?logout=1" class="btn btn-sm btn-secondary">Logout</a>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Manage Comments</h5>
                        <?php if ($res && $res->num_rows === 0) : ?>
                            <p class="text-muted">No comments yet.</p>
                        <?php endif; ?>
                        <div id="adminComments">
                            <?php while($row = $res->fetch_assoc()): ?>
                            <form method="post" class="card mb-3 admin-comment" data-id="<?= $row['id'] ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong><?= htmlspecialchars($row['username']) ?></strong>
                                            <div class="text-muted small"><?= $row['created_at'] ?></div>
                                        </div>
                                        <div class="text-end"></div>
                                    </div>
                                    <textarea name="comment" class="form-control mb-3 comment-box" rows="3"><?= htmlspecialchars($row['comment']) ?></textarea>
                                    <input type="hidden" name="edit_id" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                    <input type="hidden" name="ajax" value="1">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button class="btn btn-success btn-sm btn-save" type="button">Save</button>
                                        <button class="btn btn-danger btn-sm btn-delete" type="button" data-id="<?= $row['id'] ?>">Delete</button>
                                    </div>
                                </div>
                            </form>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
    <script src="assets/admin.js"></script>
</body>
</html>
