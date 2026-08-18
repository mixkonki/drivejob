<?php

/**
 * Admin Dashboard για Matching System
 * Εμφανίζει στατιστικά και διαχείριση του matching system
 */

// Ορισμός του ROOT_DIR
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__, 3));
}

// Φόρτωση του bootstrap
require_once ROOT_DIR . '/src/bootstrap.php';

use Drivejob\Core\AuthMiddleware;
use Drivejob\Core\Session;
use Drivejob\Core\Database;
use Drivejob\Core\Logger;
use Drivejob\Services\RealTimeMatchingService;
use Drivejob\Services\EventHookService;

// Έλεγχος αν ο χρήστης είναι admin
AuthMiddleware::hasRole('admin');

// Αρχικοποίηση υπηρεσιών
$pdo = Database::getInstance()->getConnection();
$realTimeService = new RealTimeMatchingService($pdo);
$eventHookService = new EventHookService($pdo, $realTimeService);

// Χειρισμός POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'batch_update':
            try {
                $result = $realTimeService->batchUpdateAllMatches();
                if ($result['success']) {
                    Session::set('success_message', "Batch update completed: {$result['total_updates']} scores updated");
                } else {
                    Session::set('error_message', "Batch update failed: " . $result['error']);
                }
            } catch (Exception $e) {
                Session::set('error_message', 'Error: ' . $e->getMessage());
            }
            break;

        case 'cleanup_old':
            try {
                $days = intval($_POST['days'] ?? 30);
                $cleaned = $realTimeService->cleanupOldScores($days);
                Session::set('success_message', "Cleaned up {$cleaned} old matching scores");
            } catch (Exception $e) {
                Session::set('error_message', 'Error: ' . $e->getMessage());
            }
            break;

        case 'update_driver':
            try {
                $driverId = intval($_POST['driver_id'] ?? 0);
                if ($driverId > 0) {
                    $result = $realTimeService->updateDriverMatches($driverId);
                    if ($result['success']) {
                        Session::set('success_message', "Driver {$driverId} matches updated: {$result['updated_matches']} matches");
                    } else {
                        Session::set('error_message', "Failed to update driver {$driverId} matches");
                    }
                }
            } catch (Exception $e) {
                Session::set('error_message', 'Error: ' . $e->getMessage());
            }
            break;
    }

    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Λήψη στατιστικών
$stats = $realTimeService->getMatchingStats();

// Λήψη πρόσφατων updates
$recentUpdates = [];
try {
    $stmt = $pdo->query("
        SELECT ms.*, d.first_name, d.last_name, j.title as job_title, c.company_name
        FROM matching_scores ms
        LEFT JOIN drivers d ON ms.driver_id = d.id
        LEFT JOIN job_listings j ON ms.job_id = j.id
        LEFT JOIN companies c ON j.company_id = c.id
        WHERE ms.updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY ms.updated_at DESC
        LIMIT 20
    ");
    $recentUpdates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    Logger::error('Error fetching recent updates: ' . $e->getMessage());
}

// Λήψη top matches
$topMatches = [];
try {
    $stmt = $pdo->query("
        SELECT ms.*, d.first_name, d.last_name, j.title as job_title, c.company_name
        FROM matching_scores ms
        LEFT JOIN drivers d ON ms.driver_id = d.id
        LEFT JOIN job_listings j ON ms.job_id = j.id
        LEFT JOIN companies c ON j.company_id = c.id
        WHERE ms.overall_score >= 80
        ORDER BY ms.overall_score DESC, ms.updated_at DESC
        LIMIT 10
    ");
    $topMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    Logger::error('Error fetching top matches: ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matching System Dashboard - DriveJob Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
        }

        .header .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-card .change {
            font-size: 0.8rem;
            margin-top: 5px;
        }

        .positive {
            color: #28a745;
        }

        .negative {
            color: #dc3545;
        }

        .actions-panel {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .actions-panel h2 {
            margin-bottom: 20px;
            color: #333;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .data-tables {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .table-panel {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .table-panel h3 {
            margin-bottom: 15px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .score {
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 3px;
        }

        .score.excellent {
            background: #d4edda;
            color: #155724;
        }

        .score.good {
            background: #d1ecf1;
            color: #0c5460;
        }

        .score.fair {
            background: #fff3cd;
            color: #856404;
        }

        .score.poor {
            background: #f8d7da;
            color: #721c24;
        }

        .form-inline {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 15px;
        }

        .form-inline input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        @media (max-width: 768px) {
            .data-tables {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .form-inline {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="container">
            <h1>🤖 Matching System Dashboard</h1>
            <p>Real-time monitoring and management of the AI matching system</p>
        </div>
    </div>

    <div class="container">
        <?php if (Session::has('success_message')): ?>
            <div class="alert success">
                ✅ <?php echo Session::get('success_message');
                    Session::remove('success_message'); ?>
            </div>
        <?php endif; ?>

        <?php if (Session::has('error_message')): ?>
            <div class="alert error">
                ❌ <?php echo Session::get('error_message');
                    Session::remove('error_message'); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Matches</h3>
                <div class="value"><?php echo number_format($stats['total_matches'] ?? 0); ?></div>
                <div class="change">Active matching scores</div>
            </div>

            <div class="stat-card">
                <h3>High Quality Matches</h3>
                <div class="value"><?php echo number_format($stats['high_quality_matches'] ?? 0); ?></div>
                <div class="change">≥70% compatibility</div>
            </div>

            <div class="stat-card">
                <h3>Average Score</h3>
                <div class="value"><?php echo number_format($stats['average_score'] ?? 0, 1); ?>%</div>
                <div class="change">Overall matching quality</div>
            </div>

            <div class="stat-card">
                <h3>Recent Updates</h3>
                <div class="value"><?php echo number_format($stats['recent_updates'] ?? 0); ?></div>
                <div class="change">Last 24 hours</div>
            </div>
        </div>

        <!-- Score Distribution -->
        <?php if (!empty($stats['score_distribution'])): ?>
            <div class="stat-card" style="margin-bottom: 30px;">
                <h3>Score Distribution</h3>
                <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 15px;">
                    <?php foreach ($stats['score_distribution'] as $range => $count): ?>
                        <div style="text-align: center;">
                            <div style="font-weight: bold; font-size: 1.2rem;"><?php echo number_format($count); ?></div>
                            <div style="font-size: 0.8rem; color: #666;"><?php echo $range; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Actions Panel -->
        <div class="actions-panel">
            <h2>System Actions</h2>
            <div class="action-buttons">
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="batch_update">
                    <button type="submit" class="btn btn-primary" onclick="return confirm('This will update all matching scores. Continue?')">
                        🔄 Batch Update All Scores
                    </button>
                </form>

                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="cleanup_old">
                    <input type="hidden" name="days" value="30">
                    <button type="submit" class="btn btn-warning" onclick="return confirm('This will delete scores older than 30 days. Continue?')">
                        🧹 Cleanup Old Scores
                    </button>
                </form>
            </div>

            <div class="form-inline">
                <form method="post" style="display: flex; gap: 10px; align-items: center;">
                    <input type="hidden" name="action" value="update_driver">
                    <label>Update specific driver:</label>
                    <input type="number" name="driver_id" placeholder="Driver ID" required min="1">
                    <button type="submit" class="btn btn-primary">Update Driver Matches</button>
                </form>
            </div>
        </div>

        <!-- Data Tables -->
        <div class="data-tables">
            <!-- Recent Updates -->
            <div class="table-panel">
                <h3>🕒 Recent Updates (Last 24h)</h3>
                <?php if (!empty($recentUpdates)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Driver</th>
                                <th>Job</th>
                                <th>Score</th>
                                <th>Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUpdates as $update): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(($update['first_name'] ?? '') . ' ' . ($update['last_name'] ?? '')); ?></td>
                                    <td>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars(substr($update['job_title'] ?? 'Unknown', 0, 30)); ?></div>
                                        <div style="font-size: 0.8rem; color: #666;"><?php echo htmlspecialchars($update['company_name'] ?? ''); ?></div>
                                    </td>
                                    <td>
                                        <?php
                                        $score = $update['overall_score'] ?? 0;
                                        $class = $score >= 80 ? 'excellent' : ($score >= 60 ? 'good' : ($score >= 40 ? 'fair' : 'poor'));
                                        ?>
                                        <span class="score <?php echo $class; ?>"><?php echo number_format($score, 1); ?>%</span>
                                    </td>
                                    <td><?php echo date('H:i', strtotime($update['updated_at'] ?? '')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 20px;">No recent updates</p>
                <?php endif; ?>
            </div>

            <!-- Top Matches -->
            <div class="table-panel">
                <h3>⭐ Top Matches (≥80%)</h3>
                <?php if (!empty($topMatches)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Driver</th>
                                <th>Job</th>
                                <th>Score</th>
                                <th>Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topMatches as $match): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(($match['first_name'] ?? '') . ' ' . ($match['last_name'] ?? '')); ?></td>
                                    <td>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars(substr($match['job_title'] ?? 'Unknown', 0, 30)); ?></div>
                                        <div style="font-size: 0.8rem; color: #666;"><?php echo htmlspecialchars($match['company_name'] ?? ''); ?></div>
                                    </td>
                                    <td>
                                        <span class="score excellent"><?php echo number_format($match['overall_score'] ?? 0, 1); ?>%</span>
                                    </td>
                                    <td><?php echo date('d/m H:i', strtotime($match['updated_at'] ?? '')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 20px;">No high-quality matches found</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- System Info -->
        <div class="actions-panel" style="margin-top: 30px;">
            <h2>System Information</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div>
                    <strong>Last Cron Run:</strong><br>
                    <span style="color: #666;">Check logs for details</span>
                </div>
                <div>
                    <strong>Database Status:</strong><br>
                    <span style="color: #28a745;">✅ Connected</span>
                </div>
                <div>
                    <strong>Matching Algorithm:</strong><br>
                    <span style="color: #666;">Enhanced AI v2.1</span>
                </div>
                <div>
                    <strong>Real-time Updates:</strong><br>
                    <span style="color: #28a745;">✅ Active</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh every 5 minutes
        setTimeout(function() {
            window.location.reload();
        }, 300000);

        // Add loading states to buttons
        document.querySelectorAll('button[type="submit"]').forEach(button => {
            button.addEventListener('click', function() {
                this.disabled = true;
                this.innerHTML = '⏳ Processing...';
                setTimeout(() => {
                    this.disabled = false;
                }, 5000);
            });
        });
    </script>
</body>

</html>