<?php

namespace Drivejob\Core\Exceptions;

/**
 * Εξαίρεση για απαγορευμένη πρόσβαση
 */
class ForbiddenException extends BaseException
{
    /**
     * Constructor
     *
     * @param string $message Το μήνυμα της εξαίρεσης
     * @param int $code Ο κωδικός της εξαίρεσης
     * @param \Throwable|null $previous Η προηγούμενη εξαίρεση
     * @param array $context Το context της εξαίρεσης
     */
    public function __construct($message = "Απαγορεύεται η πρόσβαση", $code = 403, \Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για απαγορευμένη πρόσβαση σε πόρο
     *
     * @param string $resource Ο πόρος
     * @param string $action Η ενέργεια
     * @param array $context Επιπλέον context
     * @return ForbiddenException Η εξαίρεση
     */
    public static function resource($resource, $action = null, array $context = [])
    {
        $message = "Δεν έχετε δικαίωμα";

        if ($action !== null) {
            $message .= " $action";
            $context['action'] = $action;
        }

        $message .= " στον πόρο $resource";
        $context['resource'] = $resource;

        return new self($message, 403, null, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για απαγορευμένη πρόσβαση λόγω ρόλου
     *
     * @param string $requiredRole Ο απαιτούμενος ρόλος
     * @param string $currentRole Ο τρέχων ρόλος
     * @param array $context Επιπλέον context
     * @return ForbiddenException Η εξαίρεση
     */
    public static function role($requiredRole, $currentRole = null, array $context = [])
    {
        $message = "Απαιτείται ο ρόλος $requiredRole";
        $context['required_role'] = $requiredRole;

        if ($currentRole !== null) {
            $message .= " (τρέχων ρόλος: $currentRole)";
            $context['current_role'] = $currentRole;
        }

        return new self($message, 403, null, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για απαγορευμένη πρόσβαση λόγω δικαιώματος
     *
     * @param string $permission Το απαιτούμενο δικαίωμα
     * @param array $context Επιπλέον context
     * @return ForbiddenException Η εξαίρεση
     */
    public static function permission($permission, array $context = [])
    {
        $message = "Απαιτείται το δικαίωμα $permission";
        $context['required_permission'] = $permission;

        return new self($message, 403, null, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για απαγορευμένη πρόσβαση λόγω ιδιοκτησίας
     *
     * @param string $resource Ο πόρος
     * @param mixed $ownerId Το αναγνωριστικό του ιδιοκτήτη
     * @param mixed $userId Το αναγνωριστικό του χρήστη
     * @param array $context Επιπλέον context
     * @return ForbiddenException Η εξαίρεση
     */
    public static function ownership($resource, $ownerId = null, $userId = null, array $context = [])
    {
        $message = "Δεν είστε ο ιδιοκτήτης του πόρου $resource";
        $context['resource'] = $resource;

        if ($ownerId !== null) {
            $context['owner_id'] = $ownerId;
        }

        if ($userId !== null) {
            $context['user_id'] = $userId;
        }

        return new self($message, 403, null, $context);
    }
}
