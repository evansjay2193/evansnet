<?php
// Database connection
$dsn = 'mysql:host=localhost;dbname=evanscode';
$username = 'root';
$password = '';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
$pdo = new PDO($dsn, $username, $password, $options);

// Pagination parameters
$limit = 10; // Number of posts per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Get current page from query string, default to 1
$offset = ($page - 1) * $limit; // Calculate the offset

// Handle like functionality
if (isset($_GET['like'])) {
    $postId = intval($_GET['like']);
    session_start();
    $userId = session_id(); // Use session ID as a unique user identifier
    
    // Check if user already liked the post
    $stmt = $pdo->prepare("SELECT * FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$postId, $userId]);
    $like = $stmt->fetch();
    
    if ($like) {
        // Unlike
        $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$postId, $userId]);
        echo 'unliked';
    } else {
        // Like
        $stmt = $pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        $stmt->execute([$postId, $userId]);
        echo 'liked';
    }
    exit;
}

// Handle comment functionality
if (isset($_POST['comment'])) {
    $postId = intval($_POST['post_id']);
    $commentText = trim($_POST['comment_text']);
    session_start();
    $userId = session_id(); // Use session ID as a unique user identifier

    if ($commentText) {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, comment_text) VALUES (?, ?, ?)");
        $stmt->execute([$postId, $userId, $commentText]);
        echo 'success';
    } else {
        echo 'error';
    }
    exit;
}

