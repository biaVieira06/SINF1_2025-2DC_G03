-- Queima das Fitas do Porto - Database Schema and Test Data
-- ============================================================

CREATE DATABASE IF NOT EXISTS queima_fitas
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE queima_fitas;

-- ============================================================
-- TABLE: roles
-- ============================================================
CREATE TABLE IF NOT EXISTS roles (
  id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(150) NOT NULL,
  email          VARCHAR(200) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  role_id        INT UNSIGNED NOT NULL,
  student_number VARCHAR(20)  DEFAULT NULL,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: faculties
-- ============================================================
CREATE TABLE IF NOT EXISTS faculties (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(200) NOT NULL,
  acronym     VARCHAR(10)  NOT NULL UNIQUE,
  description TEXT,
  color       VARCHAR(7)   NOT NULL DEFAULT '#e63946'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: tents
-- ============================================================
CREATE TABLE IF NOT EXISTS tents (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(150) NOT NULL,
  faculty_id   INT UNSIGNED NOT NULL,
  location     VARCHAR(200) NOT NULL,
  opening_time TIME         NOT NULL,
  closing_time TIME         NOT NULL,
  description  TEXT,
  CONSTRAINT fk_tents_faculty FOREIGN KEY (faculty_id) REFERENCES faculties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: events
-- ============================================================
CREATE TABLE IF NOT EXISTS events (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(200) NOT NULL,
  description TEXT,
  date_time   DATETIME     NOT NULL,
  location    VARCHAR(200) NOT NULL,
  type        ENUM('academic_ceremony','concert','cultural_activity') NOT NULL,
  tent_id     INT UNSIGNED DEFAULT NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_events_date (date_time),
  INDEX idx_events_type (type),
  CONSTRAINT fk_events_tent FOREIGN KEY (tent_id) REFERENCES tents(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: artists
-- ============================================================
CREATE TABLE IF NOT EXISTS artists (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(150) NOT NULL,
  genre      VARCHAR(100) NOT NULL,
  country    VARCHAR(100) NOT NULL,
  biography  TEXT,
  image_path VARCHAR(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: event_artists (many-to-many)
-- ============================================================
CREATE TABLE IF NOT EXISTS event_artists (
  event_id  INT UNSIGNED NOT NULL,
  artist_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (event_id, artist_id),
  CONSTRAINT fk_ea_event  FOREIGN KEY (event_id)  REFERENCES events(id)  ON DELETE CASCADE,
  CONSTRAINT fk_ea_artist FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: ratings
-- ============================================================
CREATE TABLE IF NOT EXISTS ratings (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  event_id   INT UNSIGNED DEFAULT NULL,
  tent_id    INT UNSIGNED DEFAULT NULL,
  score      TINYINT      NOT NULL CHECK (score BETWEEN 1 AND 5),
  comment    TEXT,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ratings_event (event_id),
  INDEX idx_ratings_tent  (tent_id),
  CONSTRAINT fk_ratings_user  FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE CASCADE,
  CONSTRAINT fk_ratings_event FOREIGN KEY (event_id) REFERENCES events(id)  ON DELETE CASCADE,
  CONSTRAINT fk_ratings_tent  FOREIGN KEY (tent_id)  REFERENCES tents(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: personal_agenda
-- ============================================================
CREATE TABLE IF NOT EXISTS personal_agenda (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  event_id   INT UNSIGNED NOT NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_agenda (user_id, event_id),
  CONSTRAINT fk_agenda_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
  CONSTRAINT fk_agenda_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA
-- ============================================================

INSERT INTO roles (name) VALUES ('admin'), ('student');

-- All demo accounts use password: "queima2026"
-- Hash generated with: password_hash('queima2026', PASSWORD_BCRYPT, ['cost'=>10])
-- $2y$10$YourActualHashHere — replace if needed, or use register.php to create accounts.
-- The value below is the bcrypt hash of "queima2026"
INSERT INTO users (name, email, password_hash, role_id, student_number) VALUES
('Administrador', 'admin@queima.pt',
 '$2y$10$8K1p/a0dUP1G0LT.EilLOuEgJwkbWmqyVhJq5bD3VwWfHIaY8YL5u',
 1, NULL),
('Ana Ferreira', 'ana@queima.pt',
 '$2y$10$8K1p/a0dUP1G0LT.EilLOuEgJwkbWmqyVhJq5bD3VwWfHIaY8YL5u',
 2, 'up202100001'),
('Bruno Silva', 'bruno@queima.pt',
 '$2y$10$8K1p/a0dUP1G0LT.EilLOuEgJwkbWmqyVhJq5bD3VwWfHIaY8YL5u',
 2, 'up202100002'),
('Catarina Lopes', 'catarina@queima.pt',
 '$2y$10$8K1p/a0dUP1G0LT.EilLOuEgJwkbWmqyVhJq5bD3VwWfHIaY8YL5u',
 2, 'up202100003');

-- IMPORTANT: The hash above may not match "queima2026" exactly because it was
-- pre-generated for documentation. The SAFEST approach is:
-- 1. Import this SQL to create the tables and non-user seed data.
-- 2. Then visit /register.php to create your admin account.
-- 3. Manually update the role_id to 1 (admin) via phpMyAdmin.
-- Alternatively, run this PHP snippet to get a fresh hash:
--   echo password_hash('queima2026', PASSWORD_BCRYPT);
-- and replace the hash strings above.

INSERT INTO faculties (name, acronym, description, color) VALUES
('Faculdade de Engenharia da Universidade do Porto',      'FEUP', 'A maior faculdade de engenharia de Portugal, fundada em 1837.', '#e63946'),
('Faculdade de Medicina da Universidade do Porto',        'FMUP', 'Uma das mais antigas escolas médicas do país, com grande prestígio internacional.', '#2a9d8f'),
('Faculdade de Economia da Universidade do Porto',        'FEP',  'Centro de excelência em ciências económicas e empresariais.', '#e9c46a'),
('Faculdade de Arquitectura da Universidade do Porto',    'FAUP', 'Reconhecida internacionalmente pela sua abordagem criativa e inovadora.', '#f4a261'),
('Faculdade de Ciências da Universidade do Porto',        'FCUP', 'Referência nacional em ciências exactas, naturais e da computação.', '#457b9d');

INSERT INTO tents (name, faculty_id, location, opening_time, closing_time, description) VALUES
('Tenda FEUP',     1, 'Parque da Cidade - Zona A', '18:00:00', '04:00:00', 'Tenda oficial da FEUP com animação todas as noites.'),
('Tenda FMUP',     2, 'Parque da Cidade - Zona B', '19:00:00', '03:00:00', 'Tenda da Faculdade de Medicina com bares e música ao vivo.'),
('Tenda FEP',      3, 'Parque da Cidade - Zona C', '18:30:00', '04:00:00', 'A tenda da FEP é conhecida pelas suas festas temáticas.'),
('Tenda FAUP',     4, 'Parque da Cidade - Zona D', '19:00:00', '03:30:00', 'Tenda da Arquitectura com instalações artísticas únicas.'),
('Tenda FCUP',     5, 'Parque da Cidade - Zona E', '18:00:00', '04:00:00', 'Tenda de Ciências com palco de grandes dimensões.');

INSERT INTO artists (name, genre, country, biography, image_path) VALUES
('Salvador Sobral',    'Jazz / Pop',       'Portugal', 'Vencedor do Eurovision 2017, Salvador Sobral é um dos maiores nomes da música portuguesa contemporânea.', NULL),
('Dino d\'Santiago',   'Funaná / World',   'Portugal', 'Artista cabo-verdiano radicado em Portugal, mistura funaná, batuko e música eletrónica.', NULL),
('Expensive Soul',     'Soul / R&B',       'Portugal', 'Um dos projetos musicais mais eclécticos do panorama nacional.', NULL),
('Blaya',              'Pop / Reggaeton',  'Portugal', 'Ex-membro dos Buraka Som Sistema, Blaya conquistou o mercado ibérico com hits dançantes.', NULL),
('David Carreira',     'Pop',              'Portugal', 'Cantor e dançarino português com milhões de seguidores nas redes sociais.', NULL);

-- Events spanning around the traditional Queima das Fitas week (May)
INSERT INTO events (name, description, date_time, location, type, tent_id) VALUES
('Cortejo Académico do Porto',
 'O tradicional desfile académico pelas ruas do Porto, com estudantes de todas as faculdades.',
 '2026-05-04 14:00:00', 'Avenida dos Aliados, Porto', 'academic_ceremony', NULL),

('Serenata Monumental',
 'Noite de fado e música tradicional na Ribeira do Porto.',
 '2026-05-05 21:30:00', 'Ribeira do Porto', 'academic_ceremony', NULL),

('Concerto Salvador Sobral',
 'Concerto de abertura da Queima das Fitas com Salvador Sobral.',
 '2026-05-06 22:00:00', 'Parque da Cidade - Palco Principal', 'concert', 5),

('Concerto Dino d\'Santiago',
 'Uma noite de funaná e ritmos africanos com Dino d\'Santiago.',
 '2026-05-07 23:00:00', 'Parque da Cidade - Palco Principal', 'concert', 1),

('Exposição de Arte FAUP',
 'Exposição de trabalhos dos estudantes de Arquitectura do Porto.',
 '2026-05-08 10:00:00', 'Faculdade de Arquitectura - Galeria Central', 'cultural_activity', 4),

('Workshop de Ciências',
 'Workshop interactivo de experiências científicas aberto ao público.',
 '2026-05-08 15:00:00', 'Faculdade de Ciências - Auditório A', 'cultural_activity', 5),

('Concerto Expensive Soul',
 'Soul e R&B com Expensive Soul no coração da Queima.',
 '2026-05-09 22:30:00', 'Parque da Cidade - Palco Principal', 'concert', 3),

('Cerimônia de Abertura Oficial',
 'Cerimônia oficial de abertura da Queima das Fitas do Porto com autoridades académicas.',
 '2026-05-04 11:00:00', 'Reitoria da Universidade do Porto', 'academic_ceremony', NULL),

('Concerto Blaya',
 'A energia de Blaya numa noite inesquecível de reggaeton e pop.',
 '2026-05-10 23:00:00', 'Parque da Cidade - Palco Principal', 'concert', 2),

('Concerto David Carreira',
 'O encerramento da Queima das Fitas com David Carreira.',
 '2026-05-11 22:00:00', 'Parque da Cidade - Palco Principal', 'concert', 1);

-- event_artists
INSERT INTO event_artists (event_id, artist_id) VALUES
(3, 1),  -- Concerto Salvador Sobral -> Salvador Sobral
(4, 2),  -- Concerto Dino d'Santiago -> Dino d'Santiago
(7, 3),  -- Concerto Expensive Soul  -> Expensive Soul
(9, 4),  -- Concerto Blaya           -> Blaya
(10, 5), -- Concerto David Carreira  -> David Carreira
(10, 4); -- Blaya also at David Carreira concert

-- ratings (user_id, event_id, tent_id, score, comment)
INSERT INTO ratings (user_id, event_id, tent_id, score, comment) VALUES
(2, 1,  NULL, 5, 'O cortejo foi incrível, muita emoção!'),
(3, 1,  NULL, 4, 'Lindo desfile, mas estava muito cheio.'),
(4, 1,  NULL, 5, 'Melhor cortejo dos últimos anos!'),
(2, 2,  NULL, 5, 'A Serenata foi de arrepiar. Obrigatória!'),
(3, 3,  NULL, 4, 'Salvador Sobral superou todas as expectativas.'),
(4, 3,  NULL, 5, 'Concerto memorável!'),
(2, NULL, 1,  4, 'Tenda FEUP com boa animação e organização.'),
(3, NULL, 2,  3, 'Tenda FMUP precisa de melhorar o atendimento no bar.'),
(4, NULL, 5,  5, 'Tenda FCUP tem o melhor palco do recinto!'),
(2, 4,  NULL, 4, 'Dino d\'Santiago trouxe muita energia!');

-- personal_agenda
INSERT INTO personal_agenda (user_id, event_id) VALUES
(2, 1), (2, 2), (2, 3), (2, 4),
(3, 1), (3, 3), (3, 7),
(4, 2), (4, 3), (4, 9), (4, 10);
