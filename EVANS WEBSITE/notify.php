<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <style>
        .notification {
    padding: 15px;
    background-color: #f0f0f0;
    color: #333;
    border: 1px solid #ddd;
    border-radius: 5px;
    margin: 15px;
    text-align: center;
}

    </style>
</head>
<body>
    <!-- Existing content -->

    <!-- Subscription Success/Error Message -->
    <?php
    if (isset($_SESSION['message'])) {
        echo '<div class="notification">' . $_SESSION['message'] . '</div>';
        unset($_SESSION['message']);
    }
    ?>

    <!-- Rest of your HTML -->
    
    <script src="scrip.js"></script>
</body>
</html>
