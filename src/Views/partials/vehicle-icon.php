<?php

/**
 * Εικονίδιο τύπου οχήματος.
 *
 * ΓΙΑΤΙ ΥΠΑΡΧΕΙ: η λίστα αγγελιών έδειχνε το ΙΔΙΟ εικονίδιο φορτηγού για κάθε
 * αγγελία — λεωφορείο, βαν, βυτιοφόρο και εκσκαφέας ήταν οπτικά ίδια. Ο
 * οδηγός δεν μπορούσε να ξεχωρίσει με μια ματιά τι είδους θέση είναι.
 *
 * Τα εικονίδια είναι inline SVG στο ίδιο σύστημα με το κουμπί συνθηματικού:
 * γραμμές πάχους 1.6, currentColor, χωρίς αρχεία. Κληρονομούν το χρώμα του
 * κειμένου γύρω τους και μένουν ευκρινή σε κάθε μέγεθος.
 *
 * Χρήση:
 *     <?php $vehicleIcon = 'truck_tanker';
 *           include ROOT_DIR . '/src/Views/partials/vehicle-icon.php'; ?>
 *
 * Μεταβλητές:
 *   $vehicleIcon  κωδικός τύπου (παλιές τιμές μεταφράζονται αυτόματα)
 *   $vehicleIconSize  προαιρετικό μέγεθος σε px, προεπιλογή 22
 */

$code = \Drivejob\Helpers\VehicleTypes::normalise($vehicleIcon ?? '');
$size = (int) ($vehicleIconSize ?? 22);
$label = \Drivejob\Helpers\VehicleTypes::label($code);

/**
 * Κοινή γεωμετρία ώστε τα εικονίδια να μοιάζουν οικογένεια:
 *   έδαφος στο y=17 · τροχοί με κέντρο y=16.3 · αμάξωμα από y=5 έως y=14.5
 */
$shapes = [
    'car' =>
        '<path d="M3.5 15.6v-2.4l1.9-4.1A2.1 2.1 0 0 1 7.3 7.9h9.4a2.1 2.1 0 0 1 1.9 1.2l1.9 4.1v2.4"/>
         <path d="M3.5 13.2h17"/>
         <circle cx="7.4" cy="15.8" r="1.9"/><circle cx="16.6" cy="15.8" r="1.9"/>',

    'van' =>
        '<path d="M2.5 15.6V7.6a1.4 1.4 0 0 1 1.4-1.4h8.6a1.4 1.4 0 0 1 1.4 1.4v8"/>
         <path d="M13.9 9.9h3a2 2 0 0 1 1.6.85l1.7 2.4a1.4 1.4 0 0 1 .3.85v1.6"/>
         <path d="M14 12.6h6.6"/>
         <circle cx="6.6" cy="15.9" r="1.9"/><circle cx="17.2" cy="15.9" r="1.9"/>',

    'minibus' =>
        '<rect x="2.6" y="5.4" width="18.8" height="10.2" rx="2"/>
         <path d="M2.6 9.2h18.8M9 5.4v3.8M15 5.4v3.8"/>
         <circle cx="7" cy="16.8" r="1.7"/><circle cx="17" cy="16.8" r="1.7"/>',

    'bus' =>
        '<rect x="2" y="4.6" width="20" height="11" rx="2"/>
         <path d="M2 8.8h20M7.3 4.6v4.2M12 4.6v4.2M16.7 4.6v4.2"/>
         <path d="M2 12.8h20"/>
         <circle cx="6.6" cy="17" r="1.7"/><circle cx="17.4" cy="17" r="1.7"/>',

    'truck_light' =>
        '<path d="M2.2 15.4V7.4h8.6v8"/>
         <path d="M10.8 15.4v-5h3.1a1.8 1.8 0 0 1 1.45.72l1.8 2.4a1.5 1.5 0 0 1 .3.9v0.98"/>
         <path d="M10.9 12.9h6.5"/>
         <circle cx="6" cy="15.9" r="1.8"/><circle cx="15" cy="15.9" r="1.8"/>',

    'truck_medium' =>
        '<path d="M1.6 15.2V6.4h11v8.8"/>
         <path d="M12.6 15.2v-5.4h3.3a1.8 1.8 0 0 1 1.45.72l2 2.7a1.5 1.5 0 0 1 .3.9v1.08"/>
         <path d="M12.7 12.8h6.9"/>
         <circle cx="5.4" cy="15.8" r="1.8"/><circle cx="16.8" cy="15.8" r="1.8"/>',

    'truck_heavy' =>
        '<path d="M1.4 14.9V5.8h11.6v9.1"/>
         <path d="M13 14.9V9.3h3.4a1.8 1.8 0 0 1 1.45.72l2.1 2.8a1.5 1.5 0 0 1 .3.9v1.18"/>
         <path d="M13.1 12.6h7.1"/>
         <circle cx="4.6" cy="15.6" r="1.6"/><circle cx="8.6" cy="15.6" r="1.6"/>
         <circle cx="17.2" cy="15.6" r="1.6"/>',

    'truck_articulated' =>
        '<path d="M1.3 14.6V6.2h11.4v8.4"/>
         <path d="M12.7 12.6h1.6"/>
         <path d="M14.3 14.6V9.4h2.9a1.7 1.7 0 0 1 1.4.7l1.8 2.5a1.5 1.5 0 0 1 .3.9v1.1"/>
         <path d="M14.4 12.4h6.2"/>
         <circle cx="4.4" cy="15.4" r="1.5"/><circle cx="8" cy="15.4" r="1.5"/>
         <circle cx="17.6" cy="15.4" r="1.5"/>',

    'truck_tanker' =>
        '<rect x="1.4" y="7.4" width="11.8" height="7" rx="3.5"/>
         <path d="M5.3 7.4v7M9.3 7.4v7"/>
         <path d="M13.4 14.4V9.4h3a1.8 1.8 0 0 1 1.45.72l1.9 2.6a1.5 1.5 0 0 1 .3.9v0.78"/>
         <path d="M13.5 12.4h6.5"/>
         <circle cx="5.6" cy="15.6" r="1.7"/><circle cx="17" cy="15.6" r="1.7"/>',

    'truck_refrigerated' =>
        '<path d="M1.6 15.2V6.4h11v8.8"/>
         <path d="M7.1 8.4v4.6M5.2 9.5l3.8 2.4M9 9.5l-3.8 2.4"/>
         <path d="M12.6 15.2v-5.4h3.3a1.8 1.8 0 0 1 1.45.72l2 2.7a1.5 1.5 0 0 1 .3.9v1.08"/>
         <circle cx="5.4" cy="15.8" r="1.8"/><circle cx="16.8" cy="15.8" r="1.8"/>',

    'machinery' =>
        '<path d="M2.6 14.2h8.2a1.4 1.4 0 0 1 1.4 1.4v0a1.4 1.4 0 0 1-1.4 1.4H4a1.4 1.4 0 0 1-1.4-1.4v0a1.4 1.4 0 0 1 1.4-1.4Z"/>
         <path d="M4.4 9.8h5.2v4.4H4.4z"/>
         <path d="M9.6 11.2l4.6-4.2 2.6 1.4"/>
         <path d="M16.8 8.4l3.4 2.6-1.2 3-3.4-2.2z"/>',
];

$shape = $shapes[$code] ?? $shapes['truck_medium'];
?>
<svg class="dj-vehicle-icon" viewBox="0 0 24 24"
     width="<?php echo $size; ?>" height="<?php echo $size; ?>"
     fill="none" stroke="currentColor" stroke-width="1.6"
     stroke-linecap="round" stroke-linejoin="round"
     role="img" aria-label="<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>"><?php
         echo $shape;
     ?></svg>
<?php
// Οι μεταβλητές καθαρίζονται ώστε μια δεύτερη κλήση χωρίς $vehicleIcon να μην
// κληρονομήσει σιωπηλά τον προηγούμενο τύπο.
unset($vehicleIcon, $vehicleIconSize, $code, $size, $label, $shapes, $shape);
