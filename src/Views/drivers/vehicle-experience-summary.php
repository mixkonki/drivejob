<?php
// Ελέγχουμε αν υπάρχουν δεδομένα προϋπηρεσίας
if (isset($driverVehicleExperience) && !empty($driverVehicleExperience)) :
    // Υπολογισμός συνόλων για εμπορευματικές και επιβατικές μεταφορές
    $freightYears = 0;
    $freightMonths = 0;
    $freightDays = 0;
    $passengerYears = 0;
    $passengerMonths = 0;
    $passengerDays = 0;

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
    
    // Στρογγυλοποίηση των ετών εμπορευματικών μεταφορών
    $freightDecimalYears = $freightYears + ($freightMonths / 12) + ($freightDays / 365);
    $roundedFreightYears = round($freightDecimalYears);
    
    // Στρογγυλοποίηση των ετών επιβατικών μεταφορών
    $passengerDecimalYears = $passengerYears + ($passengerMonths / 12) + ($passengerDays / 365);
    $roundedPassengerYears = round($passengerDecimalYears);
    
    // Εμφάνιση διαγνωστικών μηνυμάτων
    
    // Ενημέρωση του πεδίου experience_years στα δεδομένα του οδηγού
    if (isset($driverData) && is_array($driverData)) {
        $driverData['experience_years'] = $roundedTotalYears;

        // Ενημέρωση του πεδίου experience_years στη φόρμα
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const experienceYearsField = document.getElementById("experience_years");
                if (experienceYearsField) {
                    experienceYearsField.value = ' . $roundedTotalYears . ';
                    console.log("Ενημέρωση πεδίου experience_years με τιμή:", ' . $roundedTotalYears . ');
                }
            });
        </script>';
    }
?>
    <div class="vehicle-experience-summary">
        <h4>Σύνοψη Προϋπηρεσίας σε Οχήματα</h4>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Είδος Μεταφορών</th>
                    <th>Διάστημα</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Εμπορευματικές</td>
                    <td><?php echo $freightYears; ?> έτη, <?php echo $freightMonths; ?> μήνες, <?php echo $freightDays; ?> ημέρες</td>
                </tr>
                <tr>
                    <td>Επιβατικές</td>
                    <td><?php echo $passengerYears; ?> έτη, <?php echo $passengerMonths; ?> μήνες, <?php echo $passengerDays; ?> ημέρες</td>
                </tr>
                <tr class="summary-row total-summary">
                    <td><strong>Συνολική Προϋπηρεσία</strong></td>
                    <td><strong><?php echo $normalizedTotalYears; ?> έτη, <?php echo $normalizedTotalMonths; ?> μήνες, <?php echo $normalizedTotalDays; ?> ημέρες</strong></td>
                </tr>
            </tbody>
        </table>
    </div>
<?php else : ?>
    <div class="vehicle-experience-summary">
        <p>Δεν έχει καταχωρηθεί προϋπηρεσία σε οχήματα.</p>
    </div>
<?php endif; ?>
