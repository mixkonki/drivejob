<?php

namespace Drivejob\Core\Exceptions;

/**
 * Εξαίρεση σφάλματος διακομιστή
 * 
 * Χρησιμοποιείται όταν συμβαίνει ένα εσωτερικό σφάλμα στον διακομιστή
 */
class ServerErrorException extends BaseException
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
    protected $errorType = 'Server Error';
}
