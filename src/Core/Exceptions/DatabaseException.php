<?php

namespace Drivejob\Core\Exceptions;

/**
 * Εξαίρεση βάσης δεδομένων
 * 
 * Χρησιμοποιείται όταν συμβαίνει σφάλμα στη βάση δεδομένων
 */
class DatabaseException extends BaseException
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
    protected $errorType = 'Database Error';
}
