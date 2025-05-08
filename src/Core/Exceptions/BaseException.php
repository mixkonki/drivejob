<?php

namespace Drivejob\Core\Exceptions;

/**
 * Βασική κλάση εξαίρεσης για την εφαρμογή
 * 
 * Επεκτείνει την κλάση Exception της PHP και προσθέτει
 * υποστήριξη για context και άλλες χρήσιμες λειτουργίες.
 */
class BaseException extends \Exception
{
    /**
     * @var array Το context της εξαίρεσης
     */
    protected $context;

    /**
     * Constructor
     *
     * @param string $message Το μήνυμα της εξαίρεσης
     * @param int $code Ο κωδικός της εξαίρεσης
     * @param \Throwable|null $previous Η προηγούμενη εξαίρεση
     * @param array $context Το context της εξαίρεσης
     */
    public function __construct($message = "", $code = 0, \Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Επιστρέφει το context της εξαίρεσης
     *
     * @return array Το context της εξαίρεσης
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Προσθέτει δεδομένα στο context της εξαίρεσης
     *
     * @param string $key Το κλειδί
     * @param mixed $value Η τιμή
     * @return $this
     */
    public function addContext($key, $value)
    {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * Επιστρέφει μια αναπαράσταση της εξαίρεσης σε μορφή πίνακα
     *
     * @return array Η εξαίρεση σε μορφή πίνακα
     */
    public function toArray()
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
            'context' => $this->getContext()
        ];
    }

    /**
     * Καταγράφει την εξαίρεση στο σύστημα καταγραφής
     *
     * @return void
     */
    public function log()
    {
        if (class_exists('\\Drivejob\\Core\\Logger')) {
            \Drivejob\Core\Logger::error($this->getMessage(), $this->toArray());
        } else {
            error_log($this->getMessage() . ' in ' . $this->getFile() . ' on line ' . $this->getLine());
        }
    }
}
