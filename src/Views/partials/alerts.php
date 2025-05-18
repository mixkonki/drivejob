<?php
// Αρχείο για την εμφάνιση μηνυμάτων επιτυχίας, σφάλματος ή προειδοποίησης

use Drivejob\Core\Session;

// Τύποι μηνυμάτων
$alertTypes = [
    'success' => [
        'class' => 'alert-success',
        'icon' => 'fas fa-check-circle'
    ],
    'error' => [
        'class' => 'alert-danger',
        'icon' => 'fas fa-exclamation-circle'
    ],
    'warning' => [
        'class' => 'alert-warning',
        'icon' => 'fas fa-exclamation-triangle'
    ],
    'info' => [
        'class' => 'alert-info',
        'icon' => 'fas fa-info-circle'
    ]
];

// Έλεγχος για μηνύματα στο session
foreach ($alertTypes as $type => $config) {
    $messages = Session::get($type . '_messages', []);

    if (!empty($messages)) {
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        foreach ($messages as $message) {
?>
            <div class="alert <?php echo $config['class']; ?> alert-dismissible fade show" role="alert">
                <i class="<?php echo $config['icon']; ?> mr-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
    <?php
        }

        // Καθαρισμός των μηνυμάτων μετά την εμφάνιση
        Session::remove($type . '_messages');
    }
}

// Έλεγχος για μηνύματα που έχουν περαστεί απευθείας στο template
if (isset($successMessage) && !empty($successMessage)) {
    ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-2"></i>
        <?php echo htmlspecialchars($successMessage); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php
}

if (isset($errorMessage) && !empty($errorMessage)) {
?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?php echo htmlspecialchars($errorMessage); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php
}

if (isset($warningMessage) && !empty($warningMessage)) {
?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <?php echo htmlspecialchars($warningMessage); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php
}

if (isset($infoMessage) && !empty($infoMessage)) {
?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fas fa-info-circle mr-2"></i>
        <?php echo htmlspecialchars($infoMessage); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php
}
?>