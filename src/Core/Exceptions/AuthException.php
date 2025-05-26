<?php

namespace Drivejob\Core\Exceptions;

/**
 * Εξαίρεση αυθεντικοποίησης
 * 
 * Χρησιμοποιείται όταν συμβαίνει σφάλμα κατά την αυθεντικοποίηση
 */
class AuthException extends BaseException
{
    /**
     * Ο κωδικός HTTP
     *
     * @var int
     */
    protected $httpCode = 401;

    /**
     * Ο τύπος του σφάλματος
     *
     * @var string
     */
    protected $errorType = 'Authentication Error';

    /**
     * Δημιουργία εξαίρεσης για λήξη συνεδρίας
     *
     * @return self
     */
    public static function sessionExpired(): self
    {
        return new self('Η συνεδρία σας έχει λήξει. Παρακαλώ συνδεθείτε ξανά.');
    }

    /**
     * Δημιουργία εξαίρεσης για μη εξουσιοδοτημένη πρόσβαση
     *
     * @return self
     */
    public static function unauthorized(): self
    {
        return new self('Δεν έχετε δικαίωμα πρόσβασης σε αυτή τη σελίδα.');
    }

    /**
     * Δημιουργία εξαίρεσης για λανθασμένα διαπιστευτήρια
     *
     * @return self
     */
    public static function invalidCredentials(): self
    {
        return new self('Λανθασμένο email ή κωδικός πρόσβασης.');
    }
}
