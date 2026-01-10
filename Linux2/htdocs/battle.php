<?php
session_start();
require_once 'db_config.php';
$current_user_id = 1; // ログイン機能がない間は1で固定

try {
    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['monster_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM monsters WHERE monster_id = :mid");
        $stmt->execute([':mid' => (int)$_GET['monster_id']]);
        $monster = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($monster) {
            // 最強武器の攻撃力を判定
            $stmt_item = $pdo->prepare("SELECT MIN(item_id) FROM user_items WHERE user_id = :uid");
            $stmt_item->execute([':uid' => $current_user_id]);
            $top_id = $stmt_item->fetchColumn();

            $player_atk = 15; // デフォルト攻撃力
            if ($top_id == 1) $player_atk = 100;      // SSR
            elseif ($top_id == 2) $player_atk = 50;   // SR
            elseif ($top_id == 3) $player_atk = 30;   // R

            // バトル情報をセッションに保存
            $_SESSION['battle'] = [
                'monster' => $monster,
                'player_hp' => 100,
                'max_monster_hp' => (int)$monster['required_power'] * 5,
                'monster_hp' => (int)$monster['required_power'] * 5,
                'logs' => [htmlspecialchars($monster['monster_name']) . " があらわれた！"],
                'is_end' => false,
                'player_atk' => $player_atk
            ];
        }
    }

    // セッションがなければ冒険画面に戻す
    if (!isset($_SESSION['battle'])) {
        header("Location: adventure.php");
        exit;
    }

    $b = &$_SESSION['battle'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'attack' && !$b['is_end']) {
        
        // プレイヤーの攻撃
        $dmg = rand($b['player_atk'] - 5, $b['player_atk'] + 5);
        $b['monster_hp'] -= $dmg;
        $b['logs'][] = "あなたの攻撃！ " . htmlspecialchars($b['monster']['monster_name']) . " に {$dmg} のダメージ！";

        if ($b['monster_hp'] <= 0) {
            $b['monster_hp'] = 0;
            $b['is_end'] = true;
            $reward = (int)$b['monster']['reward_money'];
            $b['logs'][] = "★勝利！ {$reward} G を手に入れた！";

            // 1. データベースの金額を更新
            $up_stmt = $pdo->prepare("UPDATE users SET money = money + :rev WHERE user_id = :uid");
            $up_stmt->execute([':rev' => $reward, ':uid' => $current_user_id]);
            
            // 【重要】2. 本当に更新されたか、DBから最新の所持金を再取得する
            $check_stmt = $pdo->prepare("SELECT money FROM users WHERE user_id = :uid");
            $check_stmt->execute([':uid' => $current_user_id]);
            $new_money = $check_stmt->fetchColumn();

            // 【重要】3. ガチャ画面などがセッションを参照している場合のため、セッションも更新
            $_SESSION['user_money'] = $new_money; 

            $b['logs'][] = "（DB更新完了！ 現在の所持金: {$new_money} G）";
        } else {
            // 敵の反撃
            $m_dmg = rand(5, 15);
            $b['player_hp'] -= $m_dmg;
            $b['logs'][] = htmlspecialchars($b['monster']['monster_name']) . " の攻撃！ {$m_dmg} のダメージ！";

            if ($b['player_hp'] <= 0) {
                $b['player_hp'] = 0;
                $b['is_end'] = true;
                $b['logs'][] = "敗北した...";
            }
        }
    }

} catch (PDOException $e) {
    die("エラー:" . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>バトル</title>
    <style>
        body { background: #111; color: #fff; text-align: center; font-family: sans-serif; }
        .battle-container { max-width: 400px; margin: 20px auto; border: 2px solid #fff; padding: 20px; }
        .hp-bar { background: #444; height: 15px; border: 1px solid #fff; margin: 5px 0; position: relative; }
        .hp-fill { height: 100%; background: #2ecc71; transition: width 0.3s; }
        .monster-hp { background: #e74c3c; }
        .log-box { background: #000; height: 150px; overflow-y: auto; text-align: left; padding: 10px; font-size: 0.8em; margin-top: 10px; border: 1px solid #555; }
        .btn-atk { background: #fff; color: #000; padding: 10px 40px; font-weight: bold; border: none; cursor: pointer; margin: 10px; }
    </style>
</head>
<body>

<div class="battle-container">
    <h3><?php echo htmlspecialchars($b['monster']['monster_name']); ?></h3>
    <div class="hp-bar">
        <div class="hp-fill monster-hp" style="width: <?php echo max(0, ($b['monster_hp'] / $b['max_monster_hp']) * 100); ?>%;"></div>
    </div>

    <div style="font-size: 60px; margin: 20px;">👾</div>

    <p>YOUR HP: <?php echo $b['player_hp']; ?> / 100</p>
    <div class="hp-bar">
        <div class="hp-fill" style="width: <?php echo max(0, $b['player_hp']); ?>%;"></div>
    </div>

    <?php if (!$b['is_end']): ?>
        <form method="POST">
            <button type="submit" name="action" value="attack" class="btn-atk">たたかう</button>
        </form>
    <?php else: ?>
        <p>【戦闘終了】</p>
        <p><a href="adventure.php" style="color: #3498db;">冒険へ戻る</a></p>
        <?php // セッションをクリアして次のバトルに備える
              unset($_SESSION['battle']); ?>
    <?php endif; ?>

    <div class="log-box">
        <?php foreach (array_reverse($b['logs']) as $log): ?>
            <div style="margin-bottom: 5px; border-bottom: 1px solid #222;">> <?php echo $log; ?></div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>