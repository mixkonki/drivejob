<?php

namespace Drivejob\Core\Exceptions;

/**
 * Εξαίρεση για σφάλματα βάσης δεδομένων
 */
class DatabaseException extends BaseException
{
    /**
     * @var string Το SQL ερώτημα που προκάλεσε το σφάλμα
     */
    protected $query;

    /**
     * @var array Οι παράμετροι του ερωτήματος
     */
    protected $params;

    /**
     * Constructor
     *
     * @param string $message Το μήνυμα της εξαίρεσης
     * @param int $code Ο κωδικός της εξαίρεσης
     * @param \Throwable|null $previous Η προηγούμενη εξαίρεση
     * @param array $context Το context της εξαίρεσης
     * @param string $query Το SQL ερώτημα που προκάλεσε το σφάλμα
     * @param array $params Οι παράμετροι του ερωτήματος
     */
    public function __construct($message = "", $code = 0, \Throwable $previous = null, array $context = [], $query = null, array $params = [])
    {
        parent::__construct($message, $code, $previous, $context);
        $this->query = $query;
        $this->params = $params;

        // Προσθήκη του ερωτήματος και των παραμέτρων στο context
        if ($query !== null) {
            $this->addContext('query', $query);
        }

        if (!empty($params)) {
            $this->addContext('params', $params);
        }
    }

    /**
     * Επιστρέφει το SQL ερώτημα που προκάλεσε το σφάλμα
     *
     * @return string|null Το SQL ερώτημα
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Επιστρέφει τις παραμέτρους του ερωτήματος
     *
     * @return array Οι παράμετροι του ερωτήματος
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Δημιουργεί μια εξαίρεση από ένα PDOException
     *
     * @param \PDOException $e Το PDOException
     * @param string $query Το SQL ερώτημα που προκάλεσε το σφάλμα
     * @param array $params Οι παράμετροι του ερωτήματος
     * @return DatabaseException Η νέα εξαίρεση
     */
    public static function fromPDOException(\PDOException $e, $query = null, array $params = [])
    {
        return new self(
            $e->getMessage(),
            (int) $e->getCode(),
            $e,
            [
                'driver' => $e->errorInfo[0] ?? null,
                'errorCode' => $e->errorInfo[1] ?? null,
                'errorInfo' => $e->errorInfo[2] ?? null
            ],
            $query,
            $params
        );
    }
}
