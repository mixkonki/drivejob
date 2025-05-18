<?php

namespace Drivejob\Core\Exceptions;

/**
 * Εξαίρεση για μη εύρεση πόρου
 */
class NotFoundException extends BaseException
{
    /**
     * Constructor
     *
     * @param string $message Το μήνυμα της εξαίρεσης
     * @param int $code Ο κωδικός της εξαίρεσης
     * @param \Throwable|null $previous Η προηγούμενη εξαίρεση
     * @param array $context Το context της εξαίρεσης
     */
    public function __construct($message = "Ο πόρος δεν βρέθηκε", $code = 404, \Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για μη εύρεση οντότητας
     *
     * @param string $entityType Ο τύπος της οντότητας
     * @param mixed $id Το αναγνωριστικό της οντότητας
     * @param array $context Επιπλέον context
     * @return NotFoundException Η εξαίρεση
     */
    public static function entity($entityType, $id = null, array $context = [])
    {
        $message = "Η οντότητα $entityType";

        if ($id !== null) {
            $message .= " με αναγνωριστικό $id";
            $context['id'] = $id;
        }

        $message .= " δεν βρέθηκε";
        $context['entity_type'] = $entityType;

        return new self($message, 404, null, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για μη εύρεση σελίδας
     *
     * @param string $url Το URL της σελίδας
     * @param array $context Επιπλέον context
     * @return NotFoundException Η εξαίρεση
     */
    public static function page($url = null, array $context = [])
    {
        $message = "Η σελίδα";

        if ($url !== null) {
            $message .= " $url";
            $context['url'] = $url;
        }

        $message .= " δεν βρέθηκε";

        return new self($message, 404, null, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για μη εύρεση αρχείου
     *
     * @param string $path Η διαδρομή του αρχείου
     * @param array $context Επιπλέον context
     * @return NotFoundException Η εξαίρεση
     */
    public static function file($path, array $context = [])
    {
        $message = "Το αρχείο $path δεν βρέθηκε";
        $context['path'] = $path;

        return new self($message, 404, null, $context);
    }
}
