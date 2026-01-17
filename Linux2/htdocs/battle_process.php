<?php
require_once 'db_config.php';
$current_user_id = 1;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$monster_id = (int)$_POST['monster_id'];

try {
    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->beginTransaction();

    // 1. モンスター情報取得
    $stmt = $pdo->prepare("SELECT * FROM monsters WHERE monster_id = :mid");
    $stmt->execute([':mid' => $monster_id]);
    $monster = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. プレイヤーの最強武器による戦闘力計算
    // 例: SSR(ID:1)なら100点、N(ID:4)なら10点
    $stmt = $pdo->prepare("SELECT MIN(item_id) FROM user_items WHERE user_id = :uid");
    $stmt->execute([':uid' => $current_user_id]);
    $top_id = $stmt->fetchColumn();

    $player_power = 0;
    if ($top_id == 1) $player_power = 100; // SSR
    elseif ($top_id == 2) $player_power = 50;  // SR
    elseif ($top_id == 3) $player_power = 20;  // R
    elseif ($top_id == 4) $player_power = 10;  // N

    // 3. 勝敗判定 (乱数 + パワー)
    $win_chance = ($player_power / $monster['required_power']) * 50; // 適当な計算式
    $random_val = mt_rand(1, 100);
    $is_win = ($random_val <= $win_chance);

    $msg = "";
    if ($is_win) {
        $reward = $monster['reward_money'];
        $stmt = $pdo->prepare("UPDATE users SET money = money + :rev WHERE user_id = :uid");
        $stmt->execute([':rev' => $reward, ':uid' => $current_user_id]);
        $msg = "勝利！ {$reward} G を手に入れた！";
    } else {
        $msg = "敗北... もっと武器を強化しよう。";
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    $msg = "エラー: " . $e->getMessage();
}
?>

<style>
    body { background: #000; color: #fff; text-align: center; font-family: 'Courier New', monospace; }
    .battle-scene { margin-top: 50px; }
    .monster-vfx { font-size: 100px; margin-bottom: 20px; transition: transform 0.1s; }

    .log-box { background: #222; border: 2px solid #fff; padding: 20px; max-width: 500px; margin: 20px auto; text-align: left; height: 150px; overflow-y: auto; }
    .win { color: #2ecc71; font-weight: bold; }
    .lose { color: #e74c3c; font-weight: bold; }
</style>

<div class="battle-scene">
    <div class="monster-vfx <?php echo $is_win ? 'shake' : ''; ?>">
      <?php 
    $monster_icons = [
    'スライム' => '💧',
    'ゴブリン' => '👺',
    'ドラゴン' => '🐉',
    '魔王'     => '👿'
    ];
    // モンスター名からアイコンを取得し、なければ👾を出す
    $current_icon = $monster_icons[$b['monster']['monster_name']] ?? '👾';
?>

<div style="font-size: 60px; margin: 20px;">
    <?php echo $current_icon; ?>
</div>

    <div class="log-box">
        <p>>> <?php echo htmlspecialchars($monster['monster_name']); ?> があらわれた！</p>
        <p>>> あなたの攻撃！</p>
        <?php if ($is_win): ?>
            <p class="win">>> 会心の一撃！ <?php echo htmlspecialchars($monster['monster_name']); ?> を倒した！</p>
            <p class="win">>> <?php echo number_format($reward); ?> Gを手に入れた！</p>
        <?php else: ?>
            <p>>> しかし 攻撃はかわされた！</p>
            <p class="lose">>> <?php echo htmlspecialchars($monster['monster_name']); ?> の反撃！あなたは逃げ出した...</p>
        <?php endif; ?>
    </div>
</div>

<!DOCTYPE html>
<html lang="ja">
    <div style="width: 300px; background: #444; height: 20px; margin: 0 auto; border: 2px solid #fff;">
    <div style="width: <?php echo $is_win ? '0%' : '100%'; ?>; background: #2ecc71; height: 100%; transition: width 1.5s;"></div>
</div>
<p>MONSTER HP</p>
<head>
    <meta charset="UTF-8">
    <title>討伐結果</title>
    <style>
        body { background: #111; color: #fff; text-align: center; padding-top: 50px; }
        .result { font-size: 2em; margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="result"><?php echo $msg; ?></div>
    <a href="adventure.php" style="color: gold;">冒険一覧へ</a><br><br>
    <a href="gacha_index.php" style="color: gold;">ガチャを回して強くする</a>
</body>


</html>