<?php

namespace Drivejob\Core\Exceptions;

/**
 * Εξαίρεση αρχείου
 * 
 * Χρησιμοποιείται όταν συμβαίνει σφάλμα κατά τη διαχείριση αρχείων
 */
class FileException extends BaseException
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
    protected $errorType = 'File Error';
}
