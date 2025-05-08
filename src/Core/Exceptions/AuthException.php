<?php

namespace Drivejob\Core\Exceptions;

/**
 * Εξαίρεση για σφάλματα αυθεντικοποίησης
 */
class AuthException extends BaseException
{
    /**
     * Κωδικοί σφαλμάτων αυθεντικοποίησης
     */
    const ERROR_INVALID_CREDENTIALS = 1;
    const ERROR_ACCOUNT_LOCKED = 2;
    const ERROR_ACCOUNT_NOT_VERIFIED = 3;
    const ERROR_ACCOUNT_DISABLED = 4;
    const ERROR_SESSION_EXPIRED = 5;
    const ERROR_INSUFFICIENT_PERMISSIONS = 6;
    const ERROR_INVALID_TOKEN = 7;
    const ERROR_2FA_REQUIRED = 8;

    /**
     * Constructor
     *
     * @param string $message Το μήνυμα της εξαίρεσης
     * @param int $code Ο κωδικός της εξαίρεσης
     * @param \Throwable|null $previous Η προηγούμενη εξαίρεση
     * @param array $context Το context της εξαίρεσης
     */
    public function __construct($message = "Σφάλμα αυθεντικοποίησης", $code = 401, \Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για μη έγκυρα διαπιστευτήρια
     *
     * @param string $username Το όνομα χρήστη
     * @param array $context Επιπλέον context
     * @return AuthException Η εξαίρεση
     */
    public static function invalidCredentials($username = null, array $context = [])
    {
        $message = "Μη έγκυρα διαπιστευτήρια";
        $context = array_merge(['username' => $username], $context);

        return new self($message, self::ERROR_INVALID_CREDENTIALS, null, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για κλειδωμένο λογαριασμό
     *
     * @param string $username Το όνομα χρήστη
     * @param \DateTime|null $unlockTime Ο χρόνος ξεκλειδώματος
     * @param array $context Επιπλέον context
     * @return AuthException Η εξαίρεση
     */
    public static function accountLocked($username = null, \DateTime $unlockTime = null, array $context = [])
    {
        $message = "Ο λογαριασμός είναι κλειδωμένος";

        if ($unlockTime !== null) {
            $message .= " μέχρι " . $unlockTime->format('d/m/Y H:i:s');
            $context['unlock_time'] = $unlockTime->format('Y-m-d H:i:s');
        }

        $context = array_merge(['username' => $username], $context);

        return new self($message, self::ERROR_ACCOUNT_LOCKED, null, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για μη επαληθευμένο λογαριασμό
     *
     * @param string $username Το όνομα χρήστη
     * @param array $context Επιπλέον context
     * @return AuthException Η εξαίρεση
     */
    public static function accountNotVerified($username = null, array $context = [])
    {
        $message = "Ο λογαριασμός δεν έχει επαληθευτεί";
        $context = array_merge(['username' => $username], $context);

        return new self($message, self::ERROR_ACCOUNT_NOT_VERIFIED, null, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για απενεργοποιημένο λογαριασμό
     *
     * @param string $username Το όνομα χρήστη
     * @param array $context Επιπλέον context
     * @return AuthException Η εξαίρεση
     */
    public static function accountDisabled($username = null, array $context = [])
    {
        $message = "Ο λογαριασμός είναι απενεργοποιημένος";
        $context = array_merge(['username' => $username], $context);

        return new self($message, self::ERROR_ACCOUNT_DISABLED, null, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για ληγμένη συνεδρία
     *
     * @param array $context Επιπλέον context
     * @return AuthException Η εξαίρεση
     */
    public static function sessionExpired(array $context = [])
    {
        $message = "Η συνεδρία έχει λήξει";

        return new self($message, self::ERROR_SESSION_EXPIRED, null, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για ανεπαρκή δικαιώματα
     *
     * @param string $permission Το απαιτούμενο δικαίωμα
     * @param array $context Επιπλέον context
     * @return AuthException Η εξαίρεση
     */
    public static function insufficientPermissions($permission = null, array $context = [])
    {
        $message = "Ανεπαρκή δικαιώματα";

        if ($permission !== null) {
            $message .= " (απαιτείται: $permission)";
            $context['required_permission'] = $permission;
        }

        return new self($message, self::ERROR_INSUFFICIENT_PERMISSIONS, null, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για μη έγκυρο token
     *
     * @param string $tokenType Ο τύπος του token
     * @param array $context Επιπλέον context
     * @return AuthException Η εξαίρεση
     */
    public static function invalidToken($tokenType = null, array $context = [])
    {
        $message = "Μη έγκυρο token";

        if ($tokenType !== null) {
            $message .= " ($tokenType)";
            $context['token_type'] = $tokenType;
        }

        return new self($message, self::ERROR_INVALID_TOKEN, null, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για απαίτηση 2FA
     *
     * @param string $username Το όνομα χρήστη
     * @param array $context Επιπλέον context
     * @return AuthException Η εξαίρεση
     */
    public static function twoFactorAuthRequired($username = null, array $context = [])
    {
        $message = "Απαιτείται επαλήθευση δύο παραγόντων";
        $context = array_merge(['username' => $username], $context);

        return new self($message, self::ERROR_2FA_REQUIRED, null, $context);
    }
}
