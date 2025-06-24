<?php
// Fix authentication and messages issues

echo "<h1>Διόρθωση Authentication & Messages</h1>";

// 1. Fix driver profile authentication
$driverProfilePath = __DIR__ . '/drivers/driver-profile.php';
if (file_exists($driverProfilePath)) {
    $content = file_get_contents($driverProfilePath);

    // Replace auth/login with login.php
    $content = str_replace('auth/login', 'login.php', $content);

    // Make sure it uses Session class properly
    $content = str_replace(
        "if (!Session::has('user_id') || Session::get('user_role') !== 'driver') {",
        "Session::start();\nif (!Session::has('user_id') || Session::get('user_role') !== 'driver') {",
        $content
    );

    file_put_contents($driverProfilePath, $content);
    echo "<p>✅ Fixed driver profile authentication</p>";
}

// 2. Check and fix messages onclick
$messagesFiles = [
    'companies/messages.php' => 'companies',
    'drivers/messages.php' => 'drivers'
];

foreach ($messagesFiles as $file => $type) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);

        // Check if onclick is properly formatted
        if (strpos($content, 'onclick="window.location.href=') !== false) {
            echo "<p>✅ $file has correct onclick</p>";
        } else {
            // Fix onclick
            $content = str_replace(
                "onclick='window.location.href='",
                "onclick=\"window.location.href='",
                $content
            );
            $content = str_replace(
                "'>",
                "'\"",
                $content
            );
            file_put_contents($path, $content);
            echo "<p>✅ Fixed onclick in $file</p>";
        }
    }
}

// 3. Create a simple test for messages
echo "<h2>Test Message Display</h2>";

require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;

try {
    $pdo = Database::getInstance()->getConnection();

    // Get a sample message
    $stmt = $pdo->query("
        SELECT m.*, c.subject 
        FROM messages m
        JOIN conversations c ON m.conversation_id = c.id
        LIMIT 1
    ");
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($message) {
        echo "<div style='border: 1px solid #ccc; padding: 10px;'>";
        echo "<h3>Sample Message</h3>";
        echo "<p><strong>Subject:</strong> " . htmlspecialchars($message['subject']) . "</p>";
        echo "<p><strong>Full Message:</strong></p>";
        echo "<div style='background: #f0f0f0; padding: 10px;'>";
        echo nl2br(htmlspecialchars($message['message']));
        echo "</div>";
        echo "<p><strong>Message Length:</strong> " . strlen($message['message']) . " characters</p>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>";
}

// 4. Create admin monitoring page
$adminMonitoringContent = '<?php
require_once __DIR__ . \'/../../src/bootstrap.php\';

use Drivejob\Core\Session;
use Drivejob\Core\Database;
use Drivejob\Middleware\AdminMiddleware;

Session::start();

// Check admin access
$adminMiddleware = new AdminMiddleware();
$adminMiddleware->handle();

$pdo = Database::getInstance()->getConnection();

// Get messaging statistics
$stats = [];

// Total conversations
$stmt = $pdo->query("SELECT COUNT(*) FROM conversations");
$stats[\'total_conversations\'] = $stmt->fetchColumn();

// Total messages
$stmt = $pdo->query("SELECT COUNT(*) FROM messages");
$stats[\'total_messages\'] = $stmt->fetchColumn();

// Unread messages
$stmt = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0");
$stats[\'unread_messages\'] = $stmt->fetchColumn();

// Recent conversations
$stmt = $pdo->query("
    SELECT c.*, comp.company_name, 
           CONCAT(d.first_name, \' \', d.last_name) as driver_name,
           j.title as job_title,
           (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id) as message_count
    FROM conversations c
    JOIN companies comp ON c.company_id = comp.id
    JOIN drivers d ON c.driver_id = d.id
    JOIN job_listings j ON c.job_id = j.id
    ORDER BY c.updated_at DESC
    LIMIT 10
");
$recentConversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = \'Messaging Monitor\';
include ROOT_DIR . \'/src/Views/admin/header.php\';
?>

<div class="container-fluid">
    <h1>Messaging System Monitor</h1>
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Conversations</h5>
                    <h2><?php echo $stats[\'total_conversations\']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Messages</h5>
                    <h2><?php echo $stats[\'total_messages\']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Unread Messages</h5>
                    <h2><?php echo $stats[\'unread_messages\']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <h2>Recent Conversations</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Company</th>
                <th>Driver</th>
                <th>Job</th>
                <th>Messages</th>
                <th>Status</th>
                <th>Updated</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentConversations as $conv): ?>
            <tr>
                <td><?php echo $conv[\'id\']; ?></td>
                <td><?php echo htmlspecialchars($conv[\'company_name\']); ?></td>
                <td><?php echo htmlspecialchars($conv[\'driver_name\']); ?></td>
                <td><?php echo htmlspecialchars($conv[\'job_title\']); ?></td>
                <td><?php echo $conv[\'message_count\']; ?></td>
                <td>
                    <span class="badge badge-<?php echo $conv[\'status\'] === \'active\' ? \'success\' : \'secondary\'; ?>">
                        <?php echo $conv[\'status\']; ?>
                    </span>
                </td>
                <td><?php echo date(\'d/m/Y H:i\', strtotime($conv[\'updated_at\'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include ROOT_DIR . \'/src/Views/admin/footer.php\'; ?>';

// Create admin messaging monitor
$adminPath = __DIR__ . '/admin/messaging-monitor.php';
if (!file_exists($adminPath)) {
    file_put_contents($adminPath, $adminMonitoringContent);
    echo "<p>✅ Created admin messaging monitor</p>";
}

echo "<h2>Σύνοψη Διορθώσεων</h2>";
echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px;'>";
echo "<p>Διορθώθηκαν:</p>";
echo "<ul>";
echo "<li>Driver profile authentication (auth/login → login.php)</li>";
echo "<li>Messages onclick handlers</li>";
echo "<li>Admin messaging monitor created</li>";
echo "</ul>";
echo "<p><strong>Επόμενα βήματα:</strong></p>";
echo "<ol>";
echo "<li>Login ως driver ή company</li>";
echo "<li>Δοκιμάστε να κάνετε click σε ένα μήνυμα</li>";
echo "<li>Admin: <a href='" . BASE_URL . "admin/messaging-monitor.php'>Messaging Monitor</a></li>";
echo "</ol>";
echo "</div>";
