<?php
require_once 'db_config.php';
$current_user_id = 1;

// 現在のフィルタ（レアリティ）を取得
$filter_id = isset($_GET['rarity']) ? (int)$_GET['rarity'] : 0;

try {
    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 現在の装備品IDを取得
    $stmt = $pdo->prepare("SELECT equipped_user_item_id FROM users WHERE user_id = :uid");
    $stmt->execute([':uid' => $current_user_id]);
    $equipped_id = $stmt->fetchColumn();

    // アイテム一覧を取得（装備中を最優先、次にレアリティ順にソート）
    $sql = "SELECT ui.user_item_id, i.item_name, i.item_id 
            FROM user_items ui 
            JOIN items i ON ui.item_id = i.item_id 
            WHERE ui.user_id = :uid";
    
    // フィルタが指定されている場合は条件追加
    if ($filter_id > 0) {
        $sql .= " AND i.item_id = :filter_id";
    }

    $sql .= " ORDER BY (ui.user_item_id = :equipped_id) DESC, i.item_id ASC, ui.user_item_id DESC";
    
    $stmt = $pdo->prepare($sql);
    $params = [':uid' => $current_user_id, ':equipped_id' => $equipped_id];
    if ($filter_id > 0) $params[':filter_id'] = $filter_id;
    
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) { die("エラー: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>装備管理</title>
    <style>
        body { background: #111; color: #fff; text-align: center; font-family: 'Courier New', monospace; padding: 20px; }
        .tabs { margin-bottom: 20px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; }
        .tab-btn { 
            background: #333; color: #eee; border: 1px solid #555; padding: 8px 15px; 
            text-decoration: none; border-radius: 20px; font-size: 0.9em;
        }
        .tab-btn.active { background: gold; color: #000; font-weight: bold; border-color: gold; }
        
        .inventory { max-width: 500px; margin: 0 auto; display: flex; flex-direction: column; gap: 10px; }
        .item-card { 
            background: #222; border: 2px solid #444; border-radius: 10px; padding: 12px; 
            display: flex; justify-content: space-between; align-items: center;
        }
        .equipped-card { border-color: gold; background: #2c2c1a; }
        .item-info { text-align: left; }
        .rarity-ssr { color: gold; font-weight: bold; }
        .btn-equip { background: #fff; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .status-badge { color: gold; font-weight: bold; border: 1px solid gold; padding: 3px 8px; border-radius: 5px; font-size: 0.8em; }
    </style>
</head>
<body>

    <h1>🛡️ 装備管理 🛡️</h1>

     <br><a href="index.php" style="color: #ccc;">メニューへ戻る</a>

    <div class="tabs">
        <a href="?rarity=0" class="tab-btn <?php echo $filter_id == 0 ? 'active' : ''; ?>">すべて</a>
        <a href="?rarity=1" class="tab-btn <?php echo $filter_id == 1 ? 'active' : ''; ?>">SSR</a>
        <a href="?rarity=2" class="tab-btn <?php echo $filter_id == 2 ? 'active' : ''; ?>">SR</a>
        <a href="?rarity=3" class="tab-btn <?php echo $filter_id == 3 ? 'active' : ''; ?>">R</a>
        <a href="?rarity=4" class="tab-btn <?php echo $filter_id == 4 ? 'active' : ''; ?>">N</a>
    </div>

    <div class="inventory">
        <?php if (empty($items)): ?>
            <p>対象のアイテムがありません。</p>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <?php $is_equipped = ($item['user_item_id'] == $equipped_id); ?>
                <div class="item-card <?php echo $is_equipped ? 'equipped-card' : ''; ?>">
                    <div class="item-info">
                        <div class="<?php echo ($item['item_name'] === 'SSR') ? 'rarity-ssr' : ''; ?>">
                            <?php echo htmlspecialchars($item['item_name']); ?>
                        </div>
                        <div style="font-size: 0.7em; color: #888;">ID: #<?php echo $item['user_item_id']; ?></div>
                    </div>
                    <div>
                        <?php if ($is_equipped): ?>
                            <span class="status-badge">装備中</span>
                        <?php else: ?>
                            <form action="equip_process.php" method="POST">
                                <input type="hidden" name="user_item_id" value="<?php echo $item['user_item_id']; ?>">
                                <button type="submit" class="btn-equip">装備する</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>
</html>