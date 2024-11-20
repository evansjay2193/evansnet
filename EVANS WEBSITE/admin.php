<?php
session_start();
require 'config.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || (time() - $_SESSION['loggedin_time'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// Update session time
$_SESSION['loggedin_time'] = time();

// Handle form submission for posts
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle post creation
    if (isset($_POST['create_post'])) {
        $type = $_POST['type'] ?? 'text';
        $content = $_POST['content'] ?? '';
        $file = $_FILES['file']['name'] ?? '';

        // Save file if uploaded
        if ($file) {
            $target = 'uploads/' . basename($file);
            if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
                // File upload success
            } else {
                echo 'File upload failed';
            }
        }

        // Insert into database
        $stmt = $pdo->prepare("INSERT INTO posts (type, content, file, is_hidden) VALUES (?, ?, ?, ?)");
        $stmt->execute([$type, $content, $file, 0]); // Default to not hidden
    }

    // Handle post deletion
    if (isset($_POST['delete_post_id'])) {
        $postId = $_POST['delete_post_id'];

        // Delete the post and its associated data (likes and comments)
        $pdo->beginTransaction();
        try {
            // Delete likes
            $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ?");
            $stmt->execute([$postId]);

            // Delete comments
            $stmt = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
            $stmt->execute([$postId]);

            // Delete the post
            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$postId]);

            $pdo->commit();
            echo 'Post deleted successfully';
        } catch (Exception $e) {
            $pdo->rollBack();
            echo 'Failed to delete post: ' . $e->getMessage();
        }
    }
    // Handle post editing
    if (isset($_POST['edit_post_id'])) {
        $postId = $_POST['edit_post_id'];
        $type = $_POST['type'] ?? 'text';
        $content = $_POST['content'] ?? '';
        $file = $_FILES['file']['name'] ?? '';

        // Handle file upload
        if ($file) {
            $target = 'uploads/' . basename($file);
            if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
                // File upload success
            } else {
                echo 'File upload failed';
            }
        }

        // Update post in database
        $stmt = $pdo->prepare("UPDATE posts SET type = ?, content = ?, file = ? WHERE id = ?");
        $stmt->execute([$type, $content, $file, $postId]);
    }
}

// Fetch all posts for display
$posts = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EvansCode - Admin</title>
    <link rel="stylesheet" href="styles.css">
    <style>
      /* Global Styles */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f9f9f9;
    margin: 0;
    padding: 0;
    color: #333;
}

/* Navigation Bar */
nav {
    background: #0044cc;
    color: #fff;
    padding: 15px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
    transition: background 0.3s ease;
}

nav:hover {
    background: #003399; /* Slightly darker on hover */
}

.logo-container img {
    height: 40px;
}

.nav-links {
    display: flex;
    gap: 20px;
}

.nav-links a {
    color: #fff;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s ease;
}

.nav-links a:hover {
    color: #ffdd57; /* Gold color on hover */
}

/* Mobile Menu Toggle */
.menu-toggle {
    display: none;
    font-size: 24px;
    cursor: pointer;
    background: #333;
    color: #fff;
    padding: 10px;
    border-radius: 5px;
}

/* Responsive Navigation */
@media (max-width: 768px) {
    .nav-links {
        display: none;
        flex-direction: column;
        width: 100%;
        background: #0044cc;
        position: absolute;
        top: 60px; /* Adjust based on the height of your nav */
        left: 0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .nav-links.active {
        display: flex;
    }

    .menu-toggle {
        display: block;
    }
}

/* Post Form Styling */
.post-form, .posts-list {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    margin: 20px auto;
    max-width: 800px;
    position: relative;
}

/* Post Form Heading */
.post-form h2, .posts-list h2 {
    margin-top: 0;
    color: #0044cc;
    font-size: 24px;
}

/* Post Item Styling */
.post-item {
    background: #fff;
    margin-bottom: 20px;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    position: relative;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.post-item:hover {
    transform: scale(1.02);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}

.post-item h3 {
    margin: 0 0 10px;
    color: #333;
    font-size: 20px;
}

.post-item p {
    margin: 0 0 10px;
    color: #555;
}

.post-item button {
    background: #0275d8;
    color: #fff;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.post-item button:hover {
    background: #025aa5;
    transform: scale(1.05);
}

/* File Display Styling */
.post-item img, .post-item video {
    max-width: 100%;
    height: auto;
    border-radius: 5px;
    margin: 10px 0;
}

/* Edit Button */
.edit-button {
    background: #0275d8;
    color: #fff;
    border: none;
    padding: 8px 12px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    position: absolute;
    top: 15px;
    right: 60px;
}

.edit-button:hover {
    background: #025aa5;
}

/* Edit Modal Styling */
#edit-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    animation: fadeIn 0.3s ease;
}

#edit-modal > div {
    background: #fff;
    padding: 30px;
    border-radius: 10px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    position: relative;
}

