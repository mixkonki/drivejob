<?php

// Αρχικοποίηση της εφαρμογής
$container = require_once __DIR__ . '/../../src/bootstrap.php';

// Λήψη του PDO από το container
$pdo = $container->get('pdo');

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι οδηγός
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Λήψη του ID του οδηγού
$driverId = $_SESSION['user_id'];

// Δημιουργία του service
$driverProfileService = new \Drivejob\Services\DriverProfileService($pdo);

// Λήψη του προφίλ του οδηγού
$driverProfile = $driverProfileService->getDriverProfile($driverId);

// Λήψη των δεδομένων προϋπηρεσίας
$driverVehicleExperience = $driverProfile['vehicle_experience'] ?? [];

// Υπολογισμός προϋπηρεσίας για εμπορευματικές και επιβατικές μεταφορές
$freightYears = 0;
$freightMonths = 0;
$freightDays = 0;
$passengerYears = 0;
$passengerMonths = 0;
$passengerDays = 0;

if (!empty($driverVehicleExperience)) {
    foreach ($driverVehicleExperience as $exp) {
        if (isset($exp['transport_type']) && $exp['transport_type'] === 'freight') {
            $freightYears += isset($exp['years']) ? intval($exp['years']) : 0;
            $freightMonths += isset($exp['months']) ? intval($exp['months']) : 0;
            $freightDays += isset($exp['days']) ? intval($exp['days']) : 0;
        } else if (isset($exp['transport_type']) && $exp['transport_type'] === 'passenger') {
            $passengerYears += isset($exp['years']) ? intval($exp['years']) : 0;
            $passengerMonths += isset($exp['months']) ? intval($exp['months']) : 0;
            $passengerDays += isset($exp['days']) ? intval($exp['days']) : 0;
        }
    }

    // Κανονικοποίηση των μηνών και ημερών
    $freightMonths += floor($freightDays / 30);
    $freightDays = $freightDays % 30;
    $freightYears += floor($freightMonths / 12);
    $freightMonths = $freightMonths % 12;

    $passengerMonths += floor($passengerDays / 30);
    $passengerDays = $passengerDays % 30;
    $passengerYears += floor($passengerMonths / 12);
    $passengerMonths = $passengerMonths % 12;

    // Στρογγυλοποίηση των ετών εμπορευματικών μεταφορών
    $freightDecimalYears = $freightYears + ($freightMonths / 12) + ($freightDays / 365);
    $roundedFreightYears = round($freightDecimalYears);

    // Στρογγυλοποίηση των ετών επιβατικών μεταφορών
    $passengerDecimalYears = $passengerYears + ($passengerMonths / 12) + ($passengerDays / 365);
    $roundedPassengerYears = round($passengerDecimalYears);
} else {
    $roundedFreightYears = 0;
    $roundedPassengerYears = 0;
}

// Υπολογισμός συνολικής προϋπηρεσίας
$totalYears = $freightYears + $passengerYears;
$totalMonths = $freightMonths + $passengerMonths;
$totalDays = $freightDays + $passengerDays;

// Κανονικοποίηση του συνόλου
$normalizedTotalMonths = $totalMonths + floor($totalDays / 30);
$normalizedTotalDays = $totalDays % 30;
$normalizedTotalYears = $totalYears + floor($normalizedTotalMonths / 12);
$normalizedTotalMonths = $normalizedTotalMonths % 12;

// Στρογγυλοποίηση των ετών προϋπηρεσίας στον πλησιέστερο ακέραιο
$totalDecimalYears = $normalizedTotalYears + ($normalizedTotalMonths / 12) + ($normalizedTotalDays / 365);
$roundedTotalYears = round($totalDecimalYears);

