<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ゲームメニュー</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 40px; }
        .menu-box { max-width: 400px; margin: 0 auto; }
        .btn-menu {
            display: block; width: 100%; padding: 15px; margin-bottom: 20px;
            text-decoration: none; color: white; font-weight: bold; font-size: 1.2em;
            border-radius: 8px;
        }
        .btn-adventure { background-color: #48ff00ff; }
        .btn-forge { background-color: #007bff; } /* 青：強化 */
        .btn-gacha { background-color: #ff4757; } /* 赤：ガチャ */
    </style>
</head>
<body>

    <h1>メインメニュー</h1>

    <div class="menu-box">
        <a
        href="adventure.php" class="btn-menu btn-adventure">
            ⚔️ 冒険へ出る
        </a>
        <a href="equipment.php" class="btn-menu" style="background-color: #6c757d;">
        🛡️ 装備を変更する
        </a>
        <a href="forge_entrance.php" class="btn-menu btn-forge">
            🔨 装備を強化・進化させる
        </a>

        <a href="gacha_index.php" class="btn-menu btn-gacha">
            🔮 ガチャを回す
        </a>
    </div>

</body>
</html>