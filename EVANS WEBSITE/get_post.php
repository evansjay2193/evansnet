<?php
require 'config.php';

if (isset($_GET['id'])) {
    $postId = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($post) {
        echo json_encode($post);
    } else {
        echo json_encode(['error' => 'Post not found']);
    }
}
?>
