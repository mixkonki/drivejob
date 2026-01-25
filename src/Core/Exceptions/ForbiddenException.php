<?php

namespace Drivejob\Core\Exceptions;

/**
 * Εξαίρεση απαγορευμένης πρόσβασης
 * 
 * Χρησιμοποιείται όταν ο χρήστης δεν έχει δικαίωμα πρόσβασης σε έναν πόρο
 */
class ForbiddenException extends BaseException
{
    /**
     * Ο κωδικός HTTP
     *
     * @var int
     */
    protected $httpCode = 403;

    /**
     * Ο τύπος του σφάλματος
     *
     * @var string
     */
    protected $errorType = 'Forbidden';
}