// Fetch posts and comments from database with pagination
$stmt = $pdo->prepare("
    SELECT p.*, COUNT(l.id) AS likes_count
    FROM posts p
    LEFT JOIN likes l ON p.id = l.post_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

// Fetch all comments for posts
$commentsStmt = $pdo->query("SELECT * FROM comments ORDER BY created_at ASC");
$comments = $commentsStmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EvansCode - Manage Posts</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="posts.css">
</head>
<body>
    <nav>
        <div class="logo-container">
            <img src="Evanslogo.png" alt="EvansCode Logo">
        </div>
        <h1>EvansCode</h1>
        <div class="menu-toggle" id="menu-toggle">☰</div>
        <div class="nav-links" id="nav-links">
            <a href="index.html">Home</a>
            <a href="posts.php">Posts</a>
            <a href="admin.php">Admin</a>
        </div>
    </nav>

    <!-- Creative Welcome Section -->
    <div class="welcome-section">
        <div class="welcome-content">
            <h2>🌟 Welcome to EvansCode - Your Hub for Exciting Posts & More! 🌟</h2>
            <p>🎉 We're thrilled to have you here. Explore the latest posts, share your thoughts, and be part of our vibrant community. Dive into engaging content and connect with like-minded individuals. Your voice matters, and we're here to make sure it shines!</p>
            <p>❓ Have any questions or need help? Feel free to reach out—we're always here to support you and ensure you have the best experience possible. Let's make this journey exciting and memorable together!</p>
            <a href="index.html#contact" class="cta-button">Contact Us</a>
            <a href="posts.php" class="cta-button">Explore Posts</a>
        </div>
    </div>

    <div class="post-container" id="posts-start">
        <?php foreach ($posts as $post): ?>
            <div class="post">
                <div class="post-header">
                    <h2><?php echo htmlspecialchars($post['content']); ?></h2>
                    <span class="date-posted"><?php echo date('F j, Y', strtotime($post['created_at'])); ?></span>
                </div>
                <?php if ($post['type'] === 'image' && $post['file']): ?>
                    <div class="post-image">
                        <img src="uploads/<?php echo htmlspecialchars($post['file']); ?>" alt="Post Image">
                    </div>
                <?php elseif ($post['type'] === 'video' && $post['file']): ?>
                    <div class="post-video-container">
                        <video id="video-<?php echo $post['id']; ?>" controls class="post-video">
                            <source src="uploads/<?php echo htmlspecialchars($post['file']); ?>" type="video/mp4">
                        </video>
                    </div>
                <?php elseif ($post['type'] === 'ad'): ?>
                    <div class="ad"><?php echo htmlspecialchars($post['content']); ?></div>
                <?php endif; ?>
                <div class="post-info">
                    <span class="likes-count">Likes: <span id="like-count-<?php echo $post['id']; ?>"><?php echo $post['likes_count']; ?></span></span>
                    <span class="comments-count">Comments: <?php echo count(array_filter($comments, fn($c) => $c['post_id'] == $post['id'])); ?></span>
                </div>
                <div class="post-actions">
                    <button class="like-button" data-post-id="<?php echo $post['id']; ?>">
                        <i class="fas fa-thumbs-up"></i>
                    </button>
                    <button class="comment-button" onclick="toggleComments(<?php echo $post['id']; ?>)">
                        <i class="fas fa-comment"></i> Comments
                    </button>
                </div>

                <div class="comments-section" id="comments-section-<?php echo $post['id']; ?>">
                    <div class="comment-form">
                        <textarea placeholder="Add a comment..." id="comment-text-<?php echo $post['id']; ?>"></textarea>
                        <button onclick="postComment(<?php echo $post['id']; ?>)">Post Comment</button>
                    </div>
                    <?php foreach ($comments as $comment): ?>
                        <?php if ($comment['post_id'] == $post['id']): ?>
                            <div class="comment">
                                <img src="path/to/avatar/<?php echo htmlspecialchars($comment['user_avatar']); ?>" alt="User Avatar" class="avatar">
                                <div class="comment-content">
                                    <p><?php echo htmlspecialchars($comment['comment_text']); ?></p>
                                    <span class="comment-date"><?php echo date('F j, Y', strtotime($comment['created_at'])); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="posts.php?page=<?php echo $page - 1; ?>">Previous</a>
        <?php endif; ?>
        <span>Page <?php echo $page; ?></span>
        <a href="posts.php?page=<?php echo $page + 1; ?>">Next</a>
    </div>
    <script>
    // Handle menu toggle for mobile view
    document.getElementById('menu-toggle').addEventListener('click', function() {
        const navLinks = document.getElementById('nav-links');
        navLinks.style.display = navLinks.style.display === 'flex' ? 'none' : 'flex';
    });

    // Handle like button click
    document.querySelectorAll('.like-button').forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            const videoElement = document.getElementById('video-' + postId);
            const wasPlaying = videoElement ? !videoElement.paused : false;

            // Pause video if it's playing
            if (videoElement && wasPlaying) {
                videoElement.pause();
            }

            // Send like request
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'posts.php?like=' + postId, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = xhr.responseText;
                    if (response === 'liked' || response === 'unliked') {
                        const likeCountElement = document.getElementById('like-count-' + postId);
                        let currentCount = parseInt(likeCountElement.textContent, 10);
                        likeCountElement.textContent = response === 'liked' ? currentCount + 1 : currentCount - 1;
                    }
                }
            };
            xhr.send();

            // Resume video if it was playing
            if (videoElement && wasPlaying) {
                videoElement.play();
            }
        });
    });

    // Ensure only one video plays at a time
    document.querySelectorAll('.post-video').forEach(video => {
        video.addEventListener('play', function() {
            // Pause other videos
            document.querySelectorAll('.post-video').forEach(otherVideo => {
                if (otherVideo !== this && !otherVideo.paused) {
                    otherVideo.pause();
                }
            });
        });
    });

    // Toggle comments section visibility
    function toggleComments(postId) {
        const section = document.getElementById('comments-section-' + postId);
        section.style.display = section.style.display === 'none' ? 'block' : 'none';
    }

    // Post comment
    function postComment(postId) {
        const commentText = document.getElementById('comment-text-' + postId).value.trim();
        if (commentText) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'posts.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = xhr.responseText;
                    if (response === 'success') {
                        const commentsSection = document.getElementById('comments-section-' + postId);
                        const newComment = document.createElement('div');
                        newComment.className = 'comment';
                        newComment.innerHTML = `<p>${commentText}</p><span class="comment-date">Just now</span>`;
                        commentsSection.insertBefore(newComment, commentsSection.querySelector('.comment-form'));
                        document.getElementById('comment-text-' + postId).value = '';
                    } else {
                        alert('Error posting comment.');
                    }
                }
            };
            xhr.send(`post_id=${postId}&comment_text=${encodeURIComponent(commentText)}&comment=1`);
        }
    }

    // Scroll to the post container after 2 seconds
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            document.querySelector('.post-container').scrollIntoView({ behavior: 'smooth' });
        }, 2000);
    });
</script>

</body>
</html>
