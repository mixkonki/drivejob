<?php

namespace Drivejob\Core\Exceptions;

use Exception;
use Drivejob\Core\Logger;

/**
 * Βασική κλάση εξαίρεσης
 * 
 * Όλες οι εξαιρέσεις της εφαρμογής κληρονομούν από αυτή την κλάση
 */
class BaseException extends Exception
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

    /**
     * Το πλαίσιο του σφάλματος
     *
     * @var array|null
     */
    protected $context = null;

    /**
     * Constructor
     *
     * @param string $message Το μήνυμα του σφάλματος
     * @param array|null $context Το πλαίσιο του σφάλματος
     * @param int $code Ο κωδικός του σφάλματος
     * @param Exception|null $previous Η προηγούμενη εξαίρεση
     */
    public function __construct(string $message = '', array $context = null, int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Επιστρέφει τον κωδικό HTTP
     *
     * @return int Ο κωδικός HTTP
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * Επιστρέφει τον τύπο του σφάλματος
     *
     * @return string Ο τύπος του σφάλματος
     */
    public function getErrorType(): string
    {
        return $this->errorType;
    }

    /**
     * Επιστρέφει το πλαίσιο του σφάλματος
     *
     * @return array|null Το πλαίσιο του σφάλματος
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /**
     * Καταγράφει την εξαίρεση στο log
     *
     * @return void
     */
    public function log(): void
    {
        $logData = [
            'type' => $this->errorType,
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString()
        ];

        if ($this->context !== null) {
            $logData['context'] = $this->context;
        }

        Logger::error($this->getMessage(), $logData);
    }
}
