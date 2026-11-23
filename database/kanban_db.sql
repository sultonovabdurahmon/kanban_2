-- =====================================================
-- Тахтаи канбан — пойгоҳи додаҳо (MySQL / MariaDB, XAMPP)
-- Вариант 22: идоракунии вазифаҳои даста
-- Дар phpMyAdmin: Import ё SQL -> Paste -> Go
-- =====================================================
DROP DATABASE IF EXISTS kanban_db;
CREATE DATABASE kanban_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kanban_db;

-- 1. Корбарон -----------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(120) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('manager','member') NOT NULL DEFAULT 'member',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Лоиҳаҳо ------------------------------------------
CREATE TABLE projects (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(150) NOT NULL,
  description TEXT NULL,
  owner_id    INT UNSIGNED NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_projects_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Иштирокчиёни лоиҳа ------------------------------
CREATE TABLE project_members (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  joined_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_project_user (project_id, user_id),
  CONSTRAINT fk_pm_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_pm_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Сутунҳои тахта (сутуни охирин = «иҷрошуда») ------
CREATE TABLE columns (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  name       VARCHAR(80) NOT NULL,
  position   INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_columns_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Вазифаҳо ----------------------------------------
CREATE TABLE tasks (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id  INT UNSIGNED NOT NULL,
  column_id   INT UNSIGNED NOT NULL,
  title       VARCHAR(200) NOT NULL,
  description TEXT NULL,
  assignee_id INT UNSIGNED NULL,
  deadline    DATE NULL,
  priority    ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  position    INT NOT NULL DEFAULT 0,
  created_by  INT UNSIGNED NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tasks_column (column_id, position),
  INDEX idx_tasks_deadline (deadline),
  CONSTRAINT fk_tasks_project  FOREIGN KEY (project_id)  REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_tasks_column   FOREIGN KEY (column_id)   REFERENCES columns(id)  ON DELETE CASCADE,
  CONSTRAINT fk_tasks_assignee FOREIGN KEY (assignee_id) REFERENCES users(id)    ON DELETE SET NULL,
  CONSTRAINT fk_tasks_creator  FOREIGN KEY (created_by)  REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Шарҳҳо ------------------------------------------
CREATE TABLE task_comments (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  task_id    INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  comment    TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tc_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  CONSTRAINT fk_tc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Замимаҳо (файлҳо) -------------------------------
CREATE TABLE task_attachments (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  task_id    INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  file_name  VARCHAR(255) NOT NULL,
  file_path  VARCHAR(255) NOT NULL,
  file_size  INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ta_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  CONSTRAINT fk_ta_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Таърихи тағйироти вазифа: кӣ, кай ва чиро иваз кард
CREATE TABLE task_history (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  task_id    INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  action     VARCHAR(30) NOT NULL,
  field_name VARCHAR(40) NULL,
  old_value  VARCHAR(255) NULL,
  new_value  VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_th_task (task_id, created_at),
  CONSTRAINT fk_th_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  CONSTRAINT fk_th_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Маълумоти намунавӣ. Парол барои ҳамаи корбарон: password
-- =====================================================
INSERT INTO users (full_name, email, password_hash, role) VALUES
('Абдураҳмон Султонов', 'manager@kanban.tj', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager'),
('Шерали Раҳимов',      'sherali@kanban.tj', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member'),
('Мадина Каримова',     'madina@kanban.tj',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member'),
('Фирӯз Назаров',       'firuz@kanban.tj',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member');

INSERT INTO projects (name, description, owner_id) VALUES
('Сомонаи ширкат', 'Таҳияи сомонаи корпоративӣ: тарроҳӣ, фронтенд, бекенд ва озмоиш.', 1);

INSERT INTO project_members (project_id, user_id) VALUES (1,1),(1,2),(1,3),(1,4);

INSERT INTO columns (project_id, name, position) VALUES
(1, 'Барои иҷро', 0), (1, 'Дар иҷро', 1), (1, 'Иҷрошуда', 2);

INSERT INTO tasks (project_id, column_id, title, description, assignee_id, deadline, priority, position, created_by) VALUES
(1, 1, 'Тарҳи саҳифаи асосӣ дар Figma', 'Ҳамаи блокҳои саҳифаи аввал, версияи мобилӣ ва ранги асосӣ.', 3, DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'high', 0, 1),
(1, 1, 'Навиштани матни саҳифаи «Дар бораи мо»', 'Матни кӯтоҳ дар се забон.', 4, DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'low', 1, 1),
(1, 1, 'Танзими сервер ва домен', 'Хостинг, SSL ва почтаи корпоративӣ.', 2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'high', 2, 1),
(1, 2, 'HTML/CSS-и саҳифаи асосӣ', 'Тартиби адаптивӣ бо Flexbox ва Grid.', 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'medium', 0, 1),
(1, 2, 'Формаи алоқа бо PHP', 'Санҷиши маълумот ва фиристодани номаи электронӣ.', 4, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'medium', 1, 1),
(1, 3, 'Интихоби палитраи ранг ва шрифтҳо', NULL, 3, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'low', 0, 1),
(1, 3, 'Сохтани пойгоҳи додаҳо', 'Ҷадвалҳо ва алоқаҳо дар MySQL.', 2, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'high', 1, 1);

INSERT INTO task_comments (task_id, user_id, comment) VALUES
(4, 2, 'Сохтори асосӣ тайёр аст, ҳоло ба нишондодҳои мобилӣ машғулам.'),
(4, 1, 'Хуб, то пагоҳ анҷом диҳед.'),
(3, 2, 'Интизори дастрасӣ ба ҳисоби хостинг ҳастам.');

INSERT INTO task_history (task_id, user_id, action, field_name, old_value, new_value) VALUES
(4, 1, 'created', NULL, NULL, 'HTML/CSS-и саҳифаи асосӣ'),
(4, 2, 'moved', 'column', 'Барои иҷро', 'Дар иҷро'),
(3, 1, 'created', NULL, NULL, 'Танзими сервер ва домен'),
(7, 1, 'created', NULL, NULL, 'Сохтани пойгоҳи додаҳо'),
(7, 2, 'moved', 'column', 'Дар иҷро', 'Иҷрошуда');
