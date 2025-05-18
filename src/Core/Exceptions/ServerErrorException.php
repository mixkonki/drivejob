<?php

namespace Drivejob\Core\Exceptions;

/**
 * Εξαίρεση για σφάλματα διακομιστή
 */
class ServerErrorException extends BaseException
{
    /**
     * Constructor
     *
     * @param string $message Το μήνυμα της εξαίρεσης
     * @param int $code Ο κωδικός της εξαίρεσης
     * @param \Throwable|null $previous Η προηγούμενη εξαίρεση
     * @param array $context Το context της εξαίρεσης
     */
    public function __construct($message = "Σφάλμα διακομιστή", $code = 500, \Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για εσωτερικό σφάλμα
     *
     * @param string $message Το μήνυμα
     * @param \Throwable|null $previous Η προηγούμενη εξαίρεση
     * @param array $context Επιπλέον context
     * @return ServerErrorException Η εξαίρεση
     */
    public static function internal($message = "Εσωτερικό σφάλμα διακομιστή", \Throwable $previous = null, array $context = [])
    {
        return new self($message, 500, $previous, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για μη υλοποιημένη λειτουργία
     *
     * @param string $feature Η λειτουργία
     * @param array $context Επιπλέον context
     * @return ServerErrorException Η εξαίρεση
     */
    public static function notImplemented($feature, array $context = [])
    {
        $message = "Η λειτουργία $feature δεν έχει υλοποιηθεί";
        $context['feature'] = $feature;

        return new self($message, 501, null, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για μη διαθέσιμη υπηρεσία
     *
     * @param string $service Η υπηρεσία
     * @param string $reason Ο λόγος
     * @param array $context Επιπλέον context
     * @return ServerErrorException Η εξαίρεση
     */
    public static function serviceUnavailable($service, $reason = null, array $context = [])
    {
        $message = "Η υπηρεσία $service δεν είναι διαθέσιμη";
        $context['service'] = $service;

        if ($reason !== null) {
            $message .= " ($reason)";
            $context['reason'] = $reason;
        }

        return new self($message, 503, null, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για σφάλμα βάσης δεδομένων
     *
     * @param string $message Το μήνυμα
     * @param \Throwable|null $previous Η προηγούμενη εξαίρεση
     * @param array $context Επιπλέον context
     * @return ServerErrorException Η εξαίρεση
     */
    public static function database($message = "Σφάλμα βάσης δεδομένων", \Throwable $previous = null, array $context = [])
    {
        return new self($message, 500, $previous, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για σφάλμα εξωτερικής υπηρεσίας
     *
     * @param string $service Η υπηρεσία
     * @param string $message Το μήνυμα
     * @param \Throwable|null $previous Η προηγούμενη εξαίρεση
     * @param array $context Επιπλέον context
     * @return ServerErrorException Η εξαίρεση
     */
    public static function externalService($service, $message = null, \Throwable $previous = null, array $context = [])
    {
        $errorMessage = "Σφάλμα στην εξωτερική υπηρεσία $service";
        $context['service'] = $service;

        if ($message !== null) {
            $errorMessage .= ": $message";
            $context['error_message'] = $message;
        }

        return new self($errorMessage, 500, $previous, $context);
    }

    /**
     * Δημιουργεί μια εξαίρεση για σφάλμα αρχείου
     *
     * @param string $path Η διαδρομή του αρχείου
     * @param string $operation Η λειτουργία
     * @param string $message Το μήνυμα
     * @param array $context Επιπλέον context
     * @return ServerErrorException Η εξαίρεση
     */
    public static function file($path, $operation, $message = null, array $context = [])
    {
        $errorMessage = "Σφάλμα $operation αρχείου $path";
        $context['path'] = $path;
        $context['operation'] = $operation;

        if ($message !== null) {
            $errorMessage .= ": $message";
            $context['error_message'] = $message;
        }

        return new self($errorMessage, 500, null, $context);
    }
}
