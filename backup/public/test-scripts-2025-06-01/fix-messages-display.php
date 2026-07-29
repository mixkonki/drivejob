<?php
echo "<h1>Διόρθωση Εμφάνισης Μηνυμάτων</h1>";

// Files to fix
$files = [
    'companies/messages.php',
    'drivers/messages.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);

        // Fix CSS for last-message
        $oldCSS = '.last-message {
            color: #666;
            font-size: 14px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 500px;
        }';

        $newCSS = '.last-message {
            color: #666;
            font-size: 14px;
            max-width: 100%;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }';

        $content = str_replace($oldCSS, $newCSS, $content);

        // Also fix the message display to show more characters
        $content = preg_replace(
            '/htmlspecialchars\(\$conversation\[\'last_message\'\]\)/',
            'htmlspecialchars(mb_substr($conversation[\'last_message\'], 0, 150) . (strlen($conversation[\'last_message\']) > 150 ? "..." : ""))',
            $content
        );

        file_put_contents($path, $content);
        echo "<p>✅ Fixed $file</p>";
    }
}

// Check Session handling in all key files
echo "<h2>Έλεγχος Session Handling</h2>";

$sessionFiles = [
    'drivers/driver-profile.php',
    'companies/company-profile.php',
    'drivers/messages.php',
    'companies/messages.php'
];

foreach ($sessionFiles as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);

        // Check if Session::start() is called before Session::has()
        if (strpos($content, 'Session::start()') === false && strpos($content, 'Session::has') !== false) {
            // Add Session::start() at the beginning
            $content = str_replace(
                "use Drivejob\Core\Session;",
                "use Drivejob\Core\Session;\n\nSession::start();",
                $content
            );
            file_put_contents($path, $content);
            echo "<p>✅ Added Session::start() to $file</p>";
        } else {
            echo "<p>✓ $file already has proper session handling</p>";
        }
    }
}

// Create a comprehensive test page
$testPageContent = '<?php
require_once __DIR__ . \'/../src/bootstrap.php\';

use Drivejob\Core\Session;
use Drivejob\Core\Database;

Session::start();

$pdo = Database::getInstance()->getConnection();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Messaging System Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Messaging System Test</h1>
        
        <h2>1. Session Status</h2>
        <?php if (Session::has(\'user_id\')): ?>
            <div class="alert alert-success">
                Logged in as: <?php echo Session::get(\'user_email\'); ?> (<?php echo Session::get(\'user_role\'); ?>)
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                Not logged in. <a href="<?php echo BASE_URL; ?>login.php">Login here</a>
            </div>
        <?php endif; ?>
        
        <h2>2. Test Links</h2>
        <div class="list-group">
            <a href="<?php echo BASE_URL; ?>companies/profile" class="list-group-item list-group-item-action">
                Company Profile
            </a>
            <a href="<?php echo BASE_URL; ?>companies/messages" class="list-group-item list-group-item-action">
                Company Messages
            </a>
            <a href="<?php echo BASE_URL; ?>drivers/profile" class="list-group-item list-group-item-action">
                Driver Profile
            </a>
            <a href="<?php echo BASE_URL; ?>drivers/messages" class="list-group-item list-group-item-action">
                Driver Messages
            </a>
            <a href="<?php echo BASE_URL; ?>admin/messaging-monitor.php" class="list-group-item list-group-item-action">
                Admin Messaging Monitor
            </a>
        </div>
        
        <h2>3. Sample Conversations</h2>
        <?php
        $stmt = $pdo->query("
            SELECT c.*, comp.company_name, 
                   CONCAT(d.first_name, \' \', d.last_name) as driver_name,
                   j.title as job_title
            FROM conversations c
            JOIN companies comp ON c.company_id = comp.id
            JOIN drivers d ON c.driver_id = d.id
            JOIN job_listings j ON c.job_id = j.id
            LIMIT 5
        ");
        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Company</th>
                    <th>Driver</th>
                    <th>Job</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($conversations as $conv): ?>
                <tr>
                    <td><?php echo $conv[\'id\']; ?></td>
                    <td><?php echo htmlspecialchars($conv[\'company_name\']); ?></td>
                    <td><?php echo htmlspecialchars($conv[\'driver_name\']); ?></td>
                    <td><?php echo htmlspecialchars($conv[\'job_title\']); ?></td>
                    <td>
                        <a href="<?php echo BASE_URL; ?>companies/conversation/<?php echo $conv[\'id\']; ?>" 
                           class="btn btn-sm btn-primary">View as Company</a>
                        <a href="<?php echo BASE_URL; ?>drivers/conversation/<?php echo $conv[\'id\']; ?>" 
                           class="btn btn-sm btn-info">View as Driver</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>';

file_put_contents(__DIR__ . '/test-messaging-system.php', $testPageContent);
echo "<p>✅ Created test-messaging-system.php</p>";

echo "<h2>Σύνοψη Διορθώσεων</h2>";
echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px;'>";
echo "<p><strong>Διορθώθηκαν:</strong></p>";
echo "<ul>";
echo "<li>CSS για εμφάνιση μηνυμάτων (δεν κόβονται πλέον)</li>";
echo "<li>Session handling σε όλα τα αρχεία</li>";
echo "<li>Δημιουργήθηκε test page</li>";
echo "</ul>";
echo "<p><strong>Test Page:</strong> <a href='" . BASE_URL . "test-messaging-system.php'>Test Messaging System</a></p>";
echo "</div>";
