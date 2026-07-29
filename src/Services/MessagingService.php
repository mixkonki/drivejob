<?php

namespace Drivejob\Services;

use Drivejob\Core\Database;
use PDO;
use Exception;

class MessagingService
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Start a new conversation
     */
    public function startConversation($companyId, $driverId, $jobId, $subject, $initialMessage)
    {
        try {
            $this->pdo->beginTransaction();

            // Create conversation
            $stmt = $this->pdo->prepare("
                INSERT INTO conversations (company_id, driver_id, job_id, subject, last_message_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$companyId, $driverId, $jobId, $subject]);
            $conversationId = $this->pdo->lastInsertId();

            // Add initial message (without starting new transaction)
            $this->sendMessageInternal($conversationId, 'company', $companyId, $initialMessage);

            // Create notification for driver
            $this->createNotification(
                'driver',
                $driverId,
                'new_message',
                'Νέο μήνυμα',
                "Έχετε νέο μήνυμα σχετικά με: $subject",
                ['conversation_id' => $conversationId]
            );

            $this->pdo->commit();
            return $conversationId;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Send a message in a conversation (public method with transaction)
     */
    public function sendMessage($conversationId, $senderType, $senderId, $message, $attachments = null)
    {
        try {
            $this->pdo->beginTransaction();
            $messageId = $this->sendMessageInternal($conversationId, $senderType, $senderId, $message, $attachments);
            $this->pdo->commit();
            return $messageId;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Internal method to send message without transaction
     */
    private function sendMessageInternal($conversationId, $senderType, $senderId, $message, $attachments = null)
    {
        // Insert message
        $stmt = $this->pdo->prepare("
            INSERT INTO messages (conversation_id, sender_type, sender_id, message, attachments)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $conversationId,
            $senderType,
            $senderId,
            $message,
            $attachments ? json_encode($attachments) : null
        ]);
        $messageId = $this->pdo->lastInsertId();

        // Update conversation
        $unreadColumn = $senderType === 'company' ? 'driver_unread_count' : 'company_unread_count';
        $stmt = $this->pdo->prepare("
            UPDATE conversations 
            SET last_message_at = NOW(), 
                $unreadColumn = $unreadColumn + 1
            WHERE id = ?
        ");
        $stmt->execute([$conversationId]);

        // Get conversation details for notification
        $conversation = $this->getConversation($conversationId);

        // Create notification for recipient
        $recipientType = $senderType === 'company' ? 'driver' : 'company';
        $recipientId = $senderType === 'company' ? $conversation['driver_id'] : $conversation['company_id'];

        $this->createNotification(
            $recipientType,
            $recipientId,
            'new_message',
            'Νέο μήνυμα',
            mb_substr($message, 0, 100) . (mb_strlen($message) > 100 ? '...' : ''),
            ['conversation_id' => $conversationId, 'message_id' => $messageId]
        );

        return $messageId;
    }

    /**
     * Get conversation details
     */
    public function getConversation($conversationId)
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, 
                   comp.company_name,
                   CONCAT(d.first_name, ' ', d.last_name) as driver_name,
                   j.title as job_title
            FROM conversations c
            JOIN companies comp ON c.company_id = comp.id
            JOIN drivers d ON c.driver_id = d.id
            LEFT JOIN job_listings j ON c.job_id = j.id
            WHERE c.id = ?
        ");
        $stmt->execute([$conversationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get messages for a conversation
     */
    public function getMessages($conversationId, $limit = 50, $offset = 0)
    {
        $stmt = $this->pdo->prepare("
            SELECT m.*,
                   CASE 
                       WHEN m.sender_type = 'company' THEN comp.company_name
                       ELSE CONCAT(d.first_name, ' ', d.last_name)
                   END as sender_name
            FROM messages m
            JOIN conversations c ON m.conversation_id = c.id
            LEFT JOIN companies comp ON m.sender_type = 'company' AND m.sender_id = comp.id
            LEFT JOIN drivers d ON m.sender_type = 'driver' AND m.sender_id = d.id
            WHERE m.conversation_id = ?
            ORDER BY m.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$conversationId, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mark messages as read
     */
    public function markAsRead($conversationId, $userType, $userId)
    {
        try {
            $this->pdo->beginTransaction();

            // Mark messages as read
            $stmt = $this->pdo->prepare("
                UPDATE messages 
                SET is_read = TRUE, read_at = NOW()
                WHERE conversation_id = ? 
                AND sender_type != ?
                AND is_read = FALSE
            ");
            $stmt->execute([$conversationId, $userType]);

            // Reset unread count
            $unreadColumn = $userType === 'company' ? 'company_unread_count' : 'driver_unread_count';
            $stmt = $this->pdo->prepare("
                UPDATE conversations 
                SET $unreadColumn = 0
                WHERE id = ?
            ");
            $stmt->execute([$conversationId]);

            $this->pdo->commit();
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Get conversations for a user
     */
    public function getConversations($userType, $userId, $status = 'active', $limit = 20, $offset = 0)
    {
        $userColumn = $userType === 'company' ? 'company_id' : 'driver_id';
        $unreadColumn = $userType === 'company' ? 'company_unread_count' : 'driver_unread_count';

        $stmt = $this->pdo->prepare("
            SELECT c.*,
                   comp.company_name,
                   CONCAT(d.first_name, ' ', d.last_name) as driver_name,
                   j.title as job_title,
                   (SELECT message FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message
            FROM conversations c
            JOIN companies comp ON c.company_id = comp.id
            JOIN drivers d ON c.driver_id = d.id
            LEFT JOIN job_listings j ON c.job_id = j.id
            WHERE c.$userColumn = ? AND c.status = ?
            ORDER BY c.last_message_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $status, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get unread count for a user
     */
    public function getUnreadCount($userType, $userId)
    {
        $userColumn = $userType === 'company' ? 'company_id' : 'driver_id';
        $unreadColumn = $userType === 'company' ? 'company_unread_count' : 'driver_unread_count';

        $stmt = $this->pdo->prepare("
            SELECT SUM($unreadColumn) as total_unread
            FROM conversations
            WHERE $userColumn = ? AND status = 'active'
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_unread'] ?? 0;
    }

    /**
     * Create a notification
     */
    public function createNotification($userType, $userId, $type, $title, $message, $data = null)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO notifications (user_type, user_id, type, title, message, data)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userType,
            $userId,
            $type,
            $title,
            $message,
            $data ? json_encode($data) : null
        ]);
        return $this->pdo->lastInsertId();
    }

    /**
     * Get notifications for a user
     */
    public function getNotifications($userType, $userId, $unreadOnly = false, $limit = 20)
    {
        $sql = "
            SELECT * FROM notifications
            WHERE user_type = ? AND user_id = ?
        ";

        if ($unreadOnly) {
            $sql .= " AND is_read = FALSE";
        }

        $sql .= " ORDER BY created_at DESC LIMIT ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userType, $userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead($notificationId)
    {
        $stmt = $this->pdo->prepare("
            UPDATE notifications 
            SET is_read = TRUE, read_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$notificationId]);
    }

    /**
     * Get message templates for a company
     */
    public function getMessageTemplates($companyId, $category = null)
    {
        $sql = "SELECT * FROM message_templates WHERE company_id = ?";
        $params = [$companyId];

        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        $sql .= " ORDER BY usage_count DESC, created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Use a message template
     */
    public function useTemplate($templateId)
    {
        $stmt = $this->pdo->prepare("
            UPDATE message_templates 
            SET usage_count = usage_count + 1
            WHERE id = ?
        ");
        $stmt->execute([$templateId]);
    }
}
