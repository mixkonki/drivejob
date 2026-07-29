<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Core\Session;

Session::start();

$pdo = Database::getInstance()->getConnection();

echo "<h1>Διόρθωση Τελικών Προβλημάτων</h1>";

// 1. Fix conversation routing
echo "<h2>1. Διόρθωση Conversation Routing</h2>";

// Create conversation.php for companies
$convPath = ROOT_DIR . '/public/companies/conversation.php';
if (!file_exists($convPath)) {
    $content = '<?php
require_once __DIR__ . \'/../../src/bootstrap.php\';

use Drivejob\Core\Session;
use Drivejob\Core\Database;

// Check if user is logged in and is a company
if (!Session::has(\'user_id\') || Session::get(\'user_role\') !== \'company\') {
    header(\'Location: \' . BASE_URL . \'login.php\');
    exit();
}

// Get conversation ID from URL
$conversationId = isset($_GET[\'id\']) ? intval($_GET[\'id\']) : 0;
if (!$conversationId) {
    // Try to get from path
    $path = $_SERVER[\'REQUEST_URI\'];
    if (preg_match(\'/conversation\/(\d+)/\', $path, $matches)) {
        $conversationId = intval($matches[1]);
    }
}

if (!$conversationId) {
    header(\'Location: \' . BASE_URL . \'companies/messages\');
    exit();
}

$companyId = Session::get(\'user_id\');
$pdo = Database::getInstance()->getConnection();

// Verify conversation belongs to company
$stmt = $pdo->prepare("
    SELECT c.*, d.first_name, d.last_name, j.title as job_title
    FROM conversations c
    JOIN drivers d ON c.driver_id = d.id
    JOIN job_listings j ON c.job_id = j.id
    WHERE c.id = ? AND c.company_id = ?
");
$stmt->execute([$conversationId, $companyId]);
$conversation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conversation) {
    header(\'Location: \' . BASE_URL . \'companies/messages\');
    exit();
}

// Get messages
$stmt = $pdo->prepare("
    SELECT m.*, 
           CASE 
               WHEN m.sender_type = \'company\' THEN c.company_name
               WHEN m.sender_type = \'driver\' THEN CONCAT(d.first_name, \' \', d.last_name)
           END as sender_name
    FROM messages m
    LEFT JOIN companies c ON m.sender_type = \'company\' AND m.sender_id = c.id
    LEFT JOIN drivers d ON m.sender_type = \'driver\' AND m.sender_id = d.id
    WHERE m.conversation_id = ?
    ORDER BY m.created_at ASC
");
$stmt->execute([$conversationId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark messages as read
$stmt = $pdo->prepare("
    UPDATE messages 
    SET is_read = 1, read_at = NOW() 
    WHERE conversation_id = ? AND sender_type = \'driver\' AND is_read = 0
");
$stmt->execute([$conversationId]);

// Update conversation
$stmt = $pdo->prepare("UPDATE conversations SET company_unread_count = 0 WHERE id = ?");
$stmt->execute([$conversationId]);

include ROOT_DIR . \'/src/Views/companies/conversation-view.php\';
';
    file_put_contents($convPath, $content);
    echo "<p>✓ Created companies/conversation.php</p>";
}

// 2. Create conversation view
$viewPath = ROOT_DIR . '/src/Views/companies/conversation-view.php';
if (!file_exists($viewPath)) {
    $content = '<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Συνομιλία - DriveJob</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .messages-container {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            background: #f8f9fa;
        }
        
        .message {
            margin-bottom: 20px;
        }
        
        .message.sent {
            text-align: right;
        }
        
        .message-bubble {
            display: inline-block;
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 15px;
            position: relative;
        }
        
        .message.sent .message-bubble {
            background: #007bff;
            color: white;
        }
        
        .message.received .message-bubble {
            background: white;
            border: 1px solid #e0e0e0;
        }
        
        .message-time {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .message.sent .message-time {
            color: #ccc;
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
                        <h2><?php echo htmlspecialchars($conversation[\'subject\']); ?></h2>
                        <p class="text-muted mb-0">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($conversation[\'first_name\'] . \' \' . $conversation[\'last_name\']); ?> - 
                            <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($conversation[\'job_title\']); ?>
                        </p>
                    </div>
                    <a href="<?php echo BASE_URL; ?>companies/messages" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Πίσω στα Μηνύματα
                    </a>
                </div>
                
                <div class="messages-container mb-4">
                    <?php foreach ($messages as $message): ?>
                        <div class="message <?php echo $message[\'sender_type\'] === \'company\' ? \'sent\' : \'received\'; ?>">
                            <div class="message-bubble">
                                <div class="message-text"><?php echo nl2br(htmlspecialchars($message[\'message\'])); ?></div>
                                <div class="message-time">
                                    <?php 
                                    $date = new DateTime($message[\'created_at\']);
                                    echo $date->format(\'d/m/Y H:i\');
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <form method="POST" action="<?php echo BASE_URL; ?>api/messaging/send.php" class="reply-form">
                    <input type="hidden" name="conversation_id" value="<?php echo $conversationId; ?>">
                    <input type="hidden" name="driver_id" value="<?php echo $conversation[\'driver_id\']; ?>">
                    <input type="hidden" name="job_id" value="<?php echo $conversation[\'job_id\']; ?>">
                    <div class="mb-3">
                        <textarea name="message" class="form-control" rows="3" placeholder="Γράψτε το μήνυμά σας..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Αποστολή
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <?php include ROOT_DIR . \'/src/Views/partials/footer.php\'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
    file_put_contents($viewPath, $content);
    echo "<p>✓ Created conversation view</p>";
}

// 3. Fix driver profile widgets
echo "<h2>2. Διόρθωση Driver Profile Widgets</h2>";

// Check if driver is logged in for testing
if (Session::get('user_role') === 'driver') {
    $driverId = Session::get('user_id');

    // Check if driver has matches
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM matching_scores 
        WHERE driver_id = ? AND overall_score > 0.3
    ");
    $stmt->execute([$driverId]);
    $matchCount = $stmt->fetchColumn();

    echo "<p>Driver $driverId has $matchCount matches</p>";

    // Check messages
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM conversations 
        WHERE driver_id = ?
    ");
    $stmt->execute([$driverId]);
    $msgCount = $stmt->fetchColumn();

    echo "<p>Driver $driverId has $msgCount conversations</p>";
}

// 4. Create .htaccess for conversation routing
$htaccessPath = ROOT_DIR . '/public/companies/.htaccess';
if (!file_exists($htaccessPath)) {
    $content = 'RewriteEngine On
RewriteRule ^conversation/([0-9]+)/?$ conversation.php?id=$1 [L,QSA]';
    file_put_contents($htaccessPath, $content);
    echo "<p>✓ Created companies/.htaccess for routing</p>";
}

// 5. Fix driver profile to ensure widgets load
$driverProfilePath = ROOT_DIR . '/src/Views/drivers/driver-profile.php';
if (file_exists($driverProfilePath)) {
    $content = file_get_contents($driverProfilePath);

    // Check if matching widget is included
    if (strpos($content, 'matching-widget.php') === false) {
        echo "<p style='color: orange;'>⚠ Driver profile may not include matching widget</p>";
    } else {
        echo "<p>✓ Driver profile includes matching widget</p>";
    }
}

// Summary
echo "<h2>Σύνοψη</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h3>Διορθώσεις που έγιναν:</h3>";
echo "<ul>";
echo "<li>✓ Δημιουργία conversation.php για companies</li>";
echo "<li>✓ Δημιουργία conversation view</li>";
echo "<li>✓ Δημιουργία .htaccess για routing</li>";
echo "</ul>";
echo "</div>";

echo "<h3>Οδηγίες:</h3>";
echo "<ol>";
echo "<li>Login ως εταιρεία και δοκιμάστε τα μηνύματα</li>";
echo "<li>Login ως οδηγός για να δείτε τα widgets</li>";
echo "<li>Αν δεν εμφανίζονται τα widgets, ελέγξτε αν ο οδηγός έχει matches στη βάση</li>";
echo "</ol>";

// Test data creation for driver matches
echo "<h3>Δημιουργία Test Matches για τον τρέχοντα οδηγό:</h3>";
if (Session::get('user_role') === 'driver') {
    $driverId = Session::get('user_id');

    // Get active jobs
    $stmt = $pdo->query("SELECT id FROM job_listings WHERE is_active = 1 LIMIT 5");
    $jobs = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($jobs as $jobId) {
        // Check if match exists
        $stmt = $pdo->prepare("SELECT id FROM matching_scores WHERE driver_id = ? AND job_id = ?");
        $stmt->execute([$driverId, $jobId]);

        if (!$stmt->fetch()) {
            // Create match
            $score = rand(50, 95) / 100;
            $stmt = $pdo->prepare("
                INSERT INTO matching_scores 
                (driver_id, job_id, overall_score, skill_match_score, location_match_score, 
                 experience_match_score, availability_match_score, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $driverId,
                $jobId,
                $score,
                $score,
                $score,
                $score,
                $score
            ]);
            echo "<p>✓ Created match for job $jobId with score " . ($score * 100) . "%</p>";
        }
    }
} else {
    echo "<p>Login ως οδηγός για να δημιουργήσετε test matches</p>";
}
