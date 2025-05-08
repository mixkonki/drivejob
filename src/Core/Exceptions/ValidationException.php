<?php

namespace Drivejob\Core\Exceptions;

/**
 * Εξαίρεση για σφάλματα επικύρωσης
 */
class ValidationException extends BaseException
{
    /**
     * @var array Τα σφάλματα επικύρωσης
     */
    protected $errors;

    /**
     * Constructor
     *
     * @param string $message Το μήνυμα της εξαίρεσης
     * @param array $errors Τα σφάλματα επικύρωσης
     * @param int $code Ο κωδικός της εξαίρεσης
     * @param \Throwable|null $previous Η προηγούμενη εξαίρεση
     * @param array $context Το context της εξαίρεσης
     */
    public function __construct($message = "Σφάλματα επικύρωσης", array $errors = [], $code = 422, \Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
        $this->errors = $errors;
        $this->addContext('errors', $errors);
    }

    /**
     * Επιστρέφει τα σφάλματα επικύρωσης
     *
     * @return array Τα σφάλματα επικύρωσης
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Προσθέτει ένα σφάλμα επικύρωσης
     *
     * @param string $field Το πεδίο
     * @param string $message Το μήνυμα σφάλματος
     * @return $this
     */
    public function addError($field, $message)
    {
        $this->errors[$field] = $message;
        $this->addContext('errors', $this->errors);
        return $this;
    }

    /**
     * Ελέγχει αν υπάρχουν σφάλματα επικύρωσης
     *
     * @return bool Αν υπάρχουν σφάλματα
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Επιστρέφει το πρώτο σφάλμα επικύρωσης
     *
     * @return string|null Το πρώτο σφάλμα επικύρωσης
     */
    public function getFirstError()
    {
        if (empty($this->errors)) {
            return null;
        }

        return reset($this->errors);
    }

    /**
     * Επιστρέφει τα σφάλματα επικύρωσης ως HTML
     *
     * @return string Τα σφάλματα επικύρωσης ως HTML
     */
    public function getErrorsAsHtml()
    {
        if (empty($this->errors)) {
            return '';
        }

        $html = '<ul class="validation-errors">';
        foreach ($this->errors as $field => $message) {
            $html .= '<li data-field="' . htmlspecialchars($field) . '">' . htmlspecialchars($message) . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * Αποθηκεύει τα σφάλματα επικύρωσης στο session
     *
     * @return void
     */
    public function storeErrorsInSession()
    {
        if (class_exists('\\Drivejob\\Core\\Session')) {
            \Drivejob\Core\Session::set('errors', $this->errors);
            \Drivejob\Core\Session::set('old_input', $_POST);
        }
    }
}
