<?php
// Create drivers/conversation.php
$conversationContent = '<?php
require_once __DIR__ . \'/../../src/bootstrap.php\';

use Drivejob\Core\Session;
use Drivejob\Core\Database;

// Check if user is logged in and is a driver
if (!Session::has(\'user_id\') || Session::get(\'user_role\') !== \'driver\') {
    header(\'Location: \' . BASE_URL . \'login.php\');
    exit();
}

$conversationId = isset($_GET[\'id\']) ? (int)$_GET[\'id\'] : 0;
if (!$conversationId) {
    header(\'Location: \' . BASE_URL . \'drivers/messages\');
    exit();
}

$driverId = Session::get(\'user_id\');
$pdo = Database::getInstance()->getConnection();

// Verify this conversation belongs to this driver
$stmt = $pdo->prepare("SELECT * FROM conversations WHERE id = ? AND driver_id = ?");
$stmt->execute([$conversationId, $driverId]);
$conversation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conversation) {
    header(\'Location: \' . BASE_URL . \'drivers/messages\');
    exit();
}

// Get messages
$stmt = $pdo->prepare("
    SELECT m.*, 
           CASE 
               WHEN m.sender_type = \'driver\' THEN CONCAT(d.first_name, \' \', d.last_name)
               WHEN m.sender_type = \'company\' THEN c.company_name
           END as sender_name
    FROM messages m
    LEFT JOIN drivers d ON m.sender_id = d.id AND m.sender_type = \'driver\'
    LEFT JOIN companies c ON m.sender_id = c.id AND m.sender_type = \'company\'
    WHERE m.conversation_id = ?
    ORDER BY m.created_at ASC
");
$stmt->execute([$conversationId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark messages as read
$stmt = $pdo->prepare("
    UPDATE messages 
    SET is_read = 1 
    WHERE conversation_id = ? 
    AND sender_type = \'company\' 
    AND is_read = 0
");
$stmt->execute([$conversationId]);

// Get company and job info
$stmt = $pdo->prepare("
    SELECT c.*, comp.company_name, comp.company_logo, j.title as job_title
    FROM conversations c
    JOIN companies comp ON c.company_id = comp.id
    JOIN job_listings j ON c.job_id = j.id
    WHERE c.id = ?
");
$stmt->execute([$conversationId]);
$conversationInfo = $stmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = $conversationInfo[\'subject\'];
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - DriveJob</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/styles.css">
    <style>
        .message-item {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .message-item.company {
            background: #e3f2fd;
            margin-left: 20px;
        }
        .message-item.driver {
            background: #f3e5f5;
            margin-right: 20px;
        }
        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
            color: #666;
        }
        .message-content {
            white-space: pre-wrap;
        }
        .reply-form {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <?php include ROOT_DIR . \'/src/Views/partials/header.php\'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><?php echo htmlspecialchars($conversationInfo[\'subject\']); ?></h2>
                        <p class="text-muted mb-0">
                            <strong><?php echo htmlspecialchars($conversationInfo[\'company_name\']); ?></strong> - 
                            <?php echo htmlspecialchars($conversationInfo[\'job_title\']); ?>
                        </p>
                    </div>
                    <a href="<?php echo BASE_URL; ?>drivers/messages" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Πίσω στα Μηνύματα
                    </a>
                </div>

                <div class="messages-container">
                    <?php foreach ($messages as $message): ?>
                        <div class="message-item <?php echo $message[\'sender_type\']; ?>">
                            <div class="message-header">
                                <strong><?php echo htmlspecialchars($message[\'sender_name\']); ?></strong>
                                <span><?php echo date(\'d/m/Y H:i\', strtotime($message[\'created_at\'])); ?></span>
                            </div>
                            <div class="message-content">
                                <?php echo nl2br(htmlspecialchars($message[\'message\'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($conversation[\'status\'] === \'active\'): ?>
                    <div class="reply-form">
                        <h4>Απάντηση</h4>
                        <form action="<?php echo BASE_URL; ?>api/messaging/send-message.php" method="POST">
                            <input type="hidden" name="conversation_id" value="<?php echo $conversationId; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[\'csrf_token\'] ?? \'\'; ?>">
                            <div class="mb-3">
                                <textarea name="message" class="form-control" rows="4" required 
                                    placeholder="Γράψτε το μήνυμά σας εδώ..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Αποστολή Μηνύματος
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Αυτή η συνομιλία έχει κλείσει.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include ROOT_DIR . \'/src/Views/partials/footer.php\'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';

$driverConvPath = __DIR__ . '/drivers/conversation.php';
if (!file_exists($driverConvPath)) {
    file_put_contents($driverConvPath, $conversationContent);
    echo "<h1>✅ Δημιουργήθηκε το drivers/conversation.php</h1>";
} else {
    echo "<h1>✅ Το drivers/conversation.php υπάρχει ήδη</h1>";
}

echo "<p>Τώρα μπορείτε να:</p>";
echo "<ul>";
echo "<li>Login ως driver</li>";
echo "<li>Πηγαίνετε στο <a href='http://localhost/drivejob/public/drivers/messages'>Driver Messages</a></li>";
echo "<li>Κάντε κλικ σε ένα μήνυμα για να το ανοίξετε</li>";
echo "</ul>";
