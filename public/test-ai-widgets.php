<?php

/**
 * Test script για τα AI Matching Widgets
 */

require_once '../src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Core\Session;

// Start session
Session::start();

?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test AI Matching Widgets</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }

        .test-section {
            margin-bottom: 40px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .test-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
        }

        .login-form {
            max-width: 400px;
            margin: 0 auto;
        }

        .widget-preview {
            border: 2px dashed #dee2e6;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1 class="text-center mb-5">
            <i class="fas fa-robot"></i> AI Matching Widgets Test
        </h1>

        <?php if (!Session::has('user_id')): ?>
            <!-- Login Section -->
            <div class="test-section">
                <div class="test-header">
                    <h2>Σύνδεση για Test</h2>
                    <p>Συνδεθείτε ως οδηγός ή εταιρεία για να δείτε τα widgets</p>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h4>Σύνδεση ως Οδηγός</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="login_type" value="driver">
                                    <div class="form-group">
                                        <label>Driver ID για test:</label>
                                        <input type="number" name="driver_id" class="form-control" value="26" required>
                                    </div>
                                    <button type="submit" name="login" class="btn btn-primary btn-block">
                                        <i class="fas fa-truck"></i> Σύνδεση ως Οδηγός
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h4>Σύνδεση ως Εταιρεία</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="login_type" value="company">
                                    <div class="form-group">
                                        <label>Company ID για test:</label>
                                        <input type="number" name="company_id" class="form-control" value="1" required>
                                    </div>
                                    <button type="submit" name="login" class="btn btn-success btn-block">
                                        <i class="fas fa-building"></i> Σύνδεση ως Εταιρεία
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Logged In Section -->
            <div class="test-section">
                <div class="test-header">
                    <h2>Συνδεδεμένος ως: <?php echo Session::get('user_role'); ?></h2>
                    <p>User ID: <?php echo Session::get('user_id'); ?></p>
                    <form method="POST" action="" style="display: inline;">
                        <button type="submit" name="logout" class="btn btn-sm btn-danger">
                            <i class="fas fa-sign-out-alt"></i> Αποσύνδεση
                        </button>
                    </form>
                </div>

                <?php if (Session::get('user_role') === 'driver'): ?>
                    <!-- Driver Widget Preview -->
                    <div class="widget-preview">
                        <h3>Driver Matching Widget</h3>
                        <p>Το widget εμφανίζει τις top 5 προτεινόμενες θέσεις εργασίας:</p>
                        <?php include '../src/Views/drivers/partials/matching-widget.php'; ?>
                    </div>

                    <div class="mt-4">
                        <h4>Δοκιμάστε επίσης:</h4>
                        <ul>
                            <li><a href="/drivers/driver-profile" target="_blank">Driver Dashboard με Widget</a></li>
                            <li><a href="/drivers/job-matches" target="_blank">Πλήρης σελίδα προτάσεων</a></li>
                        </ul>
                    </div>
                <?php elseif (Session::get('user_role') === 'company'): ?>
                    <!-- Company Widget Preview -->
                    <div class="widget-preview">
                        <h3>Company Candidates Widget</h3>
                        <p>Το widget εμφανίζει υποψήφιους ανά θέση εργασίας:</p>
                        <?php include '../src/Views/companies/partials/candidates-widget.php'; ?>
                    </div>

                    <div class="mt-4">
                        <h4>Δοκιμάστε επίσης:</h4>
                        <ul>
                            <li><a href="/companies/company-profile" target="_blank">Company Dashboard με Widget</a></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <!-- API Test Section -->
            <div class="test-section">
                <div class="test-header">
                    <h2>API Endpoints Test</h2>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h4>Driver Endpoints:</h4>
                        <ul>
                            <li><a href="/api/matching/driver/matches?limit=5" target="_blank">/api/matching/driver/matches</a></li>
                            <li><a href="/api/matching/calculate?driver_id=26&job_id=2" target="_blank">/api/matching/calculate</a></li>
                            <li><a href="/api/matching/insights?driver_id=26&job_id=2" target="_blank">/api/matching/insights</a></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h4>Company Endpoints:</h4>
                        <ul>
                            <li><a href="/api/matching/job/candidates?job_id=2&limit=10" target="_blank">/api/matching/job/candidates</a></li>
                            <li><a href="/api/company/jobs" target="_blank">/api/company/jobs</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Info Section -->
        <div class="test-section">
            <div class="test-header">
                <h2>Πληροφορίες AI Matching System</h2>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Matching Algorithm</h5>
                            <ul class="list-unstyled">
                                <li>✓ Skills Match: 35%</li>
                                <li>✓ Location Match: 25%</li>
                                <li>✓ Experience Match: 25%</li>
                                <li>✓ Availability Match: 15%</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Features</h5>
                            <ul class="list-unstyled">
                                <li>✓ Real-time matching</li>
                                <li>✓ Score breakdown</li>
                                <li>✓ Match insights</li>
                                <li>✓ Confidence scoring</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">UI Components</h5>
                            <ul class="list-unstyled">
                                <li>✓ Driver widget</li>
                                <li>✓ Company widget</li>
                                <li>✓ Full matches page</li>
                                <li>✓ Modal details</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
// Handle login/logout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $loginType = $_POST['login_type'];

        if ($loginType === 'driver') {
            Session::set('user_id', $_POST['driver_id']);
            Session::set('user_role', 'driver');
            Session::set('user_type', 'drivers');
        } else {
            Session::set('user_id', $_POST['company_id']);
            Session::set('user_role', 'company');
            Session::set('user_type', 'companies');
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($_POST['logout'])) {
        Session::destroy();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>