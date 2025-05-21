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
}