// Εμφάνιση των αποτελεσμάτων
?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Διαγνωστικά Προϋπηρεσίας</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }

        h1 {
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .section {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        .experience-display {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            background-color: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .experience-column {
            flex: 1;
            text-align: center;
            padding: 0 10px;
        }

        .experience-column:not(:last-child) {
            border-right: 1px solid #ddd;
        }

        .experience-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }

        .total {
            color: #007bff;
        }

        .freight {
            color: #28a745;
        }

        .passenger {
            color: #dc3545;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
        }

        .debug-info {
            font-family: monospace;
            background-color: #f8f8f8;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>

<body>
    <h1>Διαγνωστικά Προϋπηρεσίας Οδηγού</h1>

    <div class="section">
        <h2>Συνοπτικά Στοιχεία</h2>
        <div class="experience-display">
            <div class="experience-column">
                <div>Συνολική Προϋπηρεσία</div>
                <div class="experience-value total"><?php echo $roundedTotalYears; ?> έτη</div>
                <div>(<?php echo $normalizedTotalYears; ?> έτη, <?php echo $normalizedTotalMonths; ?> μήνες, <?php echo $normalizedTotalDays; ?> ημέρες)</div>
            </div>
            <div class="experience-column">
                <div>Εμπορευματικές Μεταφορές</div>
                <div class="experience-value freight"><?php echo $roundedFreightYears; ?> έτη</div>
                <div>(<?php echo $freightYears; ?> έτη, <?php echo $freightMonths; ?> μήνες, <?php echo $freightDays; ?> ημέρες)</div>
            </div>
            <div class="experience-column">
                <div>Επιβατικές Μεταφορές</div>
                <div class="experience-value passenger"><?php echo $roundedPassengerYears; ?> έτη</div>
                <div>(<?php echo $passengerYears; ?> έτη, <?php echo $passengerMonths; ?> μήνες, <?php echo $passengerDays; ?> ημέρες)</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Αναλυτικά Δεδομένα Προϋπηρεσίας</h2>
        <?php if (!empty($driverVehicleExperience)) : ?>
            <table>
                <thead>
                    <tr>
                        <th>Τύπος Μεταφοράς</th>
                        <th>Έτη</th>
                        <th>Μήνες</th>
                        <th>Ημέρες</th>
                        <th>Τύπος Οχήματος</th>
                        <th>Περιγραφή</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($driverVehicleExperience as $exp) : ?>
                        <tr>
                            <td><?php echo $exp['transport_type'] === 'freight' ? 'Εμπορευματικές' : 'Επιβατικές'; ?></td>
                            <td><?php echo $exp['years'] ?? 0; ?></td>
                            <td><?php echo $exp['months'] ?? 0; ?></td>
                            <td><?php echo $exp['days'] ?? 0; ?></td>
                            <td><?php echo $exp['vehicle_type'] ?? ''; ?></td>
                            <td><?php echo $exp['description'] ?? ''; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>Δεν υπάρχουν καταχωρημένα δεδομένα προϋπηρεσίας.</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>Υπολογισμοί</h2>
        <div class="debug-info">
            <p><strong>Εμπορευματικές Μεταφορές:</strong><br>
                Έτη: <?php echo $freightYears; ?><br>
                Μήνες: <?php echo $freightMonths; ?><br>
                Ημέρες: <?php echo $freightDays; ?><br>
                Δεκαδικά έτη: <?php echo $freightDecimalYears; ?><br>
                Στρογγυλοποιημένα έτη: <?php echo $roundedFreightYears; ?></p>

            <p><strong>Επιβατικές Μεταφορές:</strong><br>
                Έτη: <?php echo $passengerYears; ?><br>
                Μήνες: <?php echo $passengerMonths; ?><br>
                Ημέρες: <?php echo $passengerDays; ?><br>
                Δεκαδικά έτη: <?php echo $passengerDecimalYears; ?><br>
                Στρογγυλοποιημένα έτη: <?php echo $roundedPassengerYears; ?></p>

            <p><strong>Συνολική Προϋπηρεσία:</strong><br>
                Έτη: <?php echo $normalizedTotalYears; ?><br>
                Μήνες: <?php echo $normalizedTotalMonths; ?><br>
                Ημέρες: <?php echo $normalizedTotalDays; ?><br>
                Δεκαδικά έτη: <?php echo $totalDecimalYears; ?><br>
                Στρογγυλοποιημένα έτη: <?php echo $roundedTotalYears; ?></p>
        </div>
    </div>

    <div class="section">
        <h2>Ενέργειες</h2>
        <p><a href="<?php echo BASE_URL; ?>drivers/edit-profile">Επιστροφή στην επεξεργασία προφίλ</a></p>
        <p><a href="<?php echo BASE_URL; ?>drivers/vehicle_experience">Διαχείριση προϋπηρεσίας σε οχήματα</a></p>
    </div>
</body>

</html>