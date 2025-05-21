<?php

namespace Drivejob\Core\Exceptions;

/**
 * Εξαίρεση μη εύρεσης
 * 
 * Χρησιμοποιείται όταν δεν βρίσκεται ένας πόρος
 */
class NotFoundException extends BaseException
{
    /**
     * Ο κωδικός HTTP
     *
     * @var int
     */
    protected $httpCode = 404;

    /**
     * Ο τύπος του σφάλματος
     *
     * @var string
     */
    protected $errorType = 'Not Found';
}
