<?php
/**
 * AWAN Notifications helper
 * Creates admin notifications and provides counts/fetches.
 */
defined('AWAN') or die();

class Notifications {

    public static function create(Database $db, string $type, string $title, string $message = '', string $url = ''): void {
        try {
            $db->insert('notifications', [
                'type'       => $type,
                'title'      => $title,
                'message'    => $message,
                'url'        => $url,
                'is_read'    => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            // Non-fatal — notifications must not break parent flows
        }
    }

    public static function unreadCount(Database $db): int {
        try {
            return (int) $db->count('notifications', 'is_read = 0');
        } catch (Throwable $e) {
            return 0;
        }
    }

    public static function markRead(Database $db, int $id): void {
        $db->update('notifications', ['is_read' => 1], 'id = ?', [$id]);
    }

    public static function markAllRead(Database $db): void {
        $db->query("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
    }

    public static function delete(Database $db, int $id): void {
        $db->delete('notifications', 'id = ?', [$id]);
    }

    public static function deleteAll(Database $db): void {
        $db->query("DELETE FROM notifications");
    }

    /**
     * Get pending counts for the admin sidebar badges.
     * Returns an associative array of section => count.
     */
    public static function pendingCounts(Database $db): array {
        $counts = [];
        try {
            $counts['notifications']  = (int) $db->count('notifications', 'is_read = 0');
            $counts['quotes']         = (int) $db->count('quote_requests',   "status = 'new'");
            $counts['tool-requests']  = (int) $db->count('tool_requests',    "status = 'new'");
            $counts['contacts']       = (int) $db->count('contact_messages', "status = 'new'");
            $counts['reports']        = (int) $db->count('reports',          "status = 'new'");
        } catch (Throwable $e) {}
        return $counts;
    }
}
