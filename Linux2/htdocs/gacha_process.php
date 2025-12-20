<?php
require_once 'db_config.php';
$current_user_id = 1;
$cost = 100;

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Location: gacha_index.php');
    exit;
}

$won_item_name = "";
$won_item_id = 0;
$error_message = "";

try{
    // ★修正1: {%database} を {$database} に修正
    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->beginTransaction();

    // ★修正2: お金チェックの前に、DBから最新の所持金を取得する (FOR UPDATEでロック)
    $stmt = $pdo->prepare("SELECT money FROM users WHERE user_id = :uid FOR UPDATE");
    $stmt->execute([':uid' => $current_user_id]);
    $money = $stmt->fetchColumn(); // ここで初めて $money が定義される

    if($money === false || $money < $cost){
        throw new Exception("お金が足りません"); 
    }

    // ガチャリスト取得
    $sql = "SELECT gp.item_id, gp.weight, i.item_name
            FROM gacha_probabilities gp
            JOIN items i ON gp.item_id = i.item_id";
    $stmt = $pdo->query($sql);
    $gacha_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ★修正3: 抽選ロジックの適正化
    // 手順A: まず重みの合計を出す
    $total_weight = 0;
    foreach($gacha_list as $item){
        $total_weight += $item['weight'];
    }

    // 手順B: 乱数を作る (1 〜 合計値)
    $random = mt_rand(1, $total_weight);

    // 手順C: 当たり判定
    $current_weight = 0;
    foreach($gacha_list as $item){
        $current_weight += $item['weight'];
        if($random <= $current_weight){
            $won_item_id = $item['item_id'];
            $won_item_name = $item['item_name'];
            break; // 当たりが決まったらループを抜ける
        }
    }

    if(!$won_item_id){
        throw new Exception("抽選に失敗しました。");
    }

    // お金を減らす
    $stmt = $pdo->prepare("UPDATE users SET money = money - :cost WHERE user_id = :uid");
    $stmt->execute([':cost' => $cost, ':uid' => $current_user_id]);

    // アイテムを付与する
    $stmt = $pdo->prepare("INSERT INTO user_items (user_id, item_id) VALUES (:uid, :item_id)");
    $stmt->execute([':uid' => $current_user_id, ':item_id' => $won_item_id]);

    $pdo->commit();

} catch (Exception $e) {
    // ★修正4: $pdo があるかチェックしてからロールバック
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ガチャ結果</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 20px; background-color: #222; color: #fff; }
        .result-box { 
            border: 2px solid gold; padding: 40px; margin: 30px auto; max-width: 500px;
            background-color: #333; border-radius: 10px;
        }
        .item-name { font-size: 2.5em; font-weight: bold; color: gold; margin: 20px 0; }
        .btn-retry { background-color: #ff4757; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .btn-home { color: #ccc; margin-left: 15px; text-decoration: underline; }
    </style>
</head>
<body>

    <?php if ($error_message): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
        <a href="gacha_index.php" style="color: white;">戻る</a>
    <?php else: ?>
        
        <div class="result-box">
            <p>ガチャ結果！！</p>
            <div class="item-name">
                <?php echo htmlspecialchars($won_item_name); ?>
            </div>
            <p>を入手しました！</p>
        </div>

        <div style="margin-top: 30px;">
            <a href="gacha_index.php" class="btn-retry">もう一度引く</a>
            <a href="index.php" class="btn-home">メインメニューへ</a>
        </div>

    <?php endif; ?>

</body>
</html>