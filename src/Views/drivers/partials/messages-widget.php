<?php
// Messages Widget for Driver Dashboard
use Drivejob\Core\Database;
use Drivejob\Core\Session;

$pdo = Database::getInstance()->getConnection();
$driverId = Session::get('user_id');

// Get unread messages count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as unread_count
    FROM messages m
    JOIN conversations c ON m.conversation_id = c.id
    WHERE c.driver_id = ? 
    AND m.sender_type = 'company' 
    AND m.is_read = 0
");
$stmt->execute([$driverId]);
$unreadCount = $stmt->fetchColumn();

// Get recent conversations
$stmt = $pdo->prepare("
    SELECT 
        c.id,
        c.subject,
        c.updated_at,
        comp.company_name,
        j.title as job_title,
        (SELECT message FROM messages 
         WHERE conversation_id = c.id 
         ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT COUNT(*) FROM messages 
         WHERE conversation_id = c.id 
         AND sender_type = 'company' 
         AND is_read = 0) as unread_messages
    FROM conversations c
    JOIN companies comp ON c.company_id = comp.id
    JOIN job_listings j ON c.job_id = j.id
    WHERE c.driver_id = ?
    ORDER BY c.updated_at DESC
    LIMIT 3
");
$stmt->execute([$driverId]);
$recentConversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="profile-section messages-widget">
    <h3><i class="fas fa-envelope"></i> Μηνύματα</h3>

    <?php if ($unreadCount > 0): ?>
        <div class="unread-notification">
            <span class="badge"><?php echo $unreadCount; ?></span> νέα μηνύματα
        </div>
    <?php endif; ?>

    <?php if (!empty($recentConversations)): ?>
        <div class="recent-messages">
            <?php foreach ($recentConversations as $conv): ?>
                <div class="message-item <?php echo $conv['unread_messages'] > 0 ? 'unread' : ''; ?>">
                    <div class="message-header">
                        <strong><?php echo htmlspecialchars($conv['company_name']); ?></strong>
                        <?php if ($conv['unread_messages'] > 0): ?>
                            <span class="unread-badge"><?php echo $conv['unread_messages']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="message-subject">
                        <?php echo htmlspecialchars($conv['subject']); ?>
                    </div>
                    <div class="message-preview">
                        <?php echo htmlspecialchars(substr($conv['last_message'] ?? '', 0, 50)) . '...'; ?>
                    </div>
                    <div class="message-time">
                        <?php
                        $date = new DateTime($conv['updated_at']);
                        echo $date->format('d/m/Y H:i');
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="widget-actions">
            <a href="<?php echo BASE_URL; ?>drivers/messages" class="btn btn-primary btn-block">
                Προβολή όλων των μηνυμάτων
            </a>
        </div>
    <?php else: ?>
        <p class="no-messages">Δεν έχετε μηνύματα</p>
        <p class="text-muted">Όταν οι εταιρείες επικοινωνήσουν μαζί σας, τα μηνύματα θα εμφανιστούν εδώ.</p>
    <?php endif; ?>
</section>

<style>
    .messages-widget {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .messages-widget h3 {
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .unread-notification {
        background: #dc3545;
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        display: inline-block;
        margin-bottom: 15px;
        font-size: 14px;
    }

    .unread-notification .badge {
        font-weight: bold;
    }

    .recent-messages {
        margin-bottom: 15px;
    }

    .message-item {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 12px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .message-item:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .message-item.unread {
        background: #f0f8ff;
        border-color: #007bff;
    }

    .message-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 5px;
    }

    .unread-badge {
        background: #dc3545;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
    }

    .message-subject {
        font-weight: 500;
        margin-bottom: 5px;
        color: #333;
    }

    .message-preview {
        font-size: 14px;
        color: #666;
        margin-bottom: 5px;
    }

    .message-time {
        font-size: 12px;
        color: #999;
    }

    .no-messages {
        text-align: center;
        color: #666;
        margin: 20px 0;
    }

    .widget-actions {
        margin-top: 15px;
    }

    .btn-block {
        width: 100%;
        display: block;
        text-align: center;
    }
</style>