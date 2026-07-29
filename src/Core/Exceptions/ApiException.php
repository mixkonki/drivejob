<?php

namespace Drivejob\Core\Exceptions;

/**
 * Εξαίρεση API
 * 
 * Χρησιμοποιείται όταν συμβαίνει σφάλμα κατά την επικοινωνία με εξωτερικό API
 */
class ApiException extends BaseException
{
    /**
     * Ο κωδικός HTTP
     *
     * @var int
     */
    protected $httpCode = 500;

    /**
     * Ο τύπος του σφάλματος
     *
     * @var string
     */
    protected $errorType = 'API Error';
}
