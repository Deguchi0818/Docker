<?php
require_once 'db_config.php';
$current_user_id = 1;

// 前の画面から「ベースの個体ID」と「アイテム種類ID」を受け取る
$base_id      = isset($_GET['base_id']) ? (int)$_GET['base_id'] : 0;
$item_type_id = isset($_GET['item_type_id']) ? (int)$_GET['item_type_id'] : 0;

$material_candidates = [];
$base_item_info = null;

try {
    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. ベース（強化元）の情報を取得（表示用）
    $sql_base = "
        SELECT ui.user_item_id, i.item_name 
        FROM user_items ui 
        JOIN items i ON ui.item_id = i.item_id 
        WHERE ui.user_item_id = :base_id
    ";
    $stmt = $pdo->prepare($sql_base);
    $stmt->bindValue(':base_id', $base_id, PDO::PARAM_INT);
    $stmt->execute();
    $base_item_info = $stmt->fetch(PDO::FETCH_ASSOC);


    // 2. 素材候補を取得（重要：ベースに選んだIDは除外する！）
    $sql_mat = "
        SELECT
            ui.user_item_id AS unique_id,
            i.item_name
        FROM user_items ui
        JOIN items i ON ui.item_id = i.item_id
        WHERE ui.user_id = :user_id 
          AND ui.item_id = :item_type_id
          AND ui.user_item_id != :base_id  /* ← ★ここが重要：ベース以外を取得 */
    ";

    $stmt = $pdo->prepare($sql_mat);
    $stmt->bindValue(':user_id', $current_user_id, PDO::PARAM_INT);
    $stmt->bindValue(':item_type_id', $item_type_id, PDO::PARAM_INT);
    $stmt->bindValue(':base_id', $base_id, PDO::PARAM_INT); // 除外条件用
    $stmt->execute();
    $material_candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("エラー: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>素材選択</title>
    <style>
        body { font-family: sans-serif; padding: 15px; }
        .base-info { background: #e6f7ff; padding: 15px; border: 1px solid #91d5ff; margin-bottom: 20px; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; }
        td, th { border: 1px solid #ddd; padding: 10px; }
        .btn-material { background-color: #dc3545; color: white; border: none; padding: 8px 15px; cursor: pointer; border-radius: 4px; display: block; margin: 0px auto;}
    </style>
</head>
<body>
    <h1>ステップ2：素材（生贄）を選択</h1>

    <div class="base-info">
        <strong>強化するアイテム（ベース）:</strong><br>
        ID: #<?php echo htmlspecialchars($base_item_info['user_item_id']); ?><br>
        名前: <?php echo htmlspecialchars($base_item_info['item_name']); ?>
    </div>

    <p>素材にするアイテムを選んでください。<span style="color:red; font-weight:bold;">※素材にしたアイテムは消滅します。</span></p>

    <?php if (empty($material_candidates)): ?>
        <p>素材にできるアイテムがありません。（ベース以外にもう1つ必要です）</p>
        <a href="index.php">戻る</a>
    <?php else: ?>
        <table>
            <tr>
                <th>個体ID</th>
                <th>アイテム名</th>
                <th>操作</th>
            </tr>
            <?php foreach ($material_candidates as $item): ?>
            <tr>
                <td>#<?php echo htmlspecialchars($item['unique_id']); ?></td>
                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                <td>
                    <form action="forge_process.php" method="POST" onsubmit="return confirm('本当に合成しますか？\nベース：#<?php echo $base_id; ?>\n素材：#<?php echo $item['unique_id']; ?>');">
                        <input type="hidden" name="base_id" value="<?php echo $base_id; ?>">
                        <input type="hidden" name="material_id" value="<?php echo $item['unique_id']; ?>">
                        
                        <button type="submit" class="btn-material">素材にする</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
<div style="margin-top: 30px;">
        <a href="forge_select.php?target_item_id=<?php echo htmlspecialchars($item_type_id); ?>" 
           style="text-decoration: none; color: #555; border: 1px solid #ccc; padding: 10px 20px; border-radius: 4px;">
            &laquo; ベース選択に戻る
        </a>
        
        &nbsp;&nbsp; <a href="index.php" style="color: #999; text-decoration: underline;">
            メニューへ戻る
        </a>
    </div>
    </body>
</html>