<?php
// Φόρτωση του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1 class="page-title">Ιστορικό Περιστατικών</h1>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success_message']; ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error_message']; ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <div class="action-buttons mb-4">
                <a href="<?php echo BASE_URL; ?>drivers/report-incident" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Αναφορά Νέου Περιστατικού
                </a>
                <a href="<?php echo BASE_URL; ?>drivers/profile" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Επιστροφή στο Προφίλ
                </a>
            </div>

            <?php if (empty($incidents)): ?>
                <div class="alert alert-info">
                    Δεν έχετε καταχωρήσει κανένα περιστατικό ακόμα.
                </div>
            <?php else: ?>
                <div class="incidents-list">
                    <?php foreach ($incidents as $incident): ?>
                        <div class="incident-card">
                            <div class="incident-header">
                                <h3 class="incident-type">
                                    <?php echo htmlspecialchars($incident['incident_type']); ?>
                                </h3>
                                <span class="incident-date">
                                    <?php echo date('d/m/Y', strtotime($incident['incident_date'])); ?>
                                </span>
                                <span class="incident-severity severity-<?php echo strtolower($incident['severity']); ?>">
                                    <?php
                                    $severityLabels = [
                                        'low' => 'Χαμηλή',
                                        'medium' => 'Μέτρια',
                                        'high' => 'Υψηλή',
                                        'critical' => 'Κρίσιμη'
                                    ];
                                    echo $severityLabels[$incident['severity']] ?? $incident['severity'];
                                    ?>
                                </span>
                            </div>
                            <div class="incident-body">
                                <p class="incident-description">
                                    <?php echo nl2br(htmlspecialchars($incident['description'])); ?>
                                </p>
                                <?php if (!empty($incident['location'])): ?>
                                    <p class="incident-location">
                                        <strong>Τοποθεσία:</strong> <?php echo htmlspecialchars($incident['location']); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($incident['file_path'])): ?>
                                    <p class="incident-file">
                                        <a href="<?php echo BASE_URL . $incident['file_path']; ?>" target="_blank" class="btn btn-sm btn-info">
                                            <i class="fas fa-file"></i> Προβολή Αρχείου
                                        </a>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="incident-footer">
                                <span class="incident-created">
                                    Καταχωρήθηκε: <?php echo date('d/m/Y H:i', strtotime($incident['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .incidents-list {
        margin-top: 20px;
    }

    .incident-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
        padding: 15px;
        border-left: 5px solid #007bff;
    }

    .incident-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }

    .incident-type {
        font-size: 18px;
        margin: 0;
        color: #333;
    }

    .incident-date {
        font-size: 14px;
        color: #666;
    }

    .incident-severity {
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
        color: white;
    }

    .severity-low {
        background-color: #28a745;
    }

    .severity-medium {
        background-color: #ffc107;
        color: #333;
    }

    .severity-high {
        background-color: #fd7e14;
    }

    .severity-critical {
        background-color: #dc3545;
    }

    .incident-body {
        margin-bottom: 15px;
    }

    .incident-description {
        margin-bottom: 10px;
        line-height: 1.5;
    }

    .incident-location {
        font-size: 14px;
        color: #666;
    }

    .incident-footer {
        font-size: 12px;
        color: #999;
        text-align: right;
        border-top: 1px solid #eee;
        padding-top: 10px;
    }

    .action-buttons {
        margin-bottom: 20px;
    }

    .action-buttons .btn {
        margin-right: 10px;
    }
</style>

<?php
// Φόρτωση του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>