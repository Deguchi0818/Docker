<?php
require_once 'db_config.php';
$current_user_id = 1;

try{
    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4"; 
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql_item = "SELECT MIN(item_id) as top_id FROM user_items WHERE user_id = :uid";
    $stmt = $pdo->prepare($sql_item);
    $stmt->execute([':uid' => $current_user_id]);
    $top_item_id = $stmt->fetchColumn();

    $monsters = $pdo->query("SELECT * FROM monsters")->fetchAll(PDO::FETCH_ASSOC);

}catch (PDOException $e){die($e->getMessage());}
?>

<style>
    body { background: #000; color: #fff; text-align: center; font-family: 'Courier New', monospace; }
    .battle-scene { margin-top: 50px; }
    .monster-vfx { font-size: 100px; margin-bottom: 20px; transition: transform 0.1s; }
    
    /* 攻撃を受けた時の揺れアニメーション */
    .shake { animation: shake 0.5s; }
    @keyframes shake {
        0% { transform: translate(1px, 1px) rotate(0deg); }
        10% { transform: translate(-1px, -2px) rotate(-1deg); }
        30% { transform: translate(3px, 2px) rotate(0deg); }
        50% { transform: translate(-1px, 2px) rotate(1deg); }
        100% { transform: translate(1px, 1px) rotate(0deg); }
    }

    .log-box { background: #222; border: 2px solid #fff; padding: 20px; max-width: 500px; margin: 20px auto; text-align: left; height: 150px; overflow-y: auto; }
    .win { color: #2ecc71; font-weight: bold; }
    .lose { color: #e74c3c; font-weight: bold; }
</style>

<?php 
$monster_images = [
    'スライム' => '💧',
    'ゴブリン' => '👺',
    'ドラゴン' => '🐉',
    '魔王' => '👿'
];
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>冒険へ出る</title>
    <style>
        body {  color: #000000ff; font-family: sans-serif; text-align: center; }
        .monster-card { border: 1px solid #444; padding: 20px; margin: 10px auto; max-width: 400px; background: #ffffffff; border-radius: 8px; }
        .btn-fight { background: #e74c3c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    </style>
</head>
<body>
    <h1>冒険（お金稼ぎ）</h1>
    <p>現在の最強武器: <strong><?php echo $top_item_id ? "ID:".$top_item_id : "なし"; ?></strong></p>

   <?php foreach ($monsters as $m): ?>
    <div class="monster-card">
        <div style="font-size: 50px;">
            <?php echo $monster_images[$m['monster_name']] ?? '👾'; ?>
        </div>
        <div class="monster-name"><?php echo htmlspecialchars($m['monster_name']); ?></div>
        <p>推奨戦闘力: <span style="color: #ff4757; font-weight: bold;"><?php echo $m['required_power']; ?></span></p>
        <p class="reward">獲得報酬: <?php echo number_format($m['reward_money']); ?> G</p>
        
        <form action="battle.php" method="GET">
            <input type="hidden" name="monster_id" value="<?php echo $m['monster_id']; ?>">
            <button type="submit" class="btn-fight">⚔️ 討伐を開始する</button>
        </form>
    </div>
<?php endforeach; ?>
    
    <br><a href="index.php" style="color: #ccc;">戻る</a>
</body>
</html>