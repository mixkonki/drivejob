<?php

namespace Drivejob\Core\Exceptions;

/**
 * Εξαίρεση επικύρωσης
 * 
 * Χρησιμοποιείται όταν αποτυγχάνει η επικύρωση δεδομένων
 */
class ValidationException extends BaseException
{
    /**
     * Ο κωδικός HTTP
     *
     * @var int
     */
    protected $httpCode = 400;

    /**
     * Ο τύπος του σφάλματος
     *
     * @var string
     */
    protected $errorType = 'Validation Error';
}
