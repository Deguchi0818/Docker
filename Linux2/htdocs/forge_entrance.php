<?php
require_once 'db_config.php';

$current_user_id = 1;

try{
        $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "
        SELECT
            ui.item_id,
            i.item_name,
            COUNT(ui.Item_id) AS total_count
        FROM
            user_items ui
        JOIN
            items i ON ui.item_id = i.item_id
        WHERE
            ui.user_id = :user_id
        GROUP BY
            ui.item_id, i.item_name
        HAVING
            COUNT(ui.item_id) >= 2
        ORDER BY
            i.item_id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
    $stmt->execute();

    $target_items = $stmt->fetchALL(PDO::FETCH_ASSOC);
}
catch (PDOException $e){
    die("データベースエラー: " . $e->getMessage());

}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>画面① 強化対象アイテム選択</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 60%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>強化対象アイテム選択</h1>

    <?php if (empty($target_items)): ?>
        <p>現在、強化のベースとして利用できるアイテムはありません。（同じアイテムIDを2個以上所持していません。）</p>
    <?php else: ?>
        <p>強化のベースとして利用するアイテムを選択してください。</p>
        <table>
            <thead>
                <tr>
                    <th>アイテムID</th>
                    <th>アイテム名</th>
                    <th>所持数</th>
                    <th>選択</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($target_items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['item_id']); ?></td>
                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                    <td><?php echo htmlspecialchars($item['total_count']); ?></td>
                    <td>
                        <a href="forge_select.php?target_item_id=<?php echo htmlspecialchars($item['item_id']); ?>">
                            強化する
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>