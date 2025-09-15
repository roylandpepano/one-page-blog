<?php
    // index.php - One Page Blog
    // Start session if not already started so we can show login state
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once 'db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>One Page Blog</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">
    <style>
        :root{
            --bg: #f8f9fa;
            --card-bg: #ffffff;
            --text: #212529;
            --muted: #6c757d;
            --accent: #198754; /* bootstrap success */
            --blockquote-bg: #f8f9fa;
            --input-bg: #fff;
            --border: rgba(0,0,0,0.08);
        }
        .dark-mode{
            --bg: #0f1720;
            --card-bg: #0b1220;
            --text: #e6eef8;
            --muted: #9aa6b2;
            --accent: #22c55e;
            --blockquote-bg: #071122;
            --input-bg: #071122;
            --border: rgba(255,255,255,0.06);
        }
        body{ background: var(--bg) !important; color: var(--text); }
        .card, .card-body, .card-img-overlay, .card-footer{ background: var(--card-bg) !important; color: var(--text) !important; }
        .bg-white{ background: var(--card-bg) !important; }
        .bg-light{ background: var(--bg) !important; }
        .text-muted{ color: var(--muted) !important; }
        blockquote.blockquote{ background: var(--blockquote-bg) !important; }
        .form-control{ background: var(--input-bg) !important; color: var(--text) !important; border-color: var(--border) !important; }
        .form-control::placeholder{ color: var(--muted); }
        .rounded-circle.bg-primary{ background: var(--accent) !important; }
        .comment-box{ background: var(--card-bg); border-color: var(--border); }
        /* comment avatar fixes */
        .comment-avatar{ width:48px; height:48px; min-width:48px; min-height:48px; font-weight:700; border-radius:50%; overflow:hidden; flex:0 0 48px; }
        .comment-item .flex-fill{ min-width:0; }
        /* Ensure comment text wraps and doesn't overflow */
        .comment-box{ word-wrap:break-word; word-break:break-word; }
            .theme-toggle{ position: fixed; right: 18px; bottom: 18px; z-index: 1050; }
            .theme-toggle button{ box-shadow: 0 4px 18px rgba(0,0,0,0.08); }
    </style>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js'></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
