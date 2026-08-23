<?php

namespace Drivejob\Services;

use PDO;
use Drivejob\Core\Logger;
use Drivejob\Repositories\NotificationRepository;

/**
 * Ειδοποιήσεις για τα γεγονότα της πλατφόρμας — η φωνή που έλειπε.
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  ΤΙ ΔΙΟΡΘΩΝΕΙ
 * ══════════════════════════════════════════════════════════════════════════
 *
 * Το NotificationRepository είχε επτά έτοιμες μεθόδους ειδοποίησης —
 * notifyNewJobApplication, notifyNewJobOffer, notifyJobOfferAccepted… —
 * και ΚΑΜΙΑ δεν καλούνταν από πουθενά. Μηδέν σημεία κλήσης σε όλο τον
 * κώδικα.
 *
 * Πρακτικά: μια εταιρεία έστελνε προσφορά και ο οδηγός δεν το μάθαινε
 * ποτέ, εκτός αν τύχαινε να ξαναμπεί και να κοιτάξει τη σωστή σελίδα.
 * Ένας οδηγός έκανε αίτηση και η εταιρεία το ίδιο. Η πλατφόρμα παρήγαγε
 * γεγονότα και τα κρατούσε μυστικά — ό,τι πιο ακριβό έχει ένα marketplace,
 * η στιγμή που οι δύο πλευρές συναντιούνται, χανόταν στη σιωπή.
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  ΣΧΕΔΙΑΣΤΙΚΕΣ ΑΠΟΦΑΣΕΙΣ
 * ══════════════════════════════════════════════════════════════════════════
 *
 * ΕΝΑ ΣΗΜΕΙΟ ΕΙΣΟΔΟΥ ΑΝΑ ΓΕΓΟΝΟΣ. Ο controller καλεί μία μέθοδο
 * («applicationSubmitted») και ο Notifier αναλαμβάνει και τα δύο κανάλια:
 * εγγραφή στον πίνακα notifications (το καμπανάκι) και email. Αν οι
 * controllers καλούσαν τα κανάλια χωριστά, κάποιο θα ξεχνιόταν — όπως
 * ξεχάστηκαν και τα επτά υπάρχοντα.
 *
 * Η ΕΙΔΟΠΟΙΗΣΗ ΔΕΝ ΡΙΧΝΕΙ ΠΟΤΕ ΤΗΝ ΕΝΕΡΓΕΙΑ. Αν το SMTP είναι κάτω, η
 * αίτηση πρέπει να υποβληθεί κανονικά. Κάθε δημόσια μέθοδος καταπίνει
 * κάθε εξαίρεση και απλώς την καταγράφει. Γι' αυτό και οι controllers
 * την καλούν ΜΕΤΑ την επιτυχή εγγραφή, ποτέ πριν.
 *
 * ΤΑ EMAIL ΔΕΝ ΠΕΡΙΕΧΟΥΝ ΠΡΟΣΩΠΙΚΑ ΔΕΔΟΜΕΝΑ. Ούτε ονόματα οδηγών, ούτε
 * τηλέφωνα, ούτε emails τρίτων — μόνο το γεγονός και σύνδεσμο προς την
 * πλατφόρμα, όπου η ορατότητα κρίνεται από το Visibility με τους
 * κανονικούς κανόνες. Το email είναι το πιο διαρρέον κανάλι που υπάρχει:
 * προωθείται, τυπώνεται, μένει σε ξένα εισερχόμενα για πάντα. Ό,τι
 * κερδίσαμε κρύβοντας τον «Οδηγό #84» στη σελίδα θα το χάναμε γράφοντας
 * το ονοματεπώνυμό του σε ένα μήνυμα.
 */
class Notifier
{
    private PDO $pdo;
    private NotificationRepository $notifications;
    private ?EmailService $email;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->notifications = new NotificationRepository($pdo);

        /*
         * Χωρίς SMTP ρυθμίσεις (τοπικό dev), το email κανάλι απλώς
         * απενεργοποιείται — το καμπανάκι δουλεύει πάντα.
         */
        $this->email = (defined('SMTP_HOST') && SMTP_HOST !== '')
            ? new EmailService(
                SMTP_HOST,
                SMTP_PORT,
                SMTP_USERNAME,
                SMTP_PASSWORD,
                SMTP_FROM_EMAIL,
                SMTP_FROM_NAME,
                defined('EMAIL_DEBUG') ? EMAIL_DEBUG : false
            )
            : null;
    }

    // ═══════════════════════════════════════════════ Αιτήσεις (οδηγός → εταιρεία)

    /** Ο οδηγός υπέβαλε αίτηση — ειδοποιείται η εταιρεία. */
    public function applicationSubmitted(int $applicationId, int $companyId, string $listingTitle): void
    {
        $this->deliver(
            userId: $companyId,
            userType: 'company',
            type: 'new_application',
            title: 'Νέα αίτηση υποψηφίου',
            message: 'Ένας οδηγός υπέβαλε αίτηση στην αγγελία «' . $listingTitle . '».',
            link: 'job-applications/view/' . $applicationId,
            emailIntro: 'Ένας υποψήφιος οδηγός μόλις υπέβαλε αίτηση στην αγγελία σου '
                . '«' . $listingTitle . '». Δες το προφίλ και τα προσόντα του στην πλατφόρμα.'
        );
    }

    /** Η εταιρεία έβαλε τον οδηγό σε προεπιλογή — ανοίγει η επικοινωνία. */
    public function applicationShortlisted(int $applicationId, int $driverId, string $listingTitle): void
    {
        $this->deliver(
            userId: $driverId,
            userType: 'driver',
            type: 'application_shortlisted',
            title: 'Η αίτησή σου προχώρησε',
            message: 'Η αίτησή σου για τη θέση «' . $listingTitle . '» μπήκε στην προεπιλογή.',
            link: 'job-applications/view/' . $applicationId,
            emailIntro: 'Καλά νέα: η αίτησή σου για τη θέση «' . $listingTitle . '» πέρασε '
                . 'στην προεπιλογή. Η εταιρεία μπορεί πλέον να δει τα στοιχεία επικοινωνίας σου '
                . 'και ίσως επικοινωνήσει μαζί σου.'
        );
    }

    /** Πρόσληψη — το καλύτερο νέο που στέλνει η πλατφόρμα. */
    public function applicationHired(int $applicationId, int $driverId, string $listingTitle): void
    {
        $this->deliver(
            userId: $driverId,
            userType: 'driver',
            type: 'application_hired',
            title: 'Η αίτησή σου έγινε δεκτή',
            message: 'Η εταιρεία αποδέχθηκε την αίτησή σου για τη θέση «' . $listingTitle . '».',
            link: 'job-applications/view/' . $applicationId,
            emailIntro: 'Συγχαρητήρια! Η εταιρεία αποδέχθηκε την αίτησή σου για τη θέση '
                . '«' . $listingTitle . '». Μπες στην πλατφόρμα για τα στοιχεία επικοινωνίας '
                . 'και τα επόμενα βήματα.'
        );
    }

    /**
     * Απόρριψη αίτησης — μόνο καμπανάκι, ΟΧΙ email.
     *
     * Η απόρριψη είναι πληροφορία που ο οδηγός πρέπει να δει, όχι είδηση
     * που αξίζει να τον βρει στο κινητό του. Ένα email «απορρίφθηκες»
     * ανάμεσα στα προσωπικά του μηνύματα κάνει την πλατφόρμα δυσάρεστη —
     * και οι απορρίψεις είναι πάντα περισσότερες από τις προσλήψεις.
     */
    public function applicationRejected(int $applicationId, int $driverId, string $listingTitle): void
    {
        $this->deliver(
            userId: $driverId,
            userType: 'driver',
            type: 'application_rejected',
            title: 'Ενημέρωση για την αίτησή σου',
            message: 'Η αίτησή σου για τη θέση «' . $listingTitle . '» δεν προχώρησε.',
            link: 'job-applications/my-applications',
            emailIntro: null
        );
    }

    // ═══════════════════════════════════════════════ Προσφορές (εταιρεία → οδηγός)

    /** Η εταιρεία έστειλε προσφορά — ειδοποιείται ο οδηγός. */
    public function offerSent(int $offerId, int $driverId, string $offerTitle): void
    {
        $this->deliver(
            userId: $driverId,
            userType: 'driver',
            type: 'new_offer',
            title: 'Νέα προσφορά εργασίας',
            message: 'Μια εταιρεία σου έστειλε προσφορά: «' . $offerTitle . '».',
            link: 'job-offers/view/' . $offerId,
            emailIntro: 'Μια εταιρεία είδε την αγγελία σου και σου έστειλε προσφορά εργασίας: '
                . '«' . $offerTitle . '». Δες τους όρους, την αμοιβή και τα συνημμένα στην '
                . 'πλατφόρμα — και αποφάσισε εσύ.'
        );
    }

    /** Ο οδηγός αποδέχθηκε — η εταιρεία μαθαίνει και ξεκλειδώνει η επικοινωνία. */
    public function offerAccepted(int $offerId, int $companyId, string $offerTitle): void
    {
        $this->deliver(
            userId: $companyId,
            userType: 'company',
            type: 'offer_accepted',
            title: 'Η προσφορά σου έγινε δεκτή',
            message: 'Ο οδηγός αποδέχθηκε την προσφορά «' . $offerTitle . '».',
            link: 'job-offers/view/' . $offerId,
            emailIntro: 'Ο οδηγός αποδέχθηκε την προσφορά σου «' . $offerTitle . '». '
                . 'Τα στοιχεία επικοινωνίας του είναι πλέον διαθέσιμα στην πλατφόρμα.'
        );
    }

    /** Ο οδηγός απέρριψε — μόνο καμπανάκι, ίδιο σκεπτικό με την απόρριψη αίτησης. */
    public function offerRejected(int $offerId, int $companyId, string $offerTitle): void
    {
        $this->deliver(
            userId: $companyId,
            userType: 'company',
            type: 'offer_rejected',
            title: 'Ενημέρωση για την προσφορά σου',
            message: 'Ο οδηγός δεν αποδέχθηκε την προσφορά «' . $offerTitle . '».',
            link: 'job-offers/my-offers',
            emailIntro: null
        );
    }

    // ═══════════════════════════════════════════════ Ο μηχανισμός

    /**
     * Παράδοση και στα δύο κανάλια. ΔΕΝ αφήνει τίποτα να διαφύγει προς τα έξω.
     *
     * @param string|null $emailIntro null = χωρίς email (μόνο καμπανάκι)
     */
    private function deliver(
        int $userId,
        string $userType,
        string $type,
        string $title,
        string $message,
        string $link,
        ?string $emailIntro
    ): void {
        // Κανάλι 1: το καμπανάκι — δουλεύει πάντα, και χωρίς SMTP.
        try {
            $this->notifications->createNotification([
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'user_id' => $userId,
                'user_type' => $userType,
                'data' => json_encode(['link' => $link], JSON_UNESCAPED_UNICODE),
                'method' => 'app',
                'sent_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            Logger::error('Αποτυχία εγγραφής ειδοποίησης', [
                'type' => $type,
                'user_id' => $userId,
                'message' => $e->getMessage(),
            ]);
        }

        // Κανάλι 2: email — μόνο αν το γεγονός το δικαιολογεί.
        if ($emailIntro === null || $this->email === null) {
            return;
        }

        try {
            $address = $this->emailFor($userId, $userType);

            if ($address === null) {
                return;
            }

            $url = BASE_URL . $link;

            $body = '<h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>'
                . '<p>' . htmlspecialchars($emailIntro, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p style="text-align:center; margin:24px 0;">'
                . '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" '
                . 'style="background:#b3261e; color:#ffffff; padding:12px 28px; '
                . 'border-radius:6px; text-decoration:none; font-weight:bold;">Άνοιγμα στο DriveJob</a></p>'
                . '<p style="color:#6b7280; font-size:13px;">Αν το κουμπί δεν λειτουργεί, '
                . 'αντίγραψε αυτόν τον σύνδεσμο: <br>'
                . '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</a></p>';

            $this->email->send($address, $title . ' — DriveJob', $body);
        } catch (\Throwable $e) {
            Logger::error('Αποτυχία αποστολής email ειδοποίησης', [
                'type' => $type,
                'user_id' => $userId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Το email του παραλήπτη — από τον σωστό πίνακα ανά ρόλο.
     */
    private function emailFor(int $userId, string $userType): ?string
    {
        $table = $userType === 'company' ? 'companies' : 'drivers';

        $st = $this->pdo->prepare("SELECT email FROM {$table} WHERE id = ? AND is_active = 1");
        $st->execute([$userId]);
        $address = $st->fetchColumn();

        return ($address !== false && filter_var($address, FILTER_VALIDATE_EMAIL))
            ? (string) $address
            : null;
    }
}
