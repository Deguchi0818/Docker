DROP USER IF EXISTS 'data_user'@'localhost';
CREATE USER 'data_user'@'localhost' IDENTIFIED BY 'data';
GRANT ALL PRIVILEGES ON * . * TO 'data_user'@'localhost';

DROP USER IF EXISTS 'data_user'@'%';
CREATE USER IF NOT EXISTS 'data_user'@'%' IDENTIFIED BY 'data';
GRANT ALL PRIVILEGES ON * . * TO 'data_user'@'%';
alter user 'data_user'@'%' identified with mysql_native_password by 'data';

DROP DATABASE IF EXISTS test_db;
CREATE DATABASE IF NOT EXISTS test_db;

use test_db

DROP TABLE IF EXISTS items;
CREATE TABLE IF NOT EXISTS items(
    item_id INT PRIMARY KEY,
    item_name VARCHAR(255)
    );

INSERT INTO items (item_id, item_name) VALUES (1, 'SSR');
INSERT INTO items (item_id, item_name) VALUES (2, 'SR');
INSERT INTO items (item_id, item_name) VALUES (3, 'R');
INSERT INTO items (item_id, item_name) VALUES (4, 'N');

DROP TABLE IF EXISTS users;
CREATE TABLE IF NOT EXISTS users(
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    user_name VARCHAR(255),
    money INT DEFAULT 3000,
    equipped_user_item_id INT DEFAULT NULL
    );

INSERT INTO users (user_name, money) VALUES ('test_user', 3000);

DROP TABLE IF EXISTS user_items;
CREATE TABLE IF NOT EXISTS user_items (
    user_item_id INT PRIMARY KEY AUTO_INCREMENT, 
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE
);

INSERT INTO user_items (user_id, item_id) VALUES (1, 2);
INSERT INTO user_items (user_id, item_id) VALUES (1, 2);
INSERT INTO user_items (user_id, item_id) VALUES (1, 3);
INSERT INTO user_items (user_id, item_id) VALUES (1, 3);
INSERT INTO user_items (user_id, item_id) VALUES (1, 3);
INSERT INTO user_items (user_id, item_id) VALUES (1, 4);
INSERT INTO user_items (user_id, item_id) VALUES (1, 4);
INSERT INTO user_items (user_id, item_id) VALUES (1, 4);

DROP TABLE IF EXISTS craft_recipes;
CREATE TABLE IF NOT EXISTS craft_recipes(
    recip_id INT PRIMARY KEY AUTO_INCREMENT,
    target_item_id INT NOT NULL,
    material_item_id INT NOT NULL,
    material_count INT DEFAULT 1,
    UNIQUE (target_item_id, material_item_id),
    FOREIGN KEY (target_item_id) REFERENCES items(item_id),
    FOREIGN KEY (material_item_id) REFERENCES items(item_id)
    );

INSERT INTO craft_recipes (target_item_id, material_item_id, material_count) VALUES (1, 2, 1);
INSERT INTO craft_recipes (target_item_id, material_item_id, material_count) VALUES (2, 3, 1);
INSERT INTO craft_recipes (target_item_id, material_item_id, material_count) VALUES (3, 4, 1);

DROP TABLE IF EXISTS gacha_probailities;
CREATE TABLE IF NOT EXISTS gacha_probabilities(
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    weight INT NOT NULL,
    FOREIGN KEY (item_id) REFERENCES items(item_id)
);

INSERT INTO gacha_probabilities (item_id, weight) VALUES (1, 1);
INSERT INTO gacha_probabilities (item_id, weight) VALUES (2, 10);
INSERT INTO gacha_probabilities (item_id, weight) VALUES (3, 30);
INSERT INTO gacha_probabilities (item_id, weight) VALUES (4, 59);

DROP TABLE IF EXISTS monsters;
CREATE TABLE IF NOT EXISTS monsters(
    monster_id INT PRIMARY KEY AUTO_INCREMENT,
    monster_name VARCHAR(255) NOT NULL,
    required_power INT NOT NULL,
    reward_money INT NOT NULL
);

INSERT INTO monsters (monster_name, required_power, reward_money) VALUES 
('スライム', 1, 50),
('ゴブリン', 10, 200),
('ドラゴン', 50, 1000),
('魔王', 100, 5000);