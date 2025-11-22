CREATE USER 'data_user'@'localhost' IDENTIFIED BY 'data';
GRANT ALL PRIVILEGES ON * . * TO 'data_user'@'localhost';

DROP DATABASE IF EXISTS test_db;
CREATE DATABASE IF NOT EXISTS test_db;

use test_db

CREATE TABLE IF NOT EXISTS jobs(
    id  INTEGER,
    name VARCHAR(255)
);

insert into jobs(id, name) values (1, 'knight'); 
insert into jobs(id, name) values (2, 'wizard'); 
insert into jobs(id, name) values (3, 'thief'); 

CREATE TABLE IF NOT EXISTS users(
    id INTEGER,
    name VARCHAR(255),
    level  INTEGER,
    job_id  INTEGER
);

insert into users(id, name, level, job_id) values (1, 'abc', 45, 3);
insert into users(id, name, level, job_id) values (2, 'def', 21, 1);
insert into users(id, name, level, job_id) values (3, 'ghi', 37, 2);
insert into users(id, name, level, job_id) values (4, 'jkl', 26, 2);
insert into users(id, name, level, job_id) values (5, 'mno', 31, 3);
insert into users(id, name, level, job_id) values (6, 'pqr', 19, 1);
insert into users(id, name, level, job_id) values (7, 'stu', 42, 1);
insert into users(id, name, level, job_id) values (8, 'vwx', 29, 2);
insert into users(id, name, level, job_id) values (9, 'yz', 40, 3);

CREATE TABLE battle_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INTEGER,
    result_win BOOLEAN,
    create_date DATE
);

insert into battle_log (user_id, result_win, create_date) values (8, TRUE, '2025-01-05');
insert into battle_log (user_id, result_win, create_date) values (9, TRUE, '2025-01-05');
insert into battle_log (user_id, result_win, create_date) values (6, TRUE, '2025-01-05');
insert into battle_log (user_id, result_win, create_date) values (5, TRUE, '2025-01-05');
insert into battle_log (user_id, result_win, create_date) values (8, TRUE, '2025-01-05');
insert into battle_log (user_id, result_win, create_date) values (3, FALSE, '2025-01-05');
insert into battle_log (user_id, result_win, create_date) values (2, FALSE, '2025-01-05');
insert into battle_log (user_id, result_win, create_date) values (9, FALSE, '2025-01-05');
insert into battle_log (user_id, result_win, create_date) values (1, FALSE, '2025-01-05');
insert into battle_log (user_id, result_win, create_date) values (8, TRUE, '2025-01-05');
insert into battle_log (user_id, result_win, create_date) values (9, TRUE, '2025-01-05');
insert into battle_log (user_id, result_win, create_date) values (8, TRUE, '2025-01-05');
insert into battle_log (user_id, result_win, create_date) values (6, FALSE, '2025-01-05');
insert into battle_log (user_id, result_win, create_date) values (1, FALSE, '2025-01-06');
insert into battle_log (user_id, result_win, create_date) values (4, TRUE, '2025-01-06');
insert into battle_log (user_id, result_win, create_date) values (2, TRUE, '2025-01-06');
insert into battle_log (user_id, result_win, create_date) values (3, TRUE, '2025-01-06');