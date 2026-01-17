<?php
require_once 'db_config.php'; // DB設定読み込み
$current_user_id = 1;

// POSTメソッド以外は弾く
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$base_id     = isset($_POST['base_id']) ? (int)$_POST['base_id'] : 0;
$material_id = isset($_POST['material_id']) ? (int)$_POST['material_id'] : 0;

$result_item = null;
$before_item_name = "";
$error_message = "";

try {
    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->beginTransaction();

    // 所有権とアイテム情報のチェック
    // ベースと素材を取得し、所有者が自分か確認する
    $sql_check = "
        SELECT ui.user_item_id, ui.item_id, i.item_name 
        FROM user_items ui
        JOIN items i ON ui.item_id = i.item_id
        WHERE ui.user_id = :uid 
        AND ui.user_item_id IN (:base, :mat)
    ";
    $stmt = $pdo->prepare($sql_check);
    $stmt->execute([':uid' => $current_user_id, ':base' => $base_id, ':mat' => $material_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2個（ベースと素材）取得できたか確認
    if (count($items) !== 2) {
        throw new Exception("アイテムが存在しないか、所有権がありません。");
    }

    // どちらがベースか判定
    $base_item_data = null;
    foreach ($items as $item) {
        if ($item['user_item_id'] == $base_id) {
            $base_item_data = $item;
            break;
        }
    }
    $before_item_name = $base_item_data['item_name'];

    // 進化レシピの確認
    $sql_recipe = "
        SELECT target_item_id 
        FROM craft_recipes 
        WHERE material_item_id = :base_item_id
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql_recipe);
    $stmt->execute([':base_item_id' => $base_item_data['item_id']]);
    $recipe = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recipe) {
        throw new Exception("このアイテムはこれ以上進化できません（レシピが見つかりません）。");
    }

    $new_item_id = $recipe['target_item_id'];

    // 素材アイテムを削除（消費）
    $sql_delete = "DELETE FROM user_items WHERE user_item_id = :mat_id";
    $stmt = $pdo->prepare($sql_delete);
    $stmt->execute([':mat_id' => $material_id]);

    // ベースアイテムを進化（item_id を新しいIDに書き換える！）
    $sql_update = "UPDATE user_items SET item_id = :new_id WHERE user_item_id = :base_id";
    $stmt = $pdo->prepare($sql_update);
    $stmt->execute([':new_id' => $new_item_id, ':base_id' => $base_id]);

    // 進化後のデータを取得（表示用）
    $sql_select = "
        SELECT 
            ui.user_item_id, 
            i.item_name,
            i.item_id
        FROM user_items ui
        JOIN items i ON ui.item_id = i.item_id
        WHERE ui.user_item_id = :base_id
    ";
    $stmt = $pdo->prepare($sql_select);
    $stmt->execute([':base_id' => $base_id]);
    $result_item = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error_message = "進化エラー: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>進化結果</title>
    <style>
        body { font-family: sans-serif; padding: 20px; text-align: center; background-color: #f0f0f5; }
        .result-box { 
            border: 2px solid #6610f2; background-color: #fff; 
            padding: 40px; max-width: 600px; margin: 30px auto; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #6610f2; margin-bottom: 20px; }
        .evolution-display { display: flex; align-items: center; justify-content: center; margin: 30px 0; font-size: 1.2em; }
        .item-box { padding: 15px; border: 1px solid #ddd; border-radius: 8px; width: 40%; background: #fafafa; }
        .arrow { font-size: 2em; color: #6610f2; margin: 0 20px; font-weight: bold; }
        .new-item { border-color: #6610f2; background: #f3f0ff; color: #6610f2; font-weight: bold; }
        .btn-link { display: inline-block; margin: 10px; padding: 10px 20px; text-decoration: none; border-radius: 5px; color: white; }
        .btn-home { background-color: #6c757d; }
    </style>
</head>
<body>

    <?php if ($error_message): ?>
        <div style="color: red; padding: 20px; background: #fff0f0; border: 1px solid red; max-width: 600px; margin: auto;">
            <h2>進化失敗...</h2>
            <p><?php echo htmlspecialchars($error_message); ?></p>
            <a href="forge_entrance.php">メニューへ戻る</a>
        </div>
    <?php else: ?>
        
        <div class="result-box">
            <h1>🎉 EVOLUTION SUCCESS! 🎉</h1>
            <p>アイテムの進化に成功しました！</p>
            

            <div class="evolution-display">
                <div class="item-box">
                    <?php echo htmlspecialchars($before_item_name); ?>
                </div>

                <div class="arrow">➡</div>

                <div class="item-box new-item">
                    <?php echo htmlspecialchars($result_item['item_name']); ?>
                </div>
            </div>

            <p style="color: #666; font-size: 0.9rem;">(ID: #<?php echo $result_item['user_item_id']; ?> のアイテムIDが更新されました)</p>

            <div style="margin-top: 30px;">
                <a href="index.php" class="btn-link btn-home">
                    メニューへ戻る
                </a>
                <a href="forge_entrance.php" class="btn-link btn-home">
                    強化を続ける
                </a>
            </div>
        </div>

    <?php endif; ?>

</body>
</html>