<?php

namespace Drivejob\Services\Score\Collectors;

use PDO;
use Throwable;
use Drivejob\Services\Score\Collector;
use Drivejob\Services\Score\Contribution;

/**
 * Τηλεματική κινητού — ΔΗΛΩΜΕΝΗ ΣΤΟ ΜΗΤΡΩΟ, ΑΝΕΝΕΡΓΗ. (01/09/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ ΥΠΑΡΧΕΙ ΤΟ ΑΡΧΕΙΟ ΑΝ ΔΕΝ ΤΡΕΧΕΙ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Ο πίνακας `driver_telemetry` υπάρχει από την αρχή (avg_speed,
 * harsh_braking, harsh_acceleration, harsh_cornering, score…) και έχει
 * μηδέν γραμμές. Ο collector διαβάζει ό,τι βρει: αν κάποια στιγμή
 * μπουν δεδομένα από οποιαδήποτε πηγή, δουλεύει αμέσως.
 *
 * Στο μητρώο είναι `active => false` ώστε η οθόνη να το δείχνει ως
 * «δεν έχει ενεργοποιηθεί» αντί να το αποσιωπά. Ο οδηγός βλέπει ΟΛΕΣ τις
 * πιθανές πηγές και ποιες λείπουν.
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΟΙ ΟΡΟΙ ΠΡΙΝ ΕΝΕΡΓΟΠΟΙΗΘΕΙ (μη διαπραγματεύσιμοι)
 * ══════════════════════════════════════════════════════════════════════
 *
 * Η ΑΠΔΠΧ δέχεται γεωεντοπισμό για δρομολόγηση και ασφάλεια, ΟΧΙ για
 * αξιολόγηση απόδοσης, και δεν δέχεται τη συγκατάθεση εργαζομένου ως
 * έγκυρη βάση (σχέση εξάρτησης). Το DriveJob ΔΕΝ είναι εργοδότης του
 * οδηγού, άρα η συγκατάθεση μπορεί να είναι έγκυρη — με προϋποθέσεις:
 *
 *   1. Ο οδηγός βλέπει τον βαθμό ΠΡΙΝ αποφασίσει αν θα δημοσιευτεί.
 *   2. Διαγραφή δεδομένων και βαθμού με ένα κουμπί, οποτεδήποτε.
 *   3. ΚΑΜΙΑ αναζήτηση εργοδότη δεν φιλτράρει με «έχει βαθμό οδήγησης» —
 *      αλλιώς η απουσία γίνεται στίγμα και η συγκατάθεση εκβιασμός.
 *   4. Ωμά ίχνη θέσης ΔΕΝ αποθηκεύονται· μόνο συγκεντρωτικά ανά διαδρομή.
 *   5. ΕΑΠΔ (DPIA) πριν από την πρώτη γραμμή παραγωγής.
 *
 * ΚΑΙ Η ΑΚΡΙΒΕΙΑ: επικυρωμένη μελέτη δίνει 96,5% συνολική ακρίβεια στον
 * διαχωρισμό οδηγού/επιβάτη — αλλά ειδικότητα 91,2% με τυπική απόκλιση
 * 14,8%. Για ασφαλιστική με 100.000 πελάτες αυτό εξομαλύνεται· για έναν
 * οδηγό που χάνει δουλειά επειδή ο συνεπιβάτης του φρενάρει απότομα, δεν
 * εξομαλύνεται τίποτα. Γι' αυτό μπαίνει ΤΕΛΕΥΤΑΙΟ, μετά τον ταχογράφο.
 */
final class TelematicsCollector implements Collector
{
    public function source(): string
    {
        return 'telematics';
    }

    public function collect(array $profile, PDO $pdo): array
    {
        $driverId = (int) ($profile['id'] ?? 0);
        if ($driverId <= 0) {
            return [];
        }

        try {
            $stmt = $pdo->prepare(
                'SELECT score, total_distance, harsh_braking, harsh_acceleration,
                        harsh_cornering, date_collected
                 FROM driver_telemetry WHERE driver_id = ?
                 ORDER BY date_collected DESC LIMIT 1'
            );
            $stmt->execute([$driverId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }

        if (!$row || $row['score'] === null) {
            return [];
        }

        $km = (float) ($row['total_distance'] ?? 0);
        // Κάτω από 500 χλμ το δείγμα δεν λέει τίποτα — δείχνεται, δεν μετράει.
        $enough = $km >= 500;

        return [new Contribution(
            source: $this->source(),
            label: 'Μετρημένη οδήγηση (κινητό)',
            points: $enough ? max(0, min(100, (float) $row['score'])) : 0,
            maxPoints: 100,
            detail: $enough
                ? sprintf('%s χλμ καταγεγραμμένα', number_format($km, 0, ',', '.'))
                : sprintf('Μόνο %s χλμ — χρειάζονται τουλάχιστον 500 για αξιόπιστη μέτρηση', number_format($km, 0, ',', '.')),
            occurredAt: $row['date_collected'] ?? null,
        )];
    }
}
