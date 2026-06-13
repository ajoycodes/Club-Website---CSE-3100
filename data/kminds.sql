-- ============================================================
--  kminds.sql  —  Run once in phpMyAdmin to set up the DB
--  Database: kminds
-- ============================================================

CREATE DATABASE IF NOT EXISTS kminds
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE kminds;

-- ── Users ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id               INT(11)      NOT NULL AUTO_INCREMENT,
    first_name       VARCHAR(100) NOT NULL,
    last_name        VARCHAR(100) NOT NULL,
    email            VARCHAR(255) NOT NULL,
    password         VARCHAR(255) NOT NULL,
    department       VARCHAR(255) DEFAULT NULL,
    background       VARCHAR(50)  DEFAULT NULL,
    interest         VARCHAR(50)  DEFAULT NULL,
    role             VARCHAR(20)  NOT NULL DEFAULT 'member',
    status           VARCHAR(20)  NOT NULL DEFAULT 'pending',
    remember_token   VARCHAR(64)  DEFAULT NULL,
    remember_expires DATETIME     DEFAULT NULL,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_email (email)
) ENGINE=InnoDB;

-- ── Events ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS events (
    id         INT(11)      NOT NULL AUTO_INCREMENT,
    slug       VARCHAR(255) NOT NULL,
    title      VARCHAR(255) NOT NULL,
    day        VARCHAR(2)   NOT NULL,
    month      VARCHAR(10)  NOT NULL,
    year       VARCHAR(4)   NOT NULL DEFAULT '2026',
    location   VARCHAR(255) NOT NULL,
    event_time VARCHAR(100) NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB;

-- ── Event RSVPs ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS events_rsvp (
    id          INT(11)      NOT NULL AUTO_INCREMENT,
    user_id     INT(11)      NOT NULL,
    event_slug  VARCHAR(255) NOT NULL,
    event_title VARCHAR(255) NOT NULL,
    rsvp_status VARCHAR(20)  DEFAULT 'going',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_event (user_id, event_slug),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Password Resets ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS password_resets (
    id         INT(11)     NOT NULL AUTO_INCREMENT,
    user_id    INT(11)     NOT NULL,
    token      VARCHAR(64) NOT NULL,
    expires_at DATETIME    NOT NULL,
    used       TINYINT(1)  NOT NULL DEFAULT 0,
    created_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_token (token),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Seed default events ───────────────────────────────────────────────────────
INSERT INTO events (slug, title, day, month, year, location, event_time) VALUES
    ('transformers-workshop-2026', 'Intro to Transformers Workshop', '28', 'Apr', '2026', 'Room 301, CS Building',    '3:00 PM – 6:00 PM'),
    ('ml-hackathon-2026',          'ML Hackathon 2026',              '10', 'May', '2026', 'University Auditorium',    '48 Hours'),
    ('paper-reading-may-2026',     'Research Paper Reading Group',   '20', 'May', '2026', 'Online (Google Meet)',     '5:00 PM – 7:00 PM'),
    ('cv-bootcamp-2026',           'Computer Vision Bootcamp',       '15', 'Mar', '2026', 'Lab 4, Engineering Block', '2 Days');
