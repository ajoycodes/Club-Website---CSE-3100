<?php
/**
 * auth/db.php
 * PDO SQLite singleton.
 * To migrate to MySQL: swap the DSN and update AUTOINCREMENT → AUTO_INCREMENT.
 */

function getDB(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dbPath = __DIR__ . '/../data/kmind.db';

    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Enable WAL mode for better concurrency
    $pdo->exec('PRAGMA journal_mode=WAL;');
    $pdo->exec('PRAGMA foreign_keys=ON;');

    // Auto-migrate schema on first connection
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id          INTEGER  PRIMARY KEY AUTOINCREMENT,
            first_name  TEXT     NOT NULL,
            last_name   TEXT     NOT NULL,
            email       TEXT     UNIQUE NOT NULL,
            password    TEXT     NOT NULL,
            department  TEXT,
            background  TEXT,
            interest    TEXT,
            role        TEXT     NOT NULL DEFAULT 'member',
            status      TEXT     NOT NULL DEFAULT 'pending',
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS events_rsvp (
            id          INTEGER  PRIMARY KEY AUTOINCREMENT,
            user_id     INTEGER  NOT NULL,
            event_slug  TEXT     NOT NULL,
            event_title TEXT     NOT NULL,
            rsvp_status TEXT     DEFAULT 'going',
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, event_slug),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS events (
            id          INTEGER  PRIMARY KEY AUTOINCREMENT,
            slug        TEXT     UNIQUE NOT NULL,
            title       TEXT     NOT NULL,
            day         TEXT     NOT NULL,
            month       TEXT     NOT NULL,
            year        TEXT     NOT NULL DEFAULT '2026',
            location    TEXT     NOT NULL,
            event_time  TEXT     NOT NULL,
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS password_resets (
            id         INTEGER  PRIMARY KEY AUTOINCREMENT,
            user_id    INTEGER  NOT NULL,
            token      TEXT     UNIQUE NOT NULL,
            expires_at DATETIME NOT NULL,
            used       INTEGER  NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");

    // Seed default events on first run
    $evtCount = (int) $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
    if ($evtCount === 0) {
        $pdo->exec("INSERT INTO events (slug, title, day, month, year, location, event_time) VALUES
            ('transformers-workshop', 'Intro to Transformers Workshop',  '28', 'Apr', '2026', 'Room 301, CS Building',    '3:00 PM – 6:00 PM'),
            ('ml-hackathon-2026',     'ML Hackathon 2026',               '10', 'May', '2026', 'University Auditorium',    '48 Hours'),
            ('paper-reading-may',     'Research Paper Reading Group',    '20', 'May', '2026', 'Online (Google Meet)',     '5:00 PM – 7:00 PM'),
            ('cv-bootcamp',           'Computer Vision Bootcamp',        '15', 'Mar', '2026', 'Lab 4, Engineering Block', '2 Days')
        ");
    }

    return $pdo;
}
