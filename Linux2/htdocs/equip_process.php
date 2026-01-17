<?php
require_once 'db_config.php';
$current_user_id = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_item_id'])) {
    try {
        $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password);

        $sql = "UPDATE users SET equipped_user_item_id = :target_id WHERE user_id = :uid";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':target_id' => (int)$_POST['user_item_id'],
            ':uid' => $current_user_id
        ]);

        header("Location: equipment.php");
        exit;
    } catch (PDOException $e) { die($e->getMessage()); }
}