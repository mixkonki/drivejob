<?php

/**
 * Δεδομένα δοκιμών beta: 10 εταιρίες με αγγελίες + 10 οδηγοί. (01/09/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Για τις δοκιμές της beta χρειάζεται μια πλατφόρμα που ΜΟΙΑΖΕΙ ζωντανή:
 * αγγελίες σε πολλές πόλεις, εμπορευματικές ΚΑΙ επιβατικές ΚΑΙ μηχανήματα,
 * οδηγοί με διαφορετικά προσόντα ώστε το ταίριασμα να έχει τι να δείξει —
 * ο οδηγός λεωφορείου να βλέπει τα ΚΤΕΛ ψηλά και τις νταλίκες χαμηλά.
 *
 * Ονοματολογία: info+betaetairia1..10@ / info+betaodigos1..10@thessdrive.gr
 * (plus-addressing στο mailbox του Κώστα — ένα LIKE 'info+beta%' τα
 * βρίσκει ΟΛΑ στο beta-cleanup). Κωδικός παντού: Demo!2026drivejob.
 *
 * Idempotent: εταιρίες/οδηγοί εντοπίζονται από email, αγγελίες από
 * (company_id, title) — δεύτερο τρέξιμο δεν διπλασιάζει τίποτα.
 */

require_once __DIR__ . '/../../../src/bootstrap.php';

$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
restore_exception_handler();
restore_error_handler();

$hash = password_hash('Demo!2026drivejob', PASSWORD_DEFAULT);
$now = date('Y-m-d H:i:s');
$expires = date('Y-m-d H:i:s', strtotime('+90 days'));

/** Συντεταγμένες πόλεων — ίδιες τιμές με GeoLocationService::$knownLocations. */
$geo = [
    'Αθήνα' => [37.9838, 23.7275], 'Θεσσαλονίκη' => [40.6401, 22.9444],
    'Πάτρα' => [38.2466, 21.7345], 'Ηράκλειο' => [35.3387, 25.1442],
    'Λάρισα' => [39.6390, 22.4174], 'Βόλος' => [39.3621, 22.9460],
    'Ιωάννινα' => [39.6650, 20.8537], 'Σέρρες' => [41.0914, 23.5470],
    'Καβάλα' => [40.9374, 24.4122], 'Ρόδος' => [36.4340, 28.2176],
];

// ═══ 1. ΕΤΑΙΡΙΕΣ ═══════════════════════════════════════════════════════
// [όνομα, πόλη, κλάδος, στόλος, οδηγοί, transport_types, περιγραφή]
$companies = [
    1 => ['Μεταφορική Βορρά ΑΕ', 'Θεσσαλονίκη', 'Μεταφορές & Logistics', 48, 55, 'international,national', 'ΔΟΚΙΜΑΣΤΙΚΗ ΕΓΓΡΑΦΗ BETA. Διεθνείς οδικές μεταφορές προς Βαλκάνια και Κεντρική Ευρώπη, στόλος 48 συρμών Euro 6.'],
    2 => ['Logistics Αττικής ΕΠΕ', 'Αθήνα', 'Μεταφορές & Logistics', 35, 40, 'urban,national', 'ΔΟΚΙΜΑΣΤΙΚΗ ΕΓΓΡΑΦΗ BETA. Αστικές διανομές και last-mile στο λεκανοπέδιο, κέντρα διαλογής σε Ασπρόπυργο και Κορωπί.'],
    3 => ['Ψυκτικές Μεταφορές Κρήτης', 'Ηράκλειο', 'Τρόφιμα & Ποτά', 22, 26, 'refrigerated,national', 'ΔΟΚΙΜΑΣΤΙΚΗ ΕΓΓΡΑΦΗ BETA. Μεταφορές νωπών και κατεψυγμένων σε όλη την Κρήτη και ακτοπλοϊκώς προς Πειραιά.'],
    4 => ['Υπεραστικές Γραμμές Ηπείρου', 'Ιωάννινα', 'Τουρισμός & Μετακινήσεις', 30, 45, 'passenger', 'ΔΟΚΙΜΑΣΤΙΚΗ ΕΓΓΡΑΦΗ BETA. Τακτικές υπεραστικές γραμμές Ηπείρου και δρομολόγια προς Αθήνα και Θεσσαλονίκη.'],
    5 => ['Aegean Tours & Transfers', 'Ρόδος', 'Τουρισμός & Μετακινήσεις', 18, 28, 'passenger', 'ΔΟΚΙΜΑΣΤΙΚΗ ΕΓΓΡΑΦΗ BETA. Τουριστικά πούλμαν και transfers αεροδρομίου, έντονη εποχικότητα Απριλίου-Οκτωβρίου.'],
    6 => ['Τεχνική Δομική Θεσσαλίας', 'Λάρισα', 'Κατασκευές', 26, 32, 'machinery,national', 'ΔΟΚΙΜΑΣΤΙΚΗ ΕΓΓΡΑΦΗ BETA. Χωματουργικά και οδοποιία σε όλη τη Θεσσαλία — εκσκαφείς, φορτωτές, γκρέιντερ.'],
    7 => ['Καύσιμα Πελοποννήσου ΑΕ', 'Πάτρα', 'Μεταφορές & Logistics', 15, 20, 'tanker,adr', 'ΔΟΚΙΜΑΣΤΙΚΗ ΕΓΓΡΑΦΗ BETA. Διανομή υγρών καυσίμων σε πρατήρια Δυτικής Ελλάδας και Πελοποννήσου με βυτιοφόρα ADR.'],
    8 => ['Ταχυμεταφορές Κεντρικής Ελλάδας', 'Βόλος', 'Μεταφορές & Logistics', 40, 48, 'urban,courier', 'ΔΟΚΙΜΑΣΤΙΚΗ ΕΓΓΡΑΦΗ BETA. Courier και δέματα, καθημερινά δρομολόγια σε Μαγνησία, Φθιώτιδα και Λάρισα.'],
    9 => ['Αγροδιακίνηση Μακεδονίας', 'Σέρρες', 'Μεταφορές & Logistics', 20, 24, 'national,agricultural', 'ΔΟΚΙΜΑΣΤΙΚΗ ΕΓΓΡΑΦΗ BETA. Μεταφορά αγροτικών προϊόντων από Σέρρες και Δράμα προς κεντρικές αγορές.'],
    10 => ['Μετακινήσεις Προσωπικού Καβάλας', 'Καβάλα', 'Τουρισμός & Μετακινήσεις', 12, 16, 'passenger', 'ΔΟΚΙΜΑΣΤΙΚΗ ΕΓΓΡΑΦΗ BETA. Μεταφορά προσωπικού εργοστασίων και ξενοδοχείων με μίνι πούλμαν σε Καβάλα και Ξάνθη.'],
];

$companyIds = [];
$newCompanies = 0;

foreach ($companies as $n => [$name, $city, $industry, $fleet, $drivers, $ttypes, $descr]) {
    $email = 'info+betaetairia' . $n . '@thessdrive.gr';
    $stmt = $pdo->prepare('SELECT id FROM companies WHERE email = ?');
    $stmt->execute([$email]);
    $id = $stmt->fetchColumn();

    [$lat, $lng] = $geo[$city];

    if (!$id) {
        $pdo->prepare(
            'INSERT INTO companies (email, password, company_name, phone, vat_number, city, country,
                 description, industry, company_size, foundation_year, founded_year, fleet_size,
                 active_drivers, transport_types, latitude, longitude,
                 is_verified, is_active, status, created_at, terms_accepted_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,1,\'active\',?,?)'
        )->execute([
            $email, $hash, $name, '23105551' . str_pad((string) $n, 2, '0', STR_PAD_LEFT),
            '9000001' . str_pad((string) $n, 2, '0', STR_PAD_LEFT), $city, 'Ελλάδα',
            $descr, $industry, $fleet > 30 ? '51-200' : '11-50', 2000 + $n, (string) (2000 + $n),
            $fleet, $drivers, $ttypes, $lat, $lng, $now, $now,
        ]);
        $id = (int) $pdo->lastInsertId();
        $newCompanies++;
    } else {
        // Υπάρχει ήδη: μόνο κωδικός/κατάσταση, τα υπόλοιπα δεν πειράζονται.
        $pdo->prepare('UPDATE companies SET password = ?, is_verified = 1, is_active = 1 WHERE id = ?')
            ->execute([$hash, $id]);
    }
    $companyIds[$n] = (int) $id;
}

// ═══ 2. ΑΓΓΕΛΙΕΣ ═══════════════════════════════════════════════════════
// [εταιρία, τίτλος, κατηγορία, transport, vehicle, license, pei, adr, tacho, oper,
//  εμπειρία, μισθός από-έως, job_type, urgent, περιγραφή, machinery_types]
$L = [
    // Μεταφορική Βορρά — διεθνή CE
    [1, 'Οδηγός διεθνών μεταφορών CE — Κεντρική Ευρώπη', 'cargo_transport', 'freight', 'truck_articulated', 'CE', 1, 0, 1, 0, 3, 1700, 2100, 'full_time', 1, 'Σταθερά δρομολόγια Θεσσαλονίκη–Γερμανία με επιστροφή ανά 15ήμερο. Σύγχρονοι ελκυστήρες Euro 6, πληρωμένες διανυκτερεύσεις.'],
    [1, 'Οδηγός CE γραμμής Βαλκανίων — εβδομαδιαία επιστροφή', 'cargo_transport', 'freight', 'truck_articulated', 'CE', 1, 0, 1, 0, 2, 1500, 1800, 'full_time', 0, 'Σόφια, Βελιγράδι, Βουκουρέστι. Κάθε Παρασκευή βράδυ στη βάση. Παλετοποιημένα φορτία.'],
    [1, 'Οδηγός CE με ADR — χημικά φορτία', 'cargo_transport', 'freight', 'truck_articulated', 'CE', 1, 1, 1, 0, 3, 1800, 2200, 'full_time', 0, 'Συσκευασμένα χημικά προς βιομηχανικούς πελάτες Βαλκανίων. Απαιτείται ADR σε ισχύ.'],
    [1, 'Οδηγός εσωτερικού C — τροφοδοσία Βόρειας Ελλάδας', 'cargo_transport', 'freight', 'truck_heavy', 'C', 1, 0, 1, 0, 1, 1250, 1500, 'full_time', 0, 'Ημερήσια δρομολόγια σε Μακεδονία και Θράκη, κάθε βράδυ σπίτι.'],
    [1, 'Βοηθός αποθήκης με δίπλωμα Γ1 — βραδινή βάρδια', 'cargo_transport', 'freight', 'truck_light', 'C1', 0, 0, 0, 0, 0, 1000, 1150, 'full_time', 0, 'Φορτοεκφόρτωση και τοπικές μετακινήσεις μεταξύ αποθηκών. Κατάλληλο για νέο επαγγελματία.'],
    // Logistics Αττικής — διανομές
    [2, 'Οδηγός βαν — διανομές κέντρου Αθήνας', 'cargo_transport', 'freight', 'van', 'B', 0, 0, 0, 0, 1, 1000, 1250, 'full_time', 1, 'Καθημερινές διανομές σε καταστήματα με προκαθορισμένο δρομολόγιο. Παρέχεται εταιρικό κινητό.'],
    [2, 'Οδηγός ελαφρού φορτηγού Γ1 — πρωινή βάρδια Ασπρόπυργος', 'cargo_transport', 'freight', 'truck_light', 'C1', 1, 0, 0, 0, 1, 1150, 1400, 'full_time', 0, 'Ανεφοδιασμός σούπερ μάρκετ από το κέντρο διανομής. Βάρδια 05:30–13:30.'],
    [2, 'Οδηγός διανομών Σαββατοκύριακου — μερική απασχόληση', 'cargo_transport', 'freight', 'van', 'B', 0, 0, 0, 0, 0, 500, 650, 'part_time', 0, 'Σάββατο και Κυριακή, 8ωρο. Ιδανικό για συμπλήρωμα εισοδήματος.'],
    [2, 'Οδηγός μεσαίου φορτηγού — γραμμή Αθήνα–Χαλκίδα', 'cargo_transport', 'freight', 'truck_medium', 'C', 1, 0, 1, 0, 2, 1300, 1550, 'full_time', 0, 'Δύο δρομολόγια ημερησίως με σταθερούς πελάτες χονδρικής.'],
    // Ψυκτικές Κρήτης
    [3, 'Οδηγός ψυγείου CE — Κρήτη και ηπειρωτική', 'cargo_transport', 'freight', 'truck_refrigerated', 'CE', 1, 0, 1, 0, 3, 1500, 1800, 'full_time', 0, 'Νωπά προς Αθήνα μέσω ακτοπλοΐας δύο φορές την εβδομάδα. Αυστηρή ψυκτική αλυσίδα.'],
    [3, 'Οδηγός ψυγείου Γ — τοπικές διανομές Ηρακλείου', 'cargo_transport', 'freight', 'truck_refrigerated', 'C', 1, 0, 0, 0, 1, 1250, 1450, 'full_time', 0, 'Καθημερινή τροφοδοσία ξενοδοχείων και σούπερ μάρκετ του νομού.'],
    [3, 'Εποχικός οδηγός Γ — καλοκαιρινή περίοδος', 'cargo_transport', 'freight', 'truck_refrigerated', 'C', 1, 0, 0, 0, 1, 1300, 1500, 'seasonal', 1, 'Μάιος–Οκτώβριος, ενίσχυση δρομολογίων τουριστικής σεζόν. Δυνατότητα μονιμοποίησης.'],
    // Υπεραστικές Ηπείρου
    [4, 'Οδηγός υπεραστικού λεωφορείου — γραμμές Ηπείρου', 'passenger_transport', 'passenger', 'bus', 'D', 1, 0, 1, 0, 3, 1400, 1700, 'full_time', 0, 'Τακτικές γραμμές Ιωάννινα–Άρτα–Πρέβεζα. Απαιτείται ΠΕΙ επιβατών σε ισχύ.'],
    [4, 'Οδηγός λεωφορείου — γραμμή Ιωάννινα–Αθήνα', 'passenger_transport', 'passenger', 'bus', 'D', 1, 0, 1, 0, 5, 1600, 1900, 'full_time', 1, 'Νυχτερινά και ημερήσια δρομολόγια εθνικής γραμμής, εναλλάξ με δεύτερο οδηγό.'],
    [4, 'Οδηγός μίνι πούλμαν — τοπικές διαδρομές', 'passenger_transport', 'passenger', 'minibus', 'D1', 1, 0, 0, 0, 1, 1150, 1350, 'full_time', 0, 'Μαθητικά και τοπικά δρομολόγια ορεινών χωριών.'],
    [4, 'Οδηγός λεωφορείου — μερική απασχόληση εκδρομών', 'passenger_transport', 'passenger', 'bus', 'D', 1, 0, 1, 0, 2, 600, 800, 'part_time', 0, 'Σαββατοκύριακα και αργίες, εκδρομικά δρομολόγια συλλόγων.'],
    // Aegean Tours
    [5, 'Οδηγός τουριστικού πούλμαν — σεζόν Ρόδου', 'passenger_transport', 'passenger', 'bus', 'D', 1, 0, 1, 0, 2, 1500, 1900, 'seasonal', 1, 'Απρίλιος–Οκτώβριος, οργανωμένες εκδρομές και transfers. Παρέχεται διαμονή. Αγγλικά απαραίτητα.'],
    [5, 'Οδηγός transfers αεροδρομίου — μίνι πούλμαν', 'passenger_transport', 'passenger', 'minibus', 'D1', 1, 0, 0, 0, 1, 1200, 1500, 'seasonal', 0, 'Μεταφορές ξενοδοχείων από/προς αεροδρόμιο Ρόδου, κυλιόμενες βάρδιες.'],
    [5, 'Οδηγός VIP μεταφορών — επιβατικό', 'passenger_transport', 'passenger', 'car', 'B', 0, 0, 0, 0, 3, 1100, 1400, 'full_time', 0, 'Ιδιωτικές μεταφορές υψηλών προδιαγραφών. Προϋπηρεσία σε αντίστοιχη θέση και αγγλικά.'],
    [5, 'Οδηγός πούλμαν πολυήμερων εκδρομών', 'passenger_transport', 'passenger', 'bus', 'D', 1, 0, 1, 0, 4, 1600, 2000, 'contract', 0, 'Πολυήμερες εκδρομές Ελλάδας με τουριστικά γραφεία. Σύμβαση έργου ανά σεζόν.'],
    // Τεχνική Δομική
    [6, 'Χειριστής εκσκαφέα — εργοτάξια Θεσσαλίας', 'machinery_operator', 'machinery', 'machinery', '', 0, 0, 0, 1, 3, 1500, 1900, 'full_time', 1, 'Χωματουργικά οδοποιίας. Άδεια χειριστή Α΄ ομάδας 1ης ειδικότητας απαραίτητη.', 'excavator'],
    [6, 'Χειριστής φορτωτή — λατομείο Τυρνάβου', 'machinery_operator', 'machinery', 'machinery', '', 0, 0, 0, 1, 2, 1400, 1700, 'full_time', 0, 'Φόρτωση αδρανών σε σταθερό λατομείο, μόνιμη βάση.', 'loader'],
    [6, 'Βοηθός χειριστή μηχανημάτων έργου', 'machinery_assistant', 'machinery', 'machinery', '', 0, 0, 0, 0, 0, 1000, 1200, 'full_time', 0, 'Υποστήριξη εργοταξίου και εκπαίδευση δίπλα σε έμπειρους χειριστές — προοπτική απόκτησης άδειας.', 'excavator,loader'],
    [6, 'Οδηγός Γ+Ε με άδεια χειριστή — μεταφορά μηχανημάτων', 'cargo_transport', 'freight', 'truck_articulated', 'CE', 1, 0, 1, 1, 4, 1700, 2000, 'full_time', 0, 'Μεταφορά μηχανημάτων με επικαθήμενη πλατφόρμα μεταξύ εργοταξίων. Συνδυασμός CE και άδειας χειριστή.', 'excavator'],
    // Καύσιμα Πελοποννήσου
    [7, 'Οδηγός βυτιοφόρου ADR — διανομή καυσίμων', 'cargo_transport', 'freight', 'truck_tanker', 'CE', 1, 1, 1, 0, 3, 1800, 2200, 'full_time', 1, 'Διανομή σε πρατήρια Αχαΐας και Ηλείας. ADR δεξαμενών (επέκταση βυτίου) απαραίτητο.'],
    [7, 'Οδηγός βυτιοφόρου Γ — τοπικές διανομές πετρελαίου θέρμανσης', 'cargo_transport', 'freight', 'truck_tanker', 'C', 1, 1, 0, 0, 2, 1500, 1800, 'full_time', 0, 'Εποχική ένταση χειμώνα, σταθερό πελατολόγιο.'],
    [7, 'Οδηγός ADR νυχτερινής τροφοδοσίας', 'cargo_transport', 'freight', 'truck_tanker', 'CE', 1, 1, 1, 0, 4, 2000, 2400, 'full_time', 0, 'Νυχτερινός ανεφοδιασμός μεγάλων πρατηρίων εθνικής οδού. Επίδομα νύχτας.'],
    // Ταχυμεταφορές Κεντρικής
    [8, 'Οδηγός courier βαν — Βόλος', 'cargo_transport', 'freight', 'van', 'B', 0, 0, 0, 0, 0, 950, 1200, 'full_time', 1, 'Παραδόσεις δεμάτων με scanner και εταιρικό όχημα. Δεν απαιτείται προϋπηρεσία — παρέχεται εκπαίδευση.'],
    [8, 'Οδηγός βαν — γραμμή Βόλος–Λαμία', 'cargo_transport', 'freight', 'van', 'B', 0, 0, 0, 0, 1, 1050, 1300, 'full_time', 0, 'Μεταφορά δεμάτων μεταξύ κέντρων διαλογής, σταθερό ωράριο.'],
    [8, 'Οδηγός ελαφρού φορτηγού — ογκώδη δέματα', 'cargo_transport', 'freight', 'truck_light', 'C1', 1, 0, 0, 0, 1, 1200, 1400, 'full_time', 0, 'Παραδόσεις επίπλων και λευκών συσκευών με βοηθό.'],
    [8, 'Οδηγός βαν απογευματινής βάρδιας — μερική', 'cargo_transport', 'freight', 'van', 'B', 0, 0, 0, 0, 0, 550, 700, 'part_time', 0, 'Απογευματινές παραδόσεις 16:00–21:00, κατάλληλο για φοιτητές με δίπλωμα Β.'],
    // Αγροδιακίνηση Μακεδονίας
    [9, 'Οδηγός φορτηγού Γ — αγροτικά προϊόντα', 'cargo_transport', 'freight', 'truck_heavy', 'C', 1, 0, 1, 0, 2, 1300, 1600, 'full_time', 0, 'Μεταφορά από συνεταιρισμούς Σερρών προς κεντρική λαχαναγορά Θεσσαλονίκης.'],
    [9, 'Οδηγός CE εποχικής αιχμής — περίοδος συγκομιδής', 'cargo_transport', 'freight', 'truck_articulated', 'CE', 1, 0, 1, 0, 2, 1500, 1900, 'seasonal', 1, 'Ιούνιος–Οκτώβριος, μεταφορά ροδάκινων και βαμβακιού. Υψηλές αποδοχές αιχμής.'],
    [9, 'Οδηγός ψυγείου Γ — ευπαθή προϊόντα', 'cargo_transport', 'freight', 'truck_refrigerated', 'C', 1, 0, 0, 0, 1, 1300, 1550, 'full_time', 0, 'Καθημερινά δρομολόγια προς αγορές Βόρειας Ελλάδας.'],
    [9, 'Οδηγός αγροτικών μεταφορών — δοκιμαστική περίοδος', 'cargo_transport', 'freight', 'truck_medium', 'C', 0, 0, 0, 0, 0, 1150, 1350, 'temporary', 0, 'Τρίμηνη σύμβαση με προοπτική μονιμοποίησης. Νέοι επαγγελματίες ευπρόσδεκτοι.'],
    // Μετακινήσεις Καβάλας
    [10, 'Οδηγός μίνι πούλμαν — μεταφορά προσωπικού εργοστασίου', 'passenger_transport', 'passenger', 'minibus', 'D1', 1, 0, 0, 0, 1, 1100, 1350, 'full_time', 0, 'Πρωινή και απογευματινή μεταφορά βαρδιών, σταθερή διαδρομή Καβάλα–ΒΙΠΕ.'],
    [10, 'Οδηγός λεωφορείου ξενοδοχειακών μεταφορών', 'passenger_transport', 'passenger', 'bus', 'D', 1, 0, 1, 0, 2, 1300, 1600, 'seasonal', 0, 'Μεταφορές ομάδων από αεροδρόμιο Καβάλας προς μονάδες Θάσου και Κεραμωτής.'],
    [10, 'Οδηγός μίνι πούλμαν Σαββατοκύριακου', 'passenger_transport', 'passenger', 'minibus', 'D1', 1, 0, 0, 0, 0, 500, 650, 'part_time', 0, 'Εκδρομικά και ιδιωτικές μισθώσεις Σαββατοκύριακου.'],
];

$newListings = 0;
$check = $pdo->prepare('SELECT id FROM job_listings WHERE company_id = ? AND title = ?');
$insL = $pdo->prepare(
    'INSERT INTO job_listings
        (title, company_id, listing_type, transport_type, job_category, job_type,
         required_license, description, salary_min, salary_max, salary_type,
         location, latitude, longitude, experience_years, min_experience,
         adr_certificate, requires_pei, requires_tachograph, operator_license,
         machinery_types, vehicle_type, contact_email, contact_phone,
         is_active, is_urgent, status, created_at, expires_at)
     VALUES (?,?,\'job_offer\',?,?,?,?,?,?,?,\'monthly\',?,?,?,?,?,?,?,?,?,?,?,?,?,1,?,\'active\',?,?)'
);

foreach ($L as $row) {
    $machinery = $row[16] ?? null;
    [$cn, $title, $cat, $transport, $vehicle, $license, $pei, $adr, $tacho, $oper,
        $exp, $salMin, $salMax, $jobType, $urgent, $descr] = $row;

    $companyId = $companyIds[$cn];
    $check->execute([$companyId, $title]);
    if ($check->fetchColumn()) {
        continue;
    }

    $city = $companies[$cn][1];
    [$lat, $lng] = $geo[$city];
    $insL->execute([
        $title, $companyId, $transport, $cat, $jobType, $license, $descr,
        $salMin, $salMax, $city, $lat, $lng, $exp, $exp,
        $adr, $pei, $tacho, $oper, $machinery, $vehicle,
        'info+betaetairia' . $cn . '@thessdrive.gr', '23105551' . str_pad((string) $cn, 2, '0', STR_PAD_LEFT),
        $urgent, $now, $expires,
    ]);
    $newListings++;
}

// ═══ 3. ΟΔΗΓΟΙ ═════════════════════════════════════════════════════════
// [επώνυμο, όνομα, πόλη, εμπειρία(έτη), about, άδειες, adr, tacho, operator]
// άδειες: [type => [pei, λήξη-σε-έτη]] — η αλυσίδα δηλώνεται πλήρης όπως στα
// πραγματικά διπλώματα (κάτοχος CE έχει και B, C).
$D = [
    1 => ['Παπαδόπουλος', 'Γιώργος', 'Θεσσαλονίκη', 12, 'Οδηγός διεθνών μεταφορών με 12 χρόνια στα Βαλκάνια και την Κεντρική Ευρώπη. ΔΟΚΙΜΑΣΤΙΚΟΣ ΛΟΓΑΡΙΑΣΜΟΣ BETA.',
        ['B' => [0, 8], 'C' => [1, 4], 'CE' => [1, 4]], 'Π1', true, null],
    2 => ['Καραγιάννης', 'Νίκος', 'Σέρρες', 8, 'Οδηγός εθνικών μεταφορών, κυρίως αγροτικά προϊόντα και παλέτες. ΔΟΚΙΜΑΣΤΙΚΟΣ ΛΟΓΑΡΙΑΣΜΟΣ BETA.',
        ['B' => [0, 9], 'C' => [1, 3]], null, true, null],
    3 => ['Δημητρίου', 'Άννα', 'Αθήνα', 3, 'Οδηγός διανομών με βαν στο κέντρο της Αθήνας, εμπειρία σε courier. ΔΟΚΙΜΑΣΤΙΚΟΣ ΛΟΓΑΡΙΑΣΜΟΣ BETA.',
        ['B' => [0, 10]], null, false, null],
    4 => ['Μανουσάκης', 'Μιχάλης', 'Ηράκλειο', 10, 'Οδηγός ψυγείων με δεκαετία στη μεταφορά νωπών εντός και εκτός Κρήτης. ΔΟΚΙΜΑΣΤΙΚΟΣ ΛΟΓΑΡΙΑΣΜΟΣ BETA.',
        ['B' => [0, 7], 'C' => [1, 5], 'CE' => [1, 5]], null, true, null],
    5 => ['Οικονόμου', 'Κώστας', 'Ιωάννινα', 15, 'Οδηγός υπεραστικών λεωφορείων, 15 χρόνια σε τακτικές γραμμές Ηπείρου. ΔΟΚΙΜΑΣΤΙΚΟΣ ΛΟΓΑΡΙΑΣΜΟΣ BETA.',
        ['B' => [0, 6], 'D' => [1, 3]], null, true, null],
    6 => ['Βασιλείου', 'Ελένη', 'Ρόδος', 7, 'Οδηγός τουριστικών πούλμαν με άριστα αγγλικά, επτά σεζόν στα Δωδεκάνησα. ΔΟΚΙΜΑΣΤΙΚΟΣ ΛΟΓΑΡΙΑΣΜΟΣ BETA.',
        ['B' => [0, 8], 'D' => [1, 4], 'DE' => [1, 4]], null, true, null],
    7 => ['Τσιτσάνης', 'Βαγγέλης', 'Λάρισα', 9, 'Χειριστής εκσκαφέα και φορτωτή σε χωματουργικά, με δίπλωμα Γ για μεταφορές. ΔΟΚΙΜΑΣΤΙΚΟΣ ΛΟΓΑΡΙΑΣΜΟΣ BETA.',
        ['B' => [0, 9], 'C' => [0, 6]], null, false, ['1', 'A', 6]],
    8 => ['Αντωνόπουλος', 'Σπύρος', 'Πάτρα', 11, 'Οδηγός βυτιοφόρων καυσίμων με ADR δεξαμενών, 11 χρόνια χωρίς συμβάν. ΔΟΚΙΜΑΣΤΙΚΟΣ ΛΟΓΑΡΙΑΣΜΟΣ BETA.',
        ['B' => [0, 5], 'C' => [1, 4], 'CE' => [1, 4]], 'Π2', true, null],
    9 => ['Χατζής', 'Δημήτρης', 'Βόλος', 14, 'Μικτός επαγγελματίας: φορτηγά και λεωφορεία, με ΠΕΙ και στα δύο. ΔΟΚΙΜΑΣΤΙΚΟΣ ΛΟΓΑΡΙΑΣΜΟΣ BETA.',
        ['B' => [0, 7], 'C' => [1, 5], 'D' => [1, 5]], null, true, null],
    10 => ['Νικολαΐδου', 'Μαρία', 'Καβάλα', 1, 'Νέα επαγγελματίας με δίπλωμα Γ1, αναζητά πρώτη σταθερή θέση σε διανομές. ΔΟΚΙΜΑΣΤΙΚΟΣ ΛΟΓΑΡΙΑΣΜΟΣ BETA.',
        ['B' => [0, 11], 'C1' => [1, 6]], null, false, null],
];

$newDrivers = 0;
$insLic = $pdo->prepare(
    'INSERT INTO driver_licenses (driver_id, license_type, has_pei, expiry_date, pei_expiry_c, pei_expiry_d, license_number, is_active)
     VALUES (?,?,?,?,?,?,?,1)'
);

foreach ($D as $n => [$last, $first, $city, $exp, $about, $licenses, $adrType, $hasTacho, $operator]) {
    $email = 'info+betaodigos' . $n . '@thessdrive.gr';
    $stmt = $pdo->prepare('SELECT id FROM drivers WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetchColumn()) {
        continue; // Υπάρχει ήδη — δεν πειράζουμε προφίλ που ίσως δοκιμάζεται.
    }

    [$lat, $lng] = $geo[$city];
    $pdo->prepare(
        'INSERT INTO drivers (email, password, first_name, last_name, phone, city, country,
             about_me, experience_years, years_experience, available_for_work,
             preferred_location, max_distance, latitude, longitude,
             cv_show_photo, cv_show_age, cv_show_phone, cv_show_email, cv_show_rating,
             is_verified, is_active, status, created_at, terms_accepted_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,1,?,?,?,?,1,0,1,1,1,1,1,\'active\',?,?)'
    )->execute([
        $email, $hash, $first, $last, '69700001' . str_pad((string) $n, 2, '0', STR_PAD_LEFT),
        $city, 'Ελλάδα', $about, $exp, $exp, $city, 100, $lat, $lng, $now, $now,
    ]);
    $driverId = (int) $pdo->lastInsertId();
    $newDrivers++;

    foreach ($licenses as $type => [$pei, $years]) {
        $expiry = date('Y-m-d', strtotime("+{$years} years"));
        $peiExpiry = $pei ? date('Y-m-d', strtotime('+3 years')) : null;
        $isC = in_array($type, ['C1', 'C1E', 'C', 'CE'], true);
        $insLic->execute([
            $driverId, $type, $pei, $expiry,
            $pei && $isC ? $peiExpiry : null,
            $pei && !$isC ? $peiExpiry : null,
            'BETA' . str_pad((string) $driverId, 4, '0', STR_PAD_LEFT),
        ]);
    }

    if ($adrType !== null) {
        $pdo->prepare('INSERT INTO driver_adr_certificates (driver_id, adr_type, expiry_date, certificate_number) VALUES (?,?,?,?)')
            ->execute([$driverId, $adrType, date('Y-m-d', strtotime('+3 years')), 'ADR-BETA' . $driverId]);
    }

    if ($hasTacho) {
        $pdo->prepare('INSERT INTO driver_tachograph_cards (driver_id, card_number, issue_date, expiry_date) VALUES (?,?,?,?)')
            ->execute([$driverId, 'GR-BETA' . str_pad((string) $driverId, 6, '0', STR_PAD_LEFT), date('Y-m-d', strtotime('-2 years')), date('Y-m-d', strtotime('+3 years'))]);
    }

    if ($operator !== null) {
        [$spec, $group, $years] = $operator;
        $pdo->prepare('INSERT INTO driver_operator_licenses (driver_id, speciality, group_type, expiry_date, license_number, issue_date) VALUES (?,?,?,?,?,?)')
            ->execute([$driverId, $spec, $group, date('Y-m-d', strtotime("+{$years} years")), 'OP-BETA' . $driverId, date('Y-m-d', strtotime('-5 years'))]);
    }
}

echo "Beta seed: {$newCompanies} νέες εταιρίες, {$newListings} νέες αγγελίες, {$newDrivers} νέοι οδηγοί.\n";
