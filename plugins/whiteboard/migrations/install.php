<?php
/**
 * Awan Whiteboard isolated schema.
 *
 * This file is intentionally called by the plugin lifecycle hook instead of
 * _database/schema.php so the plugin can be installed and removed independently.
 */
defined('AWAN') or die('Direct access denied.');

function whiteboard_migrate(Database $db): void {
    $ai = $db->driver() === 'sqlite'
        ? 'INTEGER PRIMARY KEY AUTOINCREMENT'
        : 'INT AUTO_INCREMENT PRIMARY KEY';
    $now = $db->driver() === 'sqlite' ? 'DATETIME' : 'DATETIME';

    $boards = plugin_table('whiteboard', 'boards');
    $objects = plugin_table('whiteboard', 'objects');
    $versions = plugin_table('whiteboard', 'versions');
    $members = plugin_table('whiteboard', 'board_members');
    $classrooms = plugin_table('whiteboard', 'classrooms');
    $classroomMembers = plugin_table('whiteboard', 'classroom_members');
    $notes = plugin_table('whiteboard', 'notes');
    $flashcards = plugin_table('whiteboard', 'flashcards');
    $comments = plugin_table('whiteboard', 'comments');
    $activity = plugin_table('whiteboard', 'activity_logs');

    $schemas = [
        "CREATE TABLE IF NOT EXISTS {$boards} (
            id {$ai},
            owner_id INTEGER NOT NULL,
            title VARCHAR(160) NOT NULL,
            description TEXT DEFAULT NULL,
            folder VARCHAR(40) NOT NULL DEFAULT 'personal',
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            viewport_json TEXT DEFAULT NULL,
            last_edited_by INTEGER DEFAULT NULL,
            created_at {$now} NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at {$now} NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS {$objects} (
            id {$ai},
            board_id INTEGER NOT NULL,
            object_type VARCHAR(24) NOT NULL,
            data_json TEXT NOT NULL,
            z_index INTEGER NOT NULL DEFAULT 0,
            created_by INTEGER DEFAULT NULL,
            created_at {$now} NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at {$now} NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS {$versions} (
            id {$ai},
            board_id INTEGER NOT NULL,
            created_by INTEGER DEFAULT NULL,
            label VARCHAR(160) DEFAULT NULL,
            snapshot_json TEXT NOT NULL,
            created_at {$now} NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS {$members} (
            id {$ai},
            board_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'viewer',
            invited_by INTEGER DEFAULT NULL,
            created_at {$now} NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (board_id, user_id)
        )",
        "CREATE TABLE IF NOT EXISTS {$classrooms} (
            id {$ai},
            teacher_id INTEGER NOT NULL,
            name VARCHAR(160) NOT NULL,
            description TEXT DEFAULT NULL,
            invite_code VARCHAR(80) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at {$now} NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at {$now} NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS {$classroomMembers} (
            id {$ai},
            classroom_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'student',
            created_at {$now} NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (classroom_id, user_id)
        )",
        "CREATE TABLE IF NOT EXISTS {$notes} (
            id {$ai},
            owner_id INTEGER NOT NULL,
            board_id INTEGER DEFAULT NULL,
            title VARCHAR(160) NOT NULL,
            content TEXT DEFAULT NULL,
            note_type VARCHAR(20) NOT NULL DEFAULT 'typed',
            tags_json TEXT DEFAULT NULL,
            created_at {$now} NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at {$now} NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS {$flashcards} (
            id {$ai},
            owner_id INTEGER NOT NULL,
            board_id INTEGER DEFAULT NULL,
            front TEXT NOT NULL,
            back TEXT NOT NULL,
            category VARCHAR(100) DEFAULT NULL,
            created_at {$now} NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at {$now} NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS {$comments} (
            id {$ai},
            board_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            body TEXT NOT NULL,
            object_id VARCHAR(80) DEFAULT NULL,
            created_at {$now} NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS {$activity} (
            id {$ai},
            board_id INTEGER DEFAULT NULL,
            user_id INTEGER NOT NULL,
            action VARCHAR(80) NOT NULL,
            details_json TEXT DEFAULT NULL,
            created_at {$now} NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
    ];

    foreach ($schemas as $sql) {
        $db->query($sql);
    }

    // SQLite supports IF NOT EXISTS for indexes; MySQL does not. Duplicate
    // index errors on a repeat activation are safe, but other errors are not.
    $indexes = [
        "CREATE INDEX idx_wb_boards_owner_status ON {$boards} (owner_id, status)",
        "CREATE INDEX idx_wb_objects_board ON {$objects} (board_id, z_index)",
        "CREATE INDEX idx_wb_versions_board ON {$versions} (board_id, created_at)",
        "CREATE INDEX idx_wb_members_user ON {$members} (user_id, board_id)",
        "CREATE INDEX idx_wb_classrooms_teacher ON {$classrooms} (teacher_id, status)",
        "CREATE INDEX idx_wb_notes_owner ON {$notes} (owner_id, updated_at)",
        "CREATE INDEX idx_wb_comments_board ON {$comments} (board_id, created_at)",
        "CREATE INDEX idx_wb_activity_board ON {$activity} (board_id, created_at)",
    ];
    foreach ($indexes as $sql) {
        try {
            $db->query($sql);
        } catch (Throwable $e) {
            $message = strtolower($e->getMessage());
            if (!str_contains($message, 'already exists')
                && !str_contains($message, 'duplicate key name')
                && !str_contains($message, 'duplicate')) {
                throw $e;
            }
        }
    }
}

function whiteboard_uninstall(Database $db): void {
    foreach ([
        'activity_logs', 'comments', 'flashcards', 'notes', 'classroom_members',
        'classrooms', 'board_members', 'versions', 'objects', 'boards',
    ] as $table) {
        $db->query('DROP TABLE IF EXISTS ' . plugin_table('whiteboard', $table));
    }
}