#edit-modal h2 {
    margin-top: 0;
    color: #0044cc;
    font-size: 22px;
}

#edit-form {
    display: flex;
    flex-direction: column;
}

#edit-form label {
    margin-bottom: 5px;
    font-weight: 600;
}

#edit-form textarea {
    margin-bottom: 15px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    resize: vertical;
}

#edit-form input[type="file"] {
    margin-bottom: 15px;
}

#edit-form button {
    background: #0275d8;
    color: #fff;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

#edit-form button:hover {
    background: #025aa5;
}

#edit-form .cancel-button {
    background: #d9534f;
    margin-left: 10px;
}

#edit-form .cancel-button:hover {
    background: #c9302c;
}

/* Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* Responsive Adjustments */
@media (max-width: 576px) {
    .post-form, .posts-list {
        padding: 15px;
    }

    .post-item {
        padding: 15px;
    }

    #edit-modal > div {
        padding: 20px;
    }
}


    </style>
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
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="manage_users.php">Manage Users</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="post-form">
        <h2>Create New Post</h2>
        <form action="admin.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="create_post">
            <label for="type">Type:</label>
            <select name="type" id="type" required>
                <option value="text">Article</option>
                <option value="image">Image</option>
                <option value="video">Video</option>
                <option value="ad">Advertisement</option>
            </select>
            <label for="content">Content:</label>
            <textarea name="content" id="content" rows="4" required></textarea>
            <label for="file">Upload File (optional):</label>
            <input type="file" name="file" id="file">
            <button type="submit">Post</button>
        </form>
    </div>

    <div class="posts-list">
        <h2>Manage Posts</h2>
        <?php foreach ($posts as $post): ?>
            <div class="post-item" <?php if ($post['is_hidden']): ?> style="display: none;" <?php endif; ?>>
                <h3><?php echo htmlspecialchars($post['type']); ?></h3>
                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                <?php if ($post['file']): ?>
                    <?php if ($post['type'] === 'image'): ?>
                        <img src="uploads/<?php echo htmlspecialchars($post['file']); ?>" alt="Post Image">
                    <?php elseif ($post['type'] === 'video'): ?>
                        <video controls>
                            <source src="uploads/<?php echo htmlspecialchars($post['file']); ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    <?php else: ?>
                        <p>File: <?php echo htmlspecialchars($post['file']); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
                <!-- Inside your post-item div -->
                <form action="admin.php" method="post" style="display:inline;">
                   <input type="hidden" name="delete_post_id" value="<?php echo $post['id']; ?>">
                    <button type="submit">Delete</button>
                 </form>
        <button class="edit-button" onclick="openEditModal(<?php echo $post['id']; ?>, '<?php echo addslashes($post['content']); ?>', '<?php echo addslashes($post['file']); ?>', '<?php echo $post['type']; ?>')">Edit</button>
      </div>
        <?php endforeach; ?>
    </div>

    <!-- Edit Modal -->
    <div id="edit-modal" style="display: none;">
        <div>
            <h2>Edit Post</h2>
            <form id="edit-form" action="admin.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="edit_post_id" id="edit-post-id">
                <label for="edit-type">Type:</label>
                <select name="type" id="edit-type" required>
                    <option value="text">Article</option>
                    <option value="image">Image</option>
                    <option value="video">Video</option>
                    <option value="ad">Advertisement</option>
                </select>
                <label for="edit-content">Content:</label>
                <textarea name="content" id="edit-content" rows="4" required></textarea>
                <label for="edit-file">Upload File (optional):</label>
                <input type="file" name="file" id="edit-file">
                <button type="submit">Save Changes</button>
                <button type="button" onclick="closeEditModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        // JavaScript for responsive menu
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.getElementById('nav-links').classList.toggle('active');
        });

        // JavaScript for opening and closing the edit modal
        function openEditModal(postId, content, file, type) {
            document.getElementById('edit-post-id').value = postId;
            document.getElementById('edit-content').value = content;
            document.getElementById('edit-file').value = '';
            document.getElementById('edit-type').value = type;

            document.getElementById('edit-modal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('edit-modal').style.display = 'none';
        }
    </script>
</body>
</html>
