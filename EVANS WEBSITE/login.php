<?php
session_start();
require 'config.php';

// Check if the user is already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin.php');
        exit;
    } else {
        header('Location: posts.php');
        exit;
    }
}

$login_attempts = $_SESSION['login_attempts'] ?? 0;

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if ($login_attempts >= 3) {
        $error = 'Too many login attempts. Please try again later.';
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']) ? true : false;

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['loggedin_time'] = time();
            $_SESSION['login_attempts'] = 0; // Reset login attempts

            if ($remember_me) {
                setcookie('username', $username, time() + (86400 * 30), "/"); // Cookie for 30 days
            }

            header('Location: admin.php');
            exit;
        } else {
            $_SESSION['login_attempts'] = $login_attempts + 1;
            $error = 'Invalid username or password';
        }
    }
}

// Handle forgot password request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])) {
    $email = $_POST['email'] ?? '';
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            // Generate token and send email (pseudo-code)
            $message = 'A password reset link has been sent to your email.';
        } else {
            $message = 'Email not found.';
        }
    } else {
        $message = 'Invalid email address.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EvansCode</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-form {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 100%;
            width: 100%;
            max-width: 400px;
            margin: 20px;
        }

        .login-form h2 {
            margin-top: 0;
            color: #333;
        }

        .login-form p {
            color: #555;
        }

        .login-form label {
            display: block;
            margin-bottom: 8px;
            color: #555;
        }

        .login-form input {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .login-form input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
            vertical-align: middle;
        }

        .login-form button {
            width: 100%;
            padding: 10px;
            background: #5cb85c;
            border: none;
            border-radius: 4px;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }

        .login-form button:hover {
            background: #4cae4c;
        }

        .login-form a {
            color: #5bc0de;
            text-decoration: none;
        }

        .login-form a:hover {
            text-decoration: underline;
        }

        .error, .message {
            color: #d9534f;
            margin-bottom: 20px;
        }

        .admin-notice {
            background: #f1f1f1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 20px;
            color: #333;
        }

        .reset-password-form {
            display: none;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .reset-password-form h3 {
            margin-top: 0;
        }

        .reset-password-form input {
            margin-bottom: 10px;
            width: calc(100% - 22px);
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .password-strength {
            margin: 10px 0;
            font-size: 14px;
        }

        .password-strength span {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .strength-weak {
            background-color: #d9534f;
        }

        .strength-medium {
            background-color: #f0ad4e;
        }

        .strength-strong {
            background-color: #5bc0de;
        }

        .password-strength-label {
            font-weight: bold;
        }

        .back-button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px;
            background: #d9534f;
            color: #fff;
            border-radius: 4px;
            text-align: center;
            text-decoration: none;
        }

        .back-button:hover {
            background: #c9302c;
        }
    </style>
</head>
<body>
    <div class="login-form">
        <h2>Admin Login</h2>
        <p class="admin-notice">This page is restricted to admin users only. Please enter your credentials to access the admin panel.</p>
        
        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if (isset($message)): ?>
            <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <div id="login-section">
            <form action="login.php" method="post">
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" value="<?= htmlspecialchars($_COOKIE['username'] ?? '') ?>" required>
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required oninput="checkPasswordStrength()">
                <div class="password-strength">
                    <span id="strength-indicator" class="strength-weak"></span>
                    <span id="strength-text" class="password-strength-label">Password Strength: </span><span id="strength-status">Weak</span>
                </div>
                <label>
                    <input type="checkbox" name="remember_me"> Remember me
                </label>
                <button type="submit" name="login">Login</button>
            </form>
            <p>Forgot your password? <a href="#" onclick="toggleResetPassword()">Reset here</a></p>
            <a href="posts.php" class="back-button">Back to Posts</a>
        </div>

        <div id="reset-password-section" class="reset-password-form">
            <h3>Forgot Password</h3>
            <form action="login.php" method="post">
                <label for="email">Enter your email:</label>
                <input type="email" name="email" id="email" required>
                <button type="submit" name="forgot_password">Reset Password</button>
            </form>
            <p><a href="#" onclick="toggleResetPassword()">Back to Login</a></p>
        </div>
        
        <p>By logging in, you agree to our <a href="terms.html">Terms of Service</a> and <a href="privacy.html">Privacy Policy</a>.</p>
    </div>

    <script>
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthIndicator = document.getElementById('strength-indicator');
            const strengthStatus = document.getElementById('strength-status');
            let strength = 'Weak';
            let color = '#d9534f'; // Default color for weak

            if (password.length > 8) {
                if (password.match(/[A-Z]/) && password.match(/[0-9]/)) {
                    strength = 'Strong';
                    color = '#5bc0de'; // Color for strong
                } else {
                    strength = 'Medium';
                    color = '#f0ad4e'; // Color for medium
                }
            }

            strengthIndicator.className = `strength-${strength.toLowerCase()}`;
            strengthIndicator.style.backgroundColor = color;
            strengthStatus.textContent = strength;
        }

        function toggleResetPassword() {
            const loginSection = document.getElementById('login-section');
            const resetPasswordSection = document.getElementById('reset-password-section');
            if (loginSection.style.display === 'none') {
                loginSection.style.display = 'block';
                resetPasswordSection.style.display = 'none';
            } else {
                loginSection.style.display = 'none';
                resetPasswordSection.style.display = 'block';
            }
        }
    </script>
</body>
</html>
