<?php

namespace Drivejob\Services\Expiry;

use DateTime;

/**
 * Σύνθεση μηνυμάτων (email + SMS) για τις ειδοποιήσεις λήξης αδειών (Πακέτο 5).
 *
 * Τα email HTML ζουν σε αρχεία προτύπων στο templates/emails/
 * (license_expiry_{category}.php). Σειρά αναζήτησης:
 *   1. Πρότυπο κατηγορίας (π.χ. license_expiry_adr_certificate.php)
 *   2. Γενικό πρότυπο (license_expiry_general.php)
 *   3. Ενσωματωμένο μινιμαλιστικό fallback (δεν αποτυγχάνει ποτέ)
 *
 * Τα ~900 γραμμές inline HTML του παλιού service αντικαταστάθηκαν από τα
 * αρχεία προτύπων — ό,τι έλειπε (operator_license, special_license)
 * δημιουργήθηκε ως αρχείο στο Πακέτο 5.
 */
class LicenseExpiryMessageComposer
{
    private string $templatesPath;

    public function __construct(string $templatesPath)
    {
        $this->templatesPath = rtrim($templatesPath, '/\\') . '/';
    }

    // ---- Email -----------------------------------------------------------

    /** Θέμα email ανά κατηγορία. */
    public function emailSubject(string $licenseCategory, array $ctx = []): string
    {
        switch ($licenseCategory) {
            case 'driving_license':
                return 'Ειδοποίηση λήξης άδειας οδήγησης';
            case 'pei':
                return 'Ειδοποίηση λήξης ΠΕΙ κατηγορίας ' . ($ctx['pei_category'] ?? '');
            case 'adr_certificate':
                return 'Ειδοποίηση λήξης πιστοποιητικού ADR - ' . ($ctx['adr_type'] ?? '');
            case 'tachograph_card':
                return 'Ειδοποίηση λήξης κάρτας ψηφιακού ταχογράφου';
            case 'operator_license':
                return 'Ειδοποίηση λήξης άδειας χειριστή μηχανημάτων έργου';
            case 'special_license':
                return 'Ειδοποίηση λήξης ειδικής άδειας - ' . ($ctx['license_type'] ?? '');
            default:
                return 'Ειδοποίηση λήξης άδειας';
        }
    }

    /**
     * Παράγει το HTML του email από το κατάλληλο πρότυπο.
     *
     * @param array $data Μεταβλητές του προτύπου (first_name, expiry_date,
     *                    days_before_expiry + ό,τι ειδικό ανά κατηγορία)
     */
    public function renderEmail(string $licenseCategory, array $data): string
    {
        $data['base_url'] = isset($_SERVER['HTTP_HOST'])
            ? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'])
            : 'https://drivejob.gr';
        $data['year'] = date('Y');

        $candidates = [
            $this->templatesPath . "license_expiry_{$licenseCategory}.php",
            $this->templatesPath . 'license_expiry_general.php',
        ];

        foreach ($candidates as $templateFile) {
            if (is_file($templateFile)) {
                ob_start();
                extract($data);
                include $templateFile;
                return ob_get_clean();
            }
        }

        // Έσχατο fallback — δεν εξαρτάται από το filesystem
        return $this->fallbackEmailHtml(
            $data['first_name'] ?? 'Συνεργάτη',
            $this->categoryDescription($licenseCategory),
            $data['license_type'] ?? ($data['adr_type'] ?? ($data['pei_category'] ?? 'Γενική')),
            $data['expiry_date'] ?? date('Y-m-d'),
            (int) ($data['days_before_expiry'] ?? 0)
        );
    }

    // ---- SMS -------------------------------------------------------------

    /** Κείμενο SMS ανά κατηγορία (στέλνεται ≤15 ημέρες πριν τη λήξη). */
    public function smsMessage(string $licenseCategory, array $ctx, int $daysBefore): string
    {
        $daysText = $daysBefore . ' ' . ($daysBefore == 1 ? 'ημέρα' : 'ημέρες');

        switch ($licenseCategory) {
            case 'driving_license':
                return "DriveJob: Η άδεια οδήγησής σας λήγει σε {$daysText}. Παρακαλούμε ανανεώστε την έγκαιρα.";
            case 'pei':
                return "DriveJob: Το ΠΕΙ κατηγορίας {$ctx['pei_category']} λήγει σε {$daysText}. Παρακαλούμε ανανεώστε το έγκαιρα.";
            case 'adr_certificate':
                return "DriveJob: Το πιστοποιητικό ADR τύπου {$ctx['adr_type']} λήγει σε {$daysText}. Παρακαλούμε ανανεώστε το έγκαιρα.";
            case 'tachograph_card':
                return "DriveJob: Η κάρτα ψηφιακού ταχογράφου σας λήγει σε {$daysText}. Παρακαλούμε ανανεώστε την έγκαιρα.";
            case 'operator_license':
                return "DriveJob: Η άδεια χειριστή μηχανημάτων έργου {$ctx['speciality_name']} λήγει σε {$daysText}. Παρακαλούμε ανανεώστε την έγκαιρα.";
            case 'special_license':
                return "DriveJob: Η ειδική άδεια {$ctx['license_type']} λήγει σε {$daysText}. Παρακαλούμε ανανεώστε την έγκαιρα.";
            default:
                return "DriveJob: Μια άδειά σας λήγει σε {$daysText}. Παρακαλούμε ανανεώστε την έγκαιρα.";
        }
    }

    // ---- Περιγραφές ------------------------------------------------------

    /** Περιγραφή ειδικότητας χειριστή μηχανημάτων έργου. */
    public function operatorSpecialityName(string $specialityId): string
    {
        $specialities = [
            '1' => 'Εργασίες εκσκαφής και χωματουργικές',
            '2' => 'Εργασίες ανύψωσης και μεταφοράς φορτίων',
            '3' => 'Εργασίες οδοστρωσίας',
            '4' => 'Εργασίες εξυπηρέτησης οδών και αεροδρομίων',
            '5' => 'Εργασίες υπόγειων έργων και μεταλλείων',
            '6' => 'Εργασίες έλξης',
            '7' => 'Εργασίες διάτρησης και κοπής εδαφών',
            '8' => 'Ειδικές εργασίες ανύψωσης',
        ];
        return $specialities[$specialityId] ?? "Ειδικότητα {$specialityId}";
    }

    /** Ανθρώπινη περιγραφή κατηγορίας άδειας. */
    public function categoryDescription(string $licenseCategory): string
    {
        $categories = [
            'driving_license' => 'άδεια οδήγησης',
            'pei' => 'Πιστοποιητικό Επαγγελματικής Ικανότητας (ΠΕΙ)',
            'adr_certificate' => 'πιστοποιητικό ADR',
            'tachograph_card' => 'κάρτα ψηφιακού ταχογράφου',
            'operator_license' => 'άδεια χειριστή μηχανημάτων έργου',
            'special_license' => 'ειδική άδεια',
        ];
        return $categories[$licenseCategory] ?? 'άδεια';
    }

    // ---- Fallback --------------------------------------------------------

    private function fallbackEmailHtml(string $firstName, string $categoryDesc, string $licenseType, string $expiryDate, int $daysBeforeExpiry): string
    {
        $formattedDate = (new DateTime($expiryDate))->format('d/m/Y');
        $daysText = ($daysBeforeExpiry == 1) ? 'μία ημέρα' : $daysBeforeExpiry . ' ημέρες';
        $year = date('Y');

        return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Ειδοποίηση Λήξης Άδειας - DriveJob</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
            .header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
            .warning { color: #e74c3c; font-weight: bold; }
            .button { display: inline-block; background-color: #3498db; color: white; padding: 10px 20px;
                      text-decoration: none; border-radius: 5px; margin-top: 20px; }
            .info-box { background-color: #f8f9fa; border-left: 4px solid #3498db; padding: 15px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>DriveJob - Ειδοποίηση Λήξης Άδειας</h1>
        </div>
        <div class='content'>
            <p>Αγαπητέ/ή {$firstName},</p>

            <p>Σας ενημερώνουμε ότι η <strong>{$categoryDesc}</strong> σας <strong>{$licenseType}</strong>
            πρόκειται να λήξει σε <span class='warning'>{$daysText}</span>, στις <strong>{$formattedDate}</strong>.</p>

            <div class='info-box'>
                <h3>Στοιχεία Άδειας</h3>
                <p><strong>Τύπος:</strong> {$categoryDesc} {$licenseType}<br>
                <strong>Ημερομηνία Λήξης:</strong> {$formattedDate}<br>
                <strong>Υπολειπόμενες ημέρες:</strong> {$daysBeforeExpiry}</p>
            </div>

            <p>Παρακαλούμε φροντίστε να ανανεώσετε έγκαιρα την άδειά σας για να αποφύγετε τυχόν προβλήματα
            στην επαγγελματική σας δραστηριότητα.</p>

            <p>Για να ενημερώσετε τα στοιχεία σας στο προφίλ σας στο DriveJob, πατήστε το παρακάτω κουμπί:</p>

            <a href='https://drivejob.gr/drivers/edit-profile' class='button'>Ενημέρωση Προφίλ</a>
            <p style='margin-top: 20px;'>Σας ευχαριστούμε που χρησιμοποιείτε την πλατφόρμα DriveJob.</p>

            <p>Με εκτίμηση,<br>
            Η ομάδα του DriveJob</p>
        </div>
        <div class='footer'>
            <p>Αυτό το email είναι αυτοματοποιημένο. Παρακαλούμε μην απαντήσετε σε αυτό το μήνυμα.</p>
            <p>Αν έχετε οποιαδήποτε απορία, επικοινωνήστε μαζί μας στο <a href='mailto:info@drivejob.gr'>info@drivejob.gr</a>.</p>
            <p>&copy; {$year} DriveJob. Με επιφύλαξη παντός δικαιώματος.</p>
        </div>
    </body>
    </html>
    ";
    }
}
