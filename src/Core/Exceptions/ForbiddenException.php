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

    /**
     * Δημιουργεί εξαίρεση για λάθος ρόλο
     *
     * @param string $requiredRole Ο απαιτούμενος ρόλος
     * @param string $userRole Ο ρόλος του χρήστη
     * @return self
     */
    public static function role($requiredRole, $userRole)
    {
        $message = "Δεν έχετε τα απαραίτητα δικαιώματα για αυτή την ενέργεια. ";
        $message .= "Απαιτείται ρόλος: {$requiredRole}, ο ρόλος σας: {$userRole}";

        return new self($message);
    }

    /**
     * Δημιουργεί εξαίρεση για λάθος ιδιοκτησία
     *
     * @param string $resourceType Ο τύπος του πόρου
     * @param int $ownerId Το ID του ιδιοκτήτη
     * @param int $userId Το ID του χρήστη
     * @return self
     */
    public static function ownership($resourceType, $ownerId, $userId)
    {
        $message = "Δεν έχετε δικαίωμα πρόσβασης σε αυτόν τον πόρο ({$resourceType}). ";
        $message .= "Ιδιοκτήτης: {$ownerId}, εσείς: {$userId}";

        return new self($message);
    }
}
