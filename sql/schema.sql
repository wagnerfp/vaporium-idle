-- Vaporium Idle — schema
CREATE DATABASE IF NOT EXISTS vaporium_idle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vaporium_idle;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  pass_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE vocations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  descricao VARCHAR(255) NOT NULL,
  hp_base INT NOT NULL, hp_pl INT NOT NULL,
  atk_base INT NOT NULL, atk_pl INT NOT NULL,
  def_base INT NOT NULL, def_pl INT NOT NULL,
  regen INT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE characters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(60) NOT NULL UNIQUE,
  vocation_id INT NOT NULL,
  level INT NOT NULL DEFAULT 1,
  experience BIGINT NOT NULL DEFAULT 0,
  gold INT NOT NULL DEFAULT 50,
  hp_cur INT NOT NULL DEFAULT 100,
  area_id INT NULL,
  hunt_since DATETIME NULL,
  last_tick_at DATETIME NULL,
  potions_s INT NOT NULL DEFAULT 5,
  potions_m INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (vocation_id) REFERENCES vocations(id)
) ENGINE=InnoDB;

CREATE TABLE areas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  min_level INT NOT NULL,
  descricao VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE monsters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  hp INT NOT NULL,
  attack INT NOT NULL,
  defense INT NOT NULL,
  experience INT NOT NULL,
  gold_min INT NOT NULL,
  gold_max INT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE area_monsters (
  area_id INT NOT NULL,
  monster_id INT NOT NULL,
  weight INT NOT NULL DEFAULT 1,
  PRIMARY KEY (area_id, monster_id),
  FOREIGN KEY (area_id) REFERENCES areas(id),
  FOREIGN KEY (monster_id) REFERENCES monsters(id)
) ENGINE=InnoDB;

CREATE TABLE items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  slot ENUM('arma','armadura') NOT NULL,
  attack INT NOT NULL DEFAULT 0,
  defense INT NOT NULL DEFAULT 0,
  price INT NOT NULL,
  min_level INT NOT NULL DEFAULT 1,
  vocation_id INT NULL
) ENGINE=InnoDB;

CREATE TABLE character_items (
  character_id INT NOT NULL,
  item_id INT NOT NULL,
  equipped TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (character_id, item_id),
  FOREIGN KEY (character_id) REFERENCES characters(id),
  FOREIGN KEY (item_id) REFERENCES items(id)
) ENGINE=InnoDB;

CREATE TABLE monster_loot (
  monster_id INT NOT NULL,
  item_id INT NOT NULL,
  chance INT NOT NULL,
  PRIMARY KEY (monster_id, item_id),
  FOREIGN KEY (monster_id) REFERENCES monsters(id),
  FOREIGN KEY (item_id) REFERENCES items(id)
) ENGINE=InnoDB;

CREATE TABLE hunt_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  character_id INT NOT NULL,
  ts DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  message VARCHAR(255) NOT NULL,
  INDEX (character_id, id)
) ENGINE=InnoDB;
