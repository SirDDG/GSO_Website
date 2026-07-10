<?php
/**
 * File: /includes/notification_helper.php
 *
 * Why:
 * - Central place for notification (mailbox) operations.
 */

if (!function_exists("createNotification")) {
    function createNotification(
        PDO $pdo,
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $link = null
    ): bool {
        try {
            if ($userId <= 0) {
                return false;
            }

            $stmt = $pdo->prepare("
                INSERT INTO notifications
                    (user_id, type, title, message, link)
                VALUES
                    (:user_id, :type, :title, :message, :link)
            ");

            return $stmt->execute([
                ":user_id" => $userId,
                ":type" => $type,
                ":title" => $title,
                ":message" => $message,
                ":link" => $link
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists("notificationExists")) {
    function notificationExists(
        PDO $pdo,
        int $userId,
        string $type,
        ?string $link = null,
        ?string $title = null,
        ?string $message = null
    ): bool {
        try {
            if ($userId <= 0) {
                return false;
            }

            if (!empty($link)) {
                $stmt = $pdo->prepare("
                    SELECT 1
                    FROM notifications
                    WHERE user_id = :user_id
                      AND type = :type
                      AND link = :link
                    LIMIT 1
                ");
                $stmt->execute([
                    ":user_id" => $userId,
                    ":type" => $type,
                    ":link" => $link
                ]);

                return (bool) $stmt->fetchColumn();
            }

            if ($title === null || $message === null) {
                return false;
            }

            $stmt = $pdo->prepare("
                SELECT 1
                FROM notifications
                WHERE user_id = :user_id
                  AND type = :type
                  AND title = :title
                  AND message = :message
                LIMIT 1
            ");
            $stmt->execute([
                ":user_id" => $userId,
                ":type" => $type,
                ":title" => $title,
                ":message" => $message
            ]);

            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists("getUnreadNotificationCount")) {
    function getUnreadNotificationCount(PDO $pdo, int $userId): int
    {
        try {
            if ($userId <= 0) {
                return 0;
            }

            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM notifications
                WHERE user_id = :user_id
                  AND is_read = 0
            ");
            $stmt->execute([":user_id" => $userId]);

            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }
}

if (!function_exists("getNotifications")) {
    function getNotifications(
        PDO $pdo,
        int $userId,
        int $limit = 20,
        int $offset = 0,
        bool $onlyUnread = false
    ): array {
        try {
            if ($userId <= 0) {
                return [];
            }

            $limit = max(1, min(200, $limit));
            $offset = max(0, $offset);

            $sql = "
                SELECT
                    notification_id,
                    type,
                    title,
                    message,
                    link,
                    is_read,
                    created_at
                FROM notifications
                WHERE user_id = :user_id
            ";

            $params = [":user_id" => $userId];

            if ($onlyUnread) {
                $sql .= " AND is_read = 0 ";
            }

            $sql .= " ORDER BY created_at DESC
                      LIMIT {$limit} OFFSET {$offset}";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists("markNotificationRead")) {
    function markNotificationRead(PDO $pdo, int $userId, int $notificationId): bool
    {
        try {
            if ($userId <= 0 || $notificationId <= 0) {
                return false;
            }

            $stmt = $pdo->prepare("
                UPDATE notifications
                SET is_read = 1
                WHERE notification_id = :notification_id
                  AND user_id = :user_id
                LIMIT 1
            ");

            return $stmt->execute([
                ":notification_id" => $notificationId,
                ":user_id" => $userId
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists("markAllNotificationsRead")) {
    function markAllNotificationsRead(PDO $pdo, int $userId): bool
    {
        try {
            if ($userId <= 0) {
                return false;
            }

            $stmt = $pdo->prepare("
                UPDATE notifications
                SET is_read = 1
                WHERE user_id = :user_id
                  AND is_read = 0
            ");

            return $stmt->execute([":user_id" => $userId]);
        } catch (Throwable $e) {
            return false;
        }
    }
}