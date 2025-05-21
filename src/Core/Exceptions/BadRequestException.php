<?php

namespace Drivejob\Core\Exceptions;

/**
 * Εξαίρεση κακής αίτησης
 * 
 * Χρησιμοποιείται όταν η αίτηση του χρήστη είναι μη έγκυρη
 */
class BadRequestException extends BaseException
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
    protected $errorType = 'Bad Request';
}
