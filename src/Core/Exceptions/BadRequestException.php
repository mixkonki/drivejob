<?php

namespace Drivejob\Core\Exceptions;

/**
 * Εξαίρεση για μη έγκυρο αίτημα
 */
class BadRequestException extends BaseException
{
    /**
     * Constructor
     *
     * @param string $message Το μήνυμα της εξαίρεσης
     * @param int $code Ο κωδικός της εξαίρεσης
     * @param \Throwable|null $previous Η προηγούμενη εξαίρεση
     * @param array $context Το context της εξαίρεσης
     */
    public function __construct($message = "Μη έγκυρο αίτημα", $code = 400, \Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για μη έγκυρη παράμετρο
     *
     * @param string $parameter Η παράμετρος
     * @param string $reason Ο λόγος
     * @param array $context Επιπλέον context
     * @return BadRequestException Η εξαίρεση
     */
    public static function invalidParameter($parameter, $reason = null, array $context = [])
    {
        $message = "Μη έγκυρη παράμετρος: $parameter";
        $context['parameter'] = $parameter;

        if ($reason !== null) {
            $message .= " ($reason)";
            $context['reason'] = $reason;
        }

        return new self($message, 400, null, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για απούσα παράμετρο
     *
     * @param string $parameter Η παράμετρος
     * @param array $context Επιπλέον context
     * @return BadRequestException Η εξαίρεση
     */
    public static function missingParameter($parameter, array $context = [])
    {
        $message = "Απούσα παράμετρος: $parameter";
        $context['parameter'] = $parameter;

        return new self($message, 400, null, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για μη έγκυρο αναγνωριστικό
     *
     * @param string $id Το αναγνωριστικό
     * @param array $context Επιπλέον context
     * @return BadRequestException Η εξαίρεση
     */
    public static function invalidId($id, array $context = [])
    {
        $message = "Μη έγκυρο αναγνωριστικό: $id";
        $context['id'] = $id;

        return new self($message, 400, null, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για μη έγκυρο token
     *
     * @param string $tokenType Ο τύπος του token
     * @param array $context Επιπλέον context
     * @return BadRequestException Η εξαίρεση
     */
    public static function invalidToken($tokenType, array $context = [])
    {
        $message = "Μη έγκυρο token: $tokenType";
        $context['token_type'] = $tokenType;

        return new self($message, 400, null, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για μη έγκυρη μορφή δεδομένων
     *
     * @param string $format Η μορφή
     * @param array $context Επιπλέον context
     * @return BadRequestException Η εξαίρεση
     */
    public static function invalidFormat($format, array $context = [])
    {
        $message = "Μη έγκυρη μορφή δεδομένων: $format";
        $context['format'] = $format;

        return new self($message, 400, null, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για μη έγκυρη ενέργεια
     *
     * @param string $action Η ενέργεια
     * @param string $reason Ο λόγος
     * @param array $context Επιπλέον context
     * @return BadRequestException Η εξαίρεση
     */
    public static function invalidAction($action, $reason = null, array $context = [])
    {
        $message = "Μη έγκυρη ενέργεια: $action";
        $context['action'] = $action;

        if ($reason !== null) {
            $message .= " ($reason)";
            $context['reason'] = $reason;
        }

        return new self($message, 400, null, $context);
    }
}
