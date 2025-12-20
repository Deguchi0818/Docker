<?php
require_once 'db_config.php'; // DB設定読み込み
$current_user_id = 1;

$money = 0;

try{
    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT money FROM users WHERE user_id = :uid");
    $stmt->execute([':uid' => $current_user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if($result){
        $money = $result['money'];
    }
} catch (PDOException $e){
   die("エラー: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ガチャ</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 20px; background-color: #f4f4f4; }
        .gacha-machine { 
            background: white; padding: 30px; border-radius: 15px; display: inline-block;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-top: 20px;
        }
        .money-display { font-size: 1.2em; margin-bottom: 20px; color: #333; }
        .btn-gacha {
            background-color: #ff4757; color: white; font-size: 1.2em; padding: 10px 30px;
            border: none; border-radius: 30px; cursor: pointer;
        }
        .btn-gacha:hover { background-color: #ff6b81; }
        .btn-gacha:disabled { background-color: #ccc; cursor: not-allowed; }
        .menu-link { display: block; margin-top: 20px; color: #666; }
    </style>
</head>
<body>

    <h1>🔮 アイテムガチャ 🔮</h1>

    <div class="gacha-machine">
        <div class="money-display">
            所持金: <strong><?php echo number_format($money); ?> G</strong>
        </div>
        
        <p>1回 <strong>100 G</strong> で回せます</p>

        <?php if ($money >= 100): ?>
            <form action="gacha_process.php" method="POST">
                <button type="submit" class="btn-gacha">ガチャを回す！</button>
            </form>
        <?php else: ?>
            <p style="color: red;">お金が足りません</p>
            <button class="btn-gacha" disabled>ガチャを回す！</button>
        <?php endif; ?>
    </div>

    <br>
    <a href="index.php" class="menu-link">メインメニューへ戻る</a>

</body>
</html>