<?php
session_start();
require_once 'db_config.php';
$current_user_id = 1;

$last_damage = 0;

try {
    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['monster_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM monsters WHERE monster_id = :mid");
        $stmt->execute([':mid' => (int)$_GET['monster_id']]);
        $monster = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($monster) {
            $stmt_item = $pdo->prepare("
                SELECT ui.item_id 
                FROM users u 
                JOIN user_items ui ON u.equipped_user_item_id = ui.user_item_id 
                WHERE u.user_id = :uid ");
            $stmt_item->execute([':uid' => $current_user_id]);
            $top_id = $stmt_item->fetchColumn();

            $player_atk = 15;
            if ($top_id == 1) $player_atk = 100;
            elseif ($top_id == 2) $player_atk = 50;
            elseif ($top_id == 3) $player_atk = 30;

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

    if (!isset($_SESSION['battle'])) {
        header("Location: adventure.php");
        exit;
    }

    $b = &$_SESSION['battle'];

    $old_monster_hp = $b['monster_hp'];
    $old_player_hp = $b['player_hp'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'attack' && !$b['is_end']) {
        $dmg = rand($b['player_atk'] - 5, $b['player_atk'] + 5);
        $last_damage = $dmg;
        $b['monster_hp'] -= $dmg;
        $b['logs'][] = "あなたの攻撃！ " . htmlspecialchars($b['monster']['monster_name']) . " に {$dmg} のダメージ！";

        if ($b['monster_hp'] <= 0) {
            $b['monster_hp'] = 0;
            $b['is_end'] = true;
            $reward = (int)$b['monster']['reward_money'];
            $b['logs'][] = "★勝利！ {$reward} G を手に入れた！";

            $up_stmt = $pdo->prepare("UPDATE users SET money = money + :rev WHERE user_id = :uid");
            $up_stmt->execute([':rev' => $reward, ':uid' => $current_user_id]);
            
            $check_stmt = $pdo->prepare("SELECT money FROM users WHERE user_id = :uid");
            $check_stmt->execute([':uid' => $current_user_id]);
            $new_money = $check_stmt->fetchColumn();
            $_SESSION['user_money'] = $new_money; 
            $b['logs'][] = "（DB更新完了！ 現在の所持金: {$new_money} G）";
        } else {
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
        .monster-hit {
            animation: monster-shake 0.4s ease-in-out;
        }

         @keyframes monster-shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-15px); }
            75% { transform: translateX(15px); }
        }

         @keyframes player-damage-sequence {
            0% { transform: translateX(0); background-color: rgba(255, 0, 0, 0.7); }
            20% { transform: translateX(-15px); }
            40% { transform: translateX(15px); }
            60% { transform: translateX(-10px); }
            80% { transform: translateX(10px); background-color: transparent; }
            100% { transform: translateX(0); }
        }

        .monster-vfx-container {
            position: relative;
            display: inline-block;
            margin: 20px;
        }

        .damage-pop {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            animation: damage-ani 0.6s ease-out forwards;
            color: #ff4757;
            font-size: 2.5em;
            font-weight: bold;
            text-shadow: 2px 2px 0 #000;
            z-index: 10;
            pointer-events: none;
        }

        @keyframes monster-hp-reduce {
            from { width: <?php echo ($old_monster_hp / $b['max_monster_hp']) * 100; ?>%; }
            to   { width: <?php echo ($b['monster_hp'] / $b['max_monster_hp']) * 100; ?>%; }
        }
        .monster-hp-anim {
            animation: monster-hp-reduce 0.5s ease-out forwards;
        }
        
        @keyframes damage-ani {
            0% { transform: translate(-50%, 0); opacity: 1; }
            100% { transform: translate(-50%, -60px); opacity: 0; }

            @keyframes player-hp-reduce {
            from { width: <?php echo $old_player_hp; ?>%; }
            to   { width: <?php echo $b['player_hp']; ?>%; }
        }
        .player-hp-anim {
            animation: player-hp-reduce 0.6s ease-out 0.5s forwards;
        }
        }

        body { background: #111; color: #fff; text-align: center; font-family: sans-serif; }
        .battle-container { max-width: 400px; margin: 20px auto; border: 2px solid #fff; padding: 20px; }
        .hp-bar { background: #444; height: 15px; border: 1px solid #fff; margin: 5px 0; position: relative; overflow: hidden; }
        
        .hp-fill { height: 100%; background: #2ecc71; transition: width 0.3s ease-out; }
        .monster-hp { background: #e74c3c; }

        .player-hp-fill {
            transition: width 0.6s ease-out 0.8s;
        }

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

    <div class="monster-vfx-container <?php echo ($last_damage > 0) ? 'monster-hit' : ''; ?>">
        <?php if ($last_damage > 0): ?>
            <div class="damage-pop">-<?php echo $last_damage; ?></div>
        <?php endif; ?>

        <div style="font-size: 60px;">
            <?php 
                $monster_icons = [
                    'スライム' => '💧', 'ゴブリン' => '👺', 'ドラゴン' => '🐉', '魔王' => '👿'
                ];
                echo $monster_icons[$b['monster']['monster_name']] ?? '👾'; 
            ?>
        </div>
    </div>

    <p>YOUR HP: <?php echo $b['player_hp']; ?> / 100</p>
    
    <div class="hp-bar <?php echo ($last_damage > 0) ? 'player-hit' : ''; ?>">
        <div class="hp-fill player-hp-fill" style="width: <?php echo max(0, $b['player_hp']); ?>%;"></div>
    </div>

    <?php if (!$b['is_end']): ?>
        <form method="POST">
            <button type="submit" name="action" value="attack" class="btn-atk">たたかう</button>
        </form>
    <?php else: ?>
        <p>【戦闘終了】</p>
        <p><a href="adventure.php" style="color: #3498db;">冒険へ戻る</a></p>
        <?php unset($_SESSION['battle']); ?>
    <?php endif; ?>

    <div class="log-box">
        <?php foreach (array_reverse($b['logs']) as $log): ?>
            <div style="margin-bottom: 5px; border-bottom: 1px solid #222;">> <?php echo $log; ?></div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>