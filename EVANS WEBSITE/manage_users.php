<?php
session_start();
require 'config.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Handle form submission for creating new users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    // Check if the username already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        $error = 'Username already exists';
    } else {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Insert the new user into the database
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hashedPassword, $role]);

        $success = 'User created successfully';
    }
}

// Handle form submission for updating user passwords
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $userId = $_POST['user_id'] ?? 0;
    $newPassword = $_POST['new_password'] ?? '';

    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    // Update the user's password in the database
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashedPassword, $userId]);

    $success = 'Password updated successfully';
}

// Fetch all users
$stmt = $pdo->query("SELECT id, username, role FROM users");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - EvansCode</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .user-form, .user-list {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .user-form h2, .user-list h2 {
            margin-top: 0;
        }

        .user-form label {
            display: block;
            margin-bottom: 5px;
        }

        .user-form input, .user-form select, .user-form button {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .user-form button {
            background: #5cb85c;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }

        .user-form button:hover {
            background: #4cae4c;
        }

        .error {
            color: red;
        }

        .success {
            color: green;
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
        </div>
    </nav>

    <div class="user-form">
        <h2>Create New User</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php elseif (isset($success) && !isset($_POST['update_password'])): ?>
            <p class="success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>
        <form action="manage_users.php" method="post">
            <input type="hidden" name="create_user" value="1">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required>
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
            <label for="role">Role:</label>
            <select name="role" id="role" required>
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
            <button type="submit">Create User</button>
        </form>
    </div>

    <div class="user-form">
        <h2>Update User Password</h2>
        <?php if (isset($success) && isset($_POST['update_password'])): ?>
            <p class="success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>
        <form action="manage_users.php" method="post">
            <input type="hidden" name="update_password" value="1">
            <label for="user_id">Select User:</label>
            <select name="user_id" id="user_id" required>
                <?php foreach ($users as $user): ?>
                    <option value="<?= htmlspecialchars($user['id']) ?>"><?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['role']) ?>)</option>
                <?php endforeach; ?>
            </select>
            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" id="new_password" required>
            <button type="submit">Update Password</button>
        </form>
    </div>

    <div class="user-list">
        <h2>Existing Users</h2>
        <ul>
            <?php foreach ($users as $user): ?>
                <li><?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['role']) ?>)</li>
            <?php endforeach; ?>
        </ul>
    </div>

    <script>
        // JavaScript for responsive menu
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.getElementById('nav-links').classList.toggle('active');
        });
    </script>
</body>
</html>
