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
    ");

    return $pdo;
}
