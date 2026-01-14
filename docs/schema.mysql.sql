-- 招生服务微信小程序 v0.3 (MVP) - MySQL 8.0 Schema
-- 说明：本 DDL 与 docs/CODEX_SPEC.md 数据模型一致，可用于快速落库或作为 Migration 参考。
-- 字符集：utf8mb4，存储引擎：InnoDB

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 用户与钱包
-- ----------------------------
DROP TABLE IF EXISTS user_profile;
CREATE TABLE user_profile (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  openid VARCHAR(64) NOT NULL,
  nickname VARCHAR(255) NULL,
  avatar_url VARCHAR(500) NULL,
  grade VARCHAR(32) NULL,
  city VARCHAR(64) NULL,
  privacy_settings JSON NULL,
  status ENUM('active','banned') NOT NULL DEFAULT 'active',
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_openid (openid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS user_wallet;
CREATE TABLE user_wallet (
  user_id BIGINT UNSIGNED NOT NULL,
  points INT NOT NULL DEFAULT 0,
  xp INT NOT NULL DEFAULT 0,
  level INT NOT NULL DEFAULT 1,
  updated_at DATETIME NULL,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_wallet_user FOREIGN KEY (user_id) REFERENCES user_profile(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS points_ledger;
CREATE TABLE points_ledger (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  delta_points INT NOT NULL DEFAULT 0,
  delta_xp INT NOT NULL DEFAULT 0,
  biz_type ENUM('task','checkin','test','redeem','lottery','admin_adjust') NOT NULL,
  biz_id BIGINT UNSIGNED NULL,
  idempotency_key VARCHAR(64) NULL,
  remark VARCHAR(255) NULL,
  created_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_user_created (user_id, created_at),
  UNIQUE KEY uk_user_biz_idem (user_id, biz_type, idempotency_key),
  CONSTRAINT fk_ledger_user FOREIGN KEY (user_id) REFERENCES user_profile(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 内容/招生（MVP：班型、线索、渠道）
-- ----------------------------
DROP TABLE IF EXISTS class_type;
CREATE TABLE class_type (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(128) NOT NULL,
  description TEXT NULL,
  plan_quota INT NULL,
  prereg_count INT NOT NULL DEFAULT 0,
  status ENUM('open','paused','full') NOT NULL DEFAULT 'open',
  show_quota TINYINT(1) NOT NULL DEFAULT 0,
  threshold_warn INT NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS channel_scene;
CREATE TABLE channel_scene (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  scene VARCHAR(64) NOT NULL,
  description VARCHAR(255) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_scene (scene)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS lead;
CREATE TABLE lead (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NULL,
  lead_type ENUM('pre_register','appointment') NOT NULL,
  name VARCHAR(64) NOT NULL,
  phone VARCHAR(32) NOT NULL,
  grade VARCHAR(32) NULL,
  class_type_id BIGINT UNSIGNED NULL,
  appointment_time DATETIME NULL,
  source_scene VARCHAR(64) NULL,
  follow_status ENUM('new','contacted','converted','invalid') NOT NULL DEFAULT 'new',
  meta_json JSON NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_user_created (user_id, created_at),
  KEY idx_scene_created (source_scene, created_at),
  CONSTRAINT fk_lead_user FOREIGN KEY (user_id) REFERENCES user_profile(id),
  CONSTRAINT fk_lead_class FOREIGN KEY (class_type_id) REFERENCES class_type(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 题库与测评
-- ----------------------------
DROP TABLE IF EXISTS topic;
CREATE TABLE topic (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(128) NOT NULL,
  description TEXT NULL,
  sort INT NOT NULL DEFAULT 0,
  status ENUM('online','offline') NOT NULL DEFAULT 'online',
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS test;
CREATE TABLE test (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  topic_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  mode ENUM('fixed','draw') NOT NULL DEFAULT 'fixed',
  question_count INT NOT NULL DEFAULT 0,
  time_estimate_sec INT NULL,
  reward_points INT NOT NULL DEFAULT 0,
  reward_xp INT NOT NULL DEFAULT 0,
  rule_json JSON NULL,
  status ENUM('online','offline') NOT NULL DEFAULT 'online',
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_topic (topic_id),
  CONSTRAINT fk_test_topic FOREIGN KEY (topic_id) REFERENCES topic(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS question;
CREATE TABLE question (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  test_id BIGINT UNSIGNED NULL,
  topic_id BIGINT UNSIGNED NULL,
  q_type ENUM('single','multi','scale') NOT NULL,
  stem TEXT NOT NULL,
  dimension_key VARCHAR(64) NULL,
  weight INT NOT NULL DEFAULT 1,
  status ENUM('online','offline') NOT NULL DEFAULT 'online',
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_test (test_id),
  KEY idx_topic (topic_id),
  CONSTRAINT fk_question_test FOREIGN KEY (test_id) REFERENCES test(id),
  CONSTRAINT fk_question_topic FOREIGN KEY (topic_id) REFERENCES topic(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `option`;
CREATE TABLE `option` (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  question_id BIGINT UNSIGNED NOT NULL,
  content VARCHAR(500) NOT NULL,
  score INT NOT NULL DEFAULT 0,
  dimension_key VARCHAR(64) NULL,
  status ENUM('online','offline') NOT NULL DEFAULT 'online',
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_question (question_id),
  CONSTRAINT fk_option_question FOREIGN KEY (question_id) REFERENCES question(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS attempt;
CREATE TABLE attempt (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  test_id BIGINT UNSIGNED NOT NULL,
  attempt_seed VARCHAR(64) NOT NULL,
  status ENUM('draft','submitted','abandoned') NOT NULL DEFAULT 'draft',
  started_at DATETIME NULL,
  submitted_at DATETIME NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_user_test (user_id, test_id, created_at),
  CONSTRAINT fk_attempt_user FOREIGN KEY (user_id) REFERENCES user_profile(id),
  CONSTRAINT fk_attempt_test FOREIGN KEY (test_id) REFERENCES test(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS attempt_item;
CREATE TABLE attempt_item (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  attempt_id BIGINT UNSIGNED NOT NULL,
  question_id BIGINT UNSIGNED NOT NULL,
  question_order INT NOT NULL,
  option_order_json JSON NOT NULL,
  answer_json JSON NULL,
  score INT NOT NULL DEFAULT 0,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_attempt_question (attempt_id, question_id),
  KEY idx_attempt_order (attempt_id, question_order),
  CONSTRAINT fk_item_attempt FOREIGN KEY (attempt_id) REFERENCES attempt(id),
  CONSTRAINT fk_item_question FOREIGN KEY (question_id) REFERENCES question(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS report;
CREATE TABLE report (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  test_id BIGINT UNSIGNED NOT NULL,
  attempt_id BIGINT UNSIGNED NOT NULL,
  summary TEXT NULL,
  result_json JSON NOT NULL,
  created_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_attempt_report (attempt_id),
  KEY idx_user_created (user_id, created_at),
  CONSTRAINT fk_report_user FOREIGN KEY (user_id) REFERENCES user_profile(id),
  CONSTRAINT fk_report_test FOREIGN KEY (test_id) REFERENCES test(id),
  CONSTRAINT fk_report_attempt FOREIGN KEY (attempt_id) REFERENCES attempt(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 任务与成长
-- ----------------------------
DROP TABLE IF EXISTS task_def;
CREATE TABLE task_def (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  task_type ENUM('daily','weekly','achievement','checkin') NOT NULL,
  code VARCHAR(64) NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  rule_json JSON NULL,
  reward_points INT NOT NULL DEFAULT 0,
  reward_xp INT NOT NULL DEFAULT 0,
  cooldown_sec INT NOT NULL DEFAULT 0,
  max_claim_per_day INT NULL,
  status ENUM('online','offline') NOT NULL DEFAULT 'online',
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_task_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS user_task;
CREATE TABLE user_task (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  task_def_id BIGINT UNSIGNED NOT NULL,
  progress INT NOT NULL DEFAULT 0,
  target INT NOT NULL DEFAULT 1,
  status ENUM('ongoing','claimable','claimed') NOT NULL DEFAULT 'ongoing',
  last_progress_at DATETIME NULL,
  claimed_at DATETIME NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_user_task (user_id, task_def_id),
  CONSTRAINT fk_user_task_user FOREIGN KEY (user_id) REFERENCES user_profile(id),
  CONSTRAINT fk_user_task_def FOREIGN KEY (task_def_id) REFERENCES task_def(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 权益与兑换
-- ----------------------------
DROP TABLE IF EXISTS benefit_def;
CREATE TABLE benefit_def (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  benefit_type ENUM('coupon','lottery_ticket','service','gift') NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  cost_points INT NOT NULL DEFAULT 0,
  validity_days INT NULL,
  stock INT NULL,
  per_user_limit INT NULL,
  status ENUM('online','offline') NOT NULL DEFAULT 'online',
  meta_json JSON NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS redeem_order;
CREATE TABLE redeem_order (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  benefit_def_id BIGINT UNSIGNED NOT NULL,
  cost_points INT NOT NULL DEFAULT 0,
  status ENUM('paid','refunded','cancelled') NOT NULL DEFAULT 'paid',
  idempotency_key VARCHAR(64) NOT NULL,
  created_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_user_idem (user_id, idempotency_key),
  CONSTRAINT fk_redeem_user FOREIGN KEY (user_id) REFERENCES user_profile(id),
  CONSTRAINT fk_redeem_benefit FOREIGN KEY (benefit_def_id) REFERENCES benefit_def(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS user_benefit;
CREATE TABLE user_benefit (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  benefit_def_id BIGINT UNSIGNED NOT NULL,
  status ENUM('active','used','expired') NOT NULL DEFAULT 'active',
  benefit_code VARCHAR(32) NOT NULL,
  expires_at DATETIME NULL,
  used_at DATETIME NULL,
  used_meta_json JSON NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_benefit_code (benefit_code),
  KEY idx_user_status (user_id, status),
  CONSTRAINT fk_user_benefit_user FOREIGN KEY (user_id) REFERENCES user_profile(id),
  CONSTRAINT fk_user_benefit_def FOREIGN KEY (benefit_def_id) REFERENCES benefit_def(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 兴趣搭子
-- ----------------------------
DROP TABLE IF EXISTS tag_def;
CREATE TABLE tag_def (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  category ENUM('interest','study','campus','habit') NOT NULL,
  name VARCHAR(64) NOT NULL,
  status ENUM('online','offline') NOT NULL DEFAULT 'online',
  created_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_category_name (category, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS user_tag;
CREATE TABLE user_tag (
  user_id BIGINT UNSIGNED NOT NULL,
  tag_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NULL,
  PRIMARY KEY (user_id, tag_id),
  CONSTRAINT fk_user_tag_user FOREIGN KEY (user_id) REFERENCES user_profile(id),
  CONSTRAINT fk_user_tag_def FOREIGN KEY (tag_id) REFERENCES tag_def(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS buddy_action;
CREATE TABLE buddy_action (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  target_user_id BIGINT UNSIGNED NOT NULL,
  action ENUM('like','skip','block') NOT NULL,
  created_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_user_target (user_id, target_user_id),
  KEY idx_target (target_user_id, created_at),
  CONSTRAINT fk_action_user FOREIGN KEY (user_id) REFERENCES user_profile(id),
  CONSTRAINT fk_action_target FOREIGN KEY (target_user_id) REFERENCES user_profile(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS buddy_match;
CREATE TABLE buddy_match (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_low_id BIGINT UNSIGNED NOT NULL,
  user_high_id BIGINT UNSIGNED NOT NULL,
  matched_at DATETIME NULL,
  status ENUM('active','ended') NOT NULL DEFAULT 'active',
  PRIMARY KEY (id),
  UNIQUE KEY uk_pair (user_low_id, user_high_id),
  CONSTRAINT fk_match_low FOREIGN KEY (user_low_id) REFERENCES user_profile(id),
  CONSTRAINT fk_match_high FOREIGN KEY (user_high_id) REFERENCES user_profile(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS buddy_report;
CREATE TABLE buddy_report (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  reporter_user_id BIGINT UNSIGNED NOT NULL,
  target_user_id BIGINT UNSIGNED NOT NULL,
  reason VARCHAR(255) NOT NULL,
  detail TEXT NULL,
  status ENUM('open','handled','rejected') NOT NULL DEFAULT 'open',
  handled_by BIGINT UNSIGNED NULL,
  handled_at DATETIME NULL,
  created_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_target_status (target_user_id, status, created_at),
  CONSTRAINT fk_report_reporter FOREIGN KEY (reporter_user_id) REFERENCES user_profile(id),
  CONSTRAINT fk_report_target FOREIGN KEY (target_user_id) REFERENCES user_profile(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS team;
CREATE TABLE team (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  team_type ENUM('buddy') NOT NULL DEFAULT 'buddy',
  user_low_id BIGINT UNSIGNED NOT NULL,
  user_high_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_team_pair (team_type, user_low_id, user_high_id),
  CONSTRAINT fk_team_low FOREIGN KEY (user_low_id) REFERENCES user_profile(id),
  CONSTRAINT fk_team_high FOREIGN KEY (user_high_id) REFERENCES user_profile(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS team_task_def;
CREATE TABLE team_task_def (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  rule_json JSON NULL,
  reward_points INT NOT NULL DEFAULT 0,
  reward_xp INT NOT NULL DEFAULT 0,
  status ENUM('online','offline') NOT NULL DEFAULT 'online',
  created_at DATETIME NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS team_task;
CREATE TABLE team_task (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  team_id BIGINT UNSIGNED NOT NULL,
  team_task_def_id BIGINT UNSIGNED NOT NULL,
  status ENUM('ongoing','completed') NOT NULL DEFAULT 'ongoing',
  progress_json JSON NULL,
  completed_at DATETIME NULL,
  created_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_team_status (team_id, status),
  CONSTRAINT fk_team_task_team FOREIGN KEY (team_id) REFERENCES team(id),
  CONSTRAINT fk_team_task_def FOREIGN KEY (team_task_def_id) REFERENCES team_task_def(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