</head>
<body class="">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="theme-toggle">
                    <button id="themeToggle" class="btn btn-outline-secondary" title="Toggle dark mode">🌙</button>
                </div>
                <article class="card shadow-lg border-0 mb-5">
                    <img src="https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=900&q=80" class="card-img-top" alt="Healthy Lifestyle" style="max-height:340px;object-fit:cover;">
                    <div class="card-body p-5">
                        <h1 class="card-title display-5 mb-3 fw-bold text-success">Embracing a Healthy Lifestyle: Your Path to Wellness</h1>
                        <p class="card-text lead mb-4">A healthy lifestyle is more than just a trend—it's a commitment to living your best life. Discover the essential habits and mindsets that can help you thrive physically, mentally, and emotionally.</p>
                        <h3 class="fw-semibold mt-4">1. Nourish Your Body</h3>
                        <p>Choose whole, nutrient-rich foods like fruits, vegetables, lean proteins, and whole grains. Stay hydrated and practice mindful eating to fuel your body for energy and longevity.</p>
                        <h3 class="fw-semibold mt-4">2. Move Every Day</h3>
                        <p>Regular physical activity boosts your mood, strengthens your heart, and supports a healthy weight. Whether it’s walking, yoga, cycling, or dancing, find movement you enjoy and make it part of your routine.</p>
                        <h3 class="fw-semibold mt-4">3. Prioritize Rest and Sleep</h3>
                        <p>Quality sleep is vital for recovery and mental clarity. Create a calming bedtime routine, limit screen time before bed, and aim for 7-9 hours of restful sleep each night.</p>
                        <h3 class="fw-semibold mt-4">4. Cultivate Mindfulness</h3>
                        <p>Manage stress through mindfulness practices like meditation, deep breathing, or journaling. Taking time for yourself helps maintain emotional balance and resilience.</p>
                        <blockquote class="blockquote my-4 p-4 border-start border-4" style="border-color:var(--accent)!important;">
                            <p class="mb-2">“Take care of your body. It’s the only place you have to live.”</p>
                            <footer class="blockquote-footer">Jim Rohn</footer>
                        </blockquote>
                        <p class="mb-0">Remember, a healthy lifestyle is a journey, not a destination. Start with small, sustainable changes and celebrate your progress along the way!</p>
                    </div>
                </article>
                <div class="mb-5">
                        <div class="card p-4 bg-white border rounded-3" style="border-color:var(--border)!important;">
                        <h5 class="mb-3">Leave a Comment</h5>
                        <form id="commentForm">
                            <div class="row g-2">
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="username" name="username" placeholder="Your name" required>
                                            <label for="username">Your name</label>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-floating">
                                            <textarea class="form-control" id="comment" name="comment" placeholder="Your comment" rows="2" required></textarea>
                                            <label for="comment">Your comment</label>
                                        </div>
                                    </div>
                            </div>
                            <div class="d-flex justify-content-end mt-3">
                                <button type="submit" class="btn btn-primary px-4">Post Comment</button>
                            </div>
                        </form>
                        <div id="commentMsg" class="mt-2"></div>
                    </div>
                </div>
                <div>
                    <h4 class="mb-3">Comments</h4>
                    <div id="vue-comments">
                        <comment-list :comments="comments"></comment-list>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center mt-4 mb-4" role="contentinfo">
        <?php if (!empty($_SESSION['admin'])): ?>
            <span class="me-2 text-muted">Currently logged in as <a href="admin.php"><?php echo htmlspecialchars($_SESSION['admin']); ?></a></span>
            <a href="admin.php?logout=1" class="btn btn-outline-secondary btn-sm" aria-label="Logout">Logout</a>
        <?php else: ?>
            <a href="admin.php" class="btn btn-outline-secondary btn-sm" aria-label="Admin login">Login</a>
        <?php endif; ?>
    </footer>

    <script>
    // Vue component for comments
    const CommentList = {
        props: ['comments'],
        methods: {
            initials(name){
                if(!name) return '?';
                return name.split(' ').map(n=>n.charAt(0).toUpperCase()).slice(0,2).join('');
            },
            fmtDate(d){
                try{ return new Date(d).toLocaleString(); }catch(e){ return d; }
            }
        },
        template: `
            <div>
                <div v-for="c in comments" :key="c.id" class="fade d-flex gap-3 mb-3 align-items-start comment-item">
                    <div class="comment-avatar text-white d-flex align-items-center justify-content-center" :style="{background:'var(--accent)'}">{{ initials(c.username) }}</div>
                    <div class="flex-fill">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>{{ c.username }}</strong>
                                <div class="text-muted small">{{ fmtDate(c.created_at) }}</div>
                            </div>
                        </div>
                        <div class="mt-2 comment-box border rounded-3 p-3 shadow-sm">
                            <div v-html="c.comment"></div>
                        </div>
                    </div>
                </div>
            </div>
        `
    };

    const app = Vue.createApp({
        components: { CommentList },
        data() {
            return { comments: [] };
        },
        methods: {
            fetchComments(options = {}) {
                // Get JSON for Vue and animate comments on render
                $.get('comments_api.php?json=1', (data) => {
                    if (Array.isArray(data)) {
                        this.comments = data;
                        this.$nextTick(() => {
                            const container = document.getElementById('vue-comments');
                            if (!container) return;
                            const fades = container.querySelectorAll('.fade');
                            fades.forEach(f => f.classList.remove('show'));
                            // force reflow
                            void container.offsetWidth;
                            fades.forEach(f => f.classList.add('show'));
                            if (options.scrollToLast && fades.length) {
                                // comments are returned newest-first, so scroll to the first item
                                const newest = fades[0];
                                if (newest) newest.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        });
                    }
                }, 'json');
            }
        },
        mounted() {
            this.fetchComments();
        }
    });

    app.mount('#vue-comments');

    $('#commentForm').on('submit', function(e) {
        e.preventDefault();
        $.post('comments_api.php', $(this).serialize(), function(res) {
                if(res.success) {
                iziToast.success({
                    title: 'Success',
                    message: res.message,
                    position: 'topRight'
                });
                $('#commentForm')[0].reset();
                app._instance.proxy.fetchComments({ scrollToLast: true });
            } else {
                iziToast.error({
                    title: 'Error',
                    message: res.message,
                    position: 'topRight'
                });
            }
        }, 'json');
    });
    </script>

    <script>
    // Theme toggle: persist in localStorage and apply dark-mode class
    (function(){
        const body = document.body;
        const btn = document.getElementById('themeToggle');
        const KEY = 'onepageblog:theme';
        function applyTheme(mode){
            if(mode === 'dark') body.classList.add('dark-mode'); else body.classList.remove('dark-mode');
            // update icon
            if(btn) btn.textContent = mode === 'dark' ? '☀️' : '🌙';
            if(btn) btn.setAttribute('aria-pressed', mode === 'dark');
            // update button style to better match theme
            if(btn) {
                if(mode === 'dark') {
                    btn.classList.remove('btn-outline-secondary');
                    btn.classList.add('btn-light');
                } else {
                    btn.classList.remove('btn-light');
                    btn.classList.add('btn-outline-secondary');
                }
            }
        }

        // init
        let saved = null;
        try{ saved = localStorage.getItem(KEY); }catch(e){ }
        if(!saved){
            // prefer user OS setting
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            saved = prefersDark ? 'dark' : 'light';
        }
        applyTheme(saved);

        if(btn){
            btn.addEventListener('click', function(){
                const isDark = body.classList.contains('dark-mode');
                const next = isDark ? 'light' : 'dark';
                applyTheme(next);
                try{ localStorage.setItem(KEY, next); }catch(e){}
            });
        }
    })();
    </script>
</body>
</html>