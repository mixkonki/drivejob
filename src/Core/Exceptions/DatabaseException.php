<?php

namespace Drivejob\Core\Exceptions;

/**
 * Εξαίρεση βάσης δεδομένων
 * 
 * Χρησιμοποιείται όταν συμβαίνει σφάλμα στη βάση δεδομένων
 */
class DatabaseException extends BaseException
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
    protected $errorType = 'Database Error';

    /**
     * Δημιουργία DatabaseException από PDOException
     *
     * @param \PDOException $e
     * @return self
     */
    public static function fromPDOException(\PDOException $e): self
    {
        $message = 'Database error: ' . $e->getMessage();

        // Αν είμαστε σε development mode, προσθέτουμε περισσότερες πληροφορίες
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            $message .= ' (Code: ' . $e->getCode() . ')';
        }

        // Δημιουργία context array με πληροφορίες για το σφάλμα
        $context = [
            'sql_state' => $e->errorInfo[0] ?? null,
            'driver_code' => $e->errorInfo[1] ?? null,
            'driver_message' => $e->errorInfo[2] ?? null,
        ];

        return new self($message, $context, (int)$e->getCode(), $e);
    }
}
