<?php
require_once 'db_config.php';
$current_user_id = 1;
$unit_cost = 100;

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Location: gacha_index.php');
    exit;
}

$num = isset($_POST['num']) ? (int)$_POST['num'] : 1;
if(!in_array($num, [1, 10])) $num = 1;

$cost = $unit_cost * $num;
$won_items = [];
$error_message = "";

try{
    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT money FROM users WHERE user_id = :uid FOR UPDATE");
    $stmt->execute([':uid' => $current_user_id]);
    $money = $stmt->fetchColumn(); 

    if($money === false || $money < $cost){
        throw new Exception("お金が足りません"); 
    }

    // ガチャリスト取得
    $sql = "SELECT gp.item_id, gp.weight, i.item_name
            FROM gacha_probabilities gp
            JOIN items i ON gp.item_id = i.item_id";
    $stmt = $pdo->query($sql);
    $gacha_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_weight = 0;
    foreach($gacha_list as $item){
        $total_weight += $item['weight'];
    }

    for($i = 0; $i < $num; $i++){
        $random = mt_rand(1, $total_weight);
        $current_weight = 0;
        foreach($gacha_list as $item){
            $current_weight += $item['weight'];
            if($random <= $current_weight){
                $won_items[] = [
                    'id' => $item['item_id'],
                    'name' => $item['item_name']
                ];
                break;
            }
        }
    }

    if(!$won_items){
        throw new Exception("抽選に失敗しました。");
    }

    // お金を減らす
    $stmt = $pdo->prepare("UPDATE users SET money = money - :cost WHERE user_id = :uid");
    $stmt->execute([':cost' => $cost, ':uid' => $current_user_id]);

    // アイテムを付与する
    $stmt = $pdo->prepare("INSERT INTO user_items (user_id, item_id) VALUES (:uid, :item_id)");
    foreach ($won_items as $item) {
        $stmt->execute([':uid' => $current_user_id, ':item_id' => $item['id']]);
    }
    $pdo->commit();

} catch (Exception $e) {
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
            border: 2px solid gold; padding: 20px; margin: 30px auto; max-width: 500px;
            background-color: #333; border-radius: 10px;
        }
        .item-list { text-align: left; display: inline-block; min-width: 250px; }
        .item-row { 
            font-size: 1.2em; padding: 10px; border-bottom: 1px solid #444; 
            display: flex; justify-content: space-between;
        }
        .item-row:last-child { border-bottom: none; }
        .rarity-ssr { color: gold; font-weight: bold; }
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
            <h2>ガチャ結果 (<?php echo $num; ?>回)</h2>
            <div class="item-list">
                <?php foreach ($won_items as $index => $item): ?>
                    <div class="item-row">
                        <span><?php echo ($index + 1); ?>回目:</span>
                        <span class="<?php echo ($item['name'] === 'SSR') ? 'rarity-ssr' : ''; ?>">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
            <p style="margin-top: 20px;">を合計 <?php echo $num; ?> 個入手しました！</p>
        </div>

        <div style="margin-top: 30px;">
            <a href="gacha_index.php" class="btn-retry">ガチャ画面へ</a>
            <a href="index.php" class="btn-home">メインメニューへ</a>
        </div>

    <?php endif; ?>

</body>
</html>