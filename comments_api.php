<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    // comments_api.php - Handles comment CRUD for public and admin
    require_once 'db.php';
    header('Content-Type: application/json');

    // Add comment (public)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['comment'])) {
        $username = $conn->real_escape_string($_POST['username']);
        $comment = $conn->real_escape_string($_POST['comment']);
        $sql = "INSERT INTO comments (username, comment) VALUES ('$username', '$comment')";
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Comment added!']);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to add comment: ' . $conn->error
            ]);
        }
        exit;
    }

    // List comments (HTML for AJAX or JSON for Vue)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $result = $conn->query("SELECT * FROM comments ORDER BY created_at DESC");
        if (isset($_GET['json'])) {
            $comments = [];
            while ($row = $result->fetch_assoc()) {
                $row['comment'] = nl2br(htmlspecialchars($row['comment']));
                $row['username'] = htmlspecialchars($row['username']);
                $comments[] = $row;
            }
            echo json_encode($comments);
        } else {
            $comments = '';
            while ($row = $result->fetch_assoc()) {
                $comments .= '<div class="card mb-2"><div class="card-body">';
                $comments .= '<h6 class="card-title">' . htmlspecialchars($row['username']) . ' <small class="text-muted">' . $row['created_at'] . '</small></h6>';
                $comments .= '<p class="card-text">' . nl2br(htmlspecialchars($row['comment'])) . '</p>';
                $comments .= '</div></div>';
            }
            header('Content-Type: text/html');
            echo $comments;
        }
        exit;
    }
?>
