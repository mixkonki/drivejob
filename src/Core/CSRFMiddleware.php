<?php

namespace Drivejob\Core;

class CSRFMiddleware
{
    /**
     * Πεδία που ΔΕΝ επιστρέφουν ποτέ στη φόρμα.
     *
     * Το old_input γράφεται στη συνεδρία και ξαναγεμίζει τη φόρμα. Ένα
     * συνθηματικό εκεί μέσα σημαίνει συνθηματικό σε καθαρό κείμενο στον
     * δίσκο του server, για ώρες.
     */
    private const NEVER_REMEMBER = [
        'password', 'confirm_password', 'password_confirmation',
        'current_password', 'new_password', 'csrf_token',
    ];

    /**
     * Ελέγχει το CSRF token για POST αιτήματα.
     */
    public static function handle()
    {
        Session::start();

        // Παράκαμψη για σύνδεση και endpoints ταυτοποίησης
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (
            strpos($requestUri, 'login') !== false ||
            strpos($requestUri, '/auth/') !== false ||
            strpos($requestUri, 'auth/login') !== false
        ) {
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            return;
        }

        if (!isset($_POST['csrf_token'])) {
            self::reject('missing');
        }

        if (!CSRF::validateToken($_POST['csrf_token'])) {
            self::reject('invalid');
        }
    }

    /**
     * Απόρριψη αιτήματος — ΧΩΡΙΣ να χαθεί η δουλειά του χρήστη.
     *
     * ══════════════════════════════════════════════════════════════════════
     *  ΓΙΑΤΙ ΞΑΝΑΓΡΑΦΤΗΚΕ ΑΥΤΟ
     * ══════════════════════════════════════════════════════════════════════
     *
     * Εδώ υπήρχε:
     *
     *     header("HTTP/1.0 403 Forbidden");
     *     die('Access Forbidden: Invalid CSRF token');
     *
     * Δηλαδή: λευκή σελίδα, αγγλικό μήνυμα, μηδέν εξήγηση, όλα τα
     * συμπληρωμένα πεδία χαμένα, κανένας δρόμος πίσω εκτός από το κουμπί
     * «πίσω» του browser.
     *
     * Και η αιτία που το ενεργοποιούσε ΔΕΝ ήταν επίθεση. Ήταν το εξής:
     * το cookie της συνεδρίας ζούσε 24 ώρες ενώ τα δεδομένα της στον server
     * σβήνονταν στα 24 ΛΕΠΤΑ (βλ. Session::start). Ένας χρήστης που άφηνε
     * τη φόρμα εγγραφής ανοιχτή όσο απαντούσε ένα τηλέφωνο, έχανε τα πάντα.
     *
     * Το CSRF είναι μηχανισμός ασφαλείας και ΜΕΝΕΙ αυστηρός: το αίτημα δεν
     * εκτελείται ποτέ. Αυτό όμως δεν είναι λόγος να τιμωρείται ο χρήστης.
     * Τον γυρίζουμε στη φόρμα, με τα στοιχεία του, με ένα καινούριο token
     * και με μια πρόταση στα ελληνικά που εξηγεί τι συνέβη.
     *
     * Ο επιτιθέμενος δεν κερδίζει τίποτα από αυτό: το αίτημά του
     * εξακολουθεί να μην εκτελείται, και δεν βλέπει ποτέ σελίδα-θύμα —
     * βλέπει τη δική του κενή φόρμα.
     */
    private static function reject(string $reason): void
    {
        Logger::warning('CSRF: αίτημα απορρίφθηκε', [
            'reason' => $reason,
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'has_session_token' => Session::has('csrf_token'),
            'session_id' => session_id(),
        ]);

        // Τα δεδομένα της φόρμας, χωρίς τίποτα μυστικό.
        $safeInput = $_POST;
        foreach (self::NEVER_REMEMBER as $secret) {
            unset($safeInput[$secret]);
        }
        Session::set('old_input', $safeInput);

        /*
         * Το μήνυμα λέει ΤΙ έγινε και ΤΙ να κάνει ο χρήστης. Δεν αναφέρει
         * «CSRF» ούτε «token»: δεν σημαίνουν τίποτα για κάποιον που θέλει
         * απλώς να γραφτεί, και ακούγονται σαν να έφταιξε αυτός.
         */
        Session::set('error_message',
            'Η σελίδα έμεινε ανοιχτή αρκετή ώρα και η φόρμα έληξε για λόγους '
            . 'ασφαλείας. Τα στοιχεία σου διατηρήθηκαν — συμπλήρωσε ξανά το '
            . 'συνθηματικό και πάτα «Εγγραφή».'
        );

        // Νέο token, ώστε η επόμενη προσπάθεια να πετύχει.
        CSRF::generateToken();

        header('Location: ' . self::formUrl(), true, 303);
        exit;
    }

    /**
     * Πού επιστρέφει ο χρήστης.
     *
     * Οι φόρμες μας υποβάλλονται στο ίδιο URL που τις εμφανίζει
     * (/drivers/register → POST /drivers/register), οπότε το τρέχον URI
     * είναι η σωστή διεύθυνση. Το Referer χρησιμοποιείται μόνο ως εφεδρεία
     * και μόνο αν δείχνει στο ίδιο site — αλλιώς θα ήταν ανοιχτή
     * ανακατεύθυνση.
     */
    private static function formUrl(): string
    {
        $base = defined('BASE_URL') ? BASE_URL : '/';
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        if ($uri !== '' && str_starts_with($uri, '/')) {
            return $uri;
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer !== '' && str_starts_with($referer, rtrim($base, '/'))) {
            return $referer;
        }

        return $base;
    }
}
