<?php
require_once 'db_config.php';
$current_user_id = 1;

// 画面①からアイテム種類IDを受け取る
$target_item_id = isset($_GET['target_item_id']) ? (int)$_GET['target_item_id'] : 0;

$base_candidates = [];
$item_name = "";

try {
    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 該当するアイテムをすべて取得
    $sql = "
        SELECT
            ui.user_item_id AS unique_id,
            ui.item_id,
            i.item_name
        FROM user_items ui
        JOIN items i ON ui.item_id = i.item_id
        WHERE ui.user_id = :user_id AND ui.item_id = :target_item_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', $current_user_id, PDO::PARAM_INT);
    $stmt->bindValue(':target_item_id', $target_item_id, PDO::PARAM_INT);
    $stmt->execute();
    $base_candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($base_candidates)) {
        $item_name = $base_candidates[0]['item_name'];
    }

} catch(PDOException $e) {
    die("エラー: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ベース選択</title>
    <style>
        /* 見やすくするためのスタイル */
        body { font-family: sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        td, th { border: 1px solid #ddd; padding: 10px; }
        .btn-base { background-color: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>ステップ1：強化するベースを選択</h1>
    <p>「<?php echo htmlspecialchars($item_name); ?>」の中から、強化したい（残したい）個体を選んでください。</p>

    <table>
        <tr>
            <th>個体ID</th>
            <th>アイテム名</th>
            <th>選択</th>
        </tr>
        <?php foreach ($base_candidates as $item): ?>
        <tr>
            <td>#<?php echo htmlspecialchars($item['unique_id']); ?></td>
            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
            <td>
                <a href="forge_material.php?base_id=<?php echo $item['unique_id']; ?>&item_type_id=<?php echo $item['item_id']; ?>" class="btn-base">
                    これをベースにする
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div style="margin-top: 30px;">
        <a href="forge_entrance.php" style="text-decoration: none; color: #555; border: 1px solid #ccc; padding: 10px 20px; border-radius: 4px;">
            &laquo; メニュー（種類選択）へ戻る
        </a>
    </div>
    </body>
</body>
</html>