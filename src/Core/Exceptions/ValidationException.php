<?php

namespace Drivejob\Core\Exceptions;

/**
 * Εξαίρεση επικύρωσης
 * 
 * Χρησιμοποιείται όταν αποτυγχάνει η επικύρωση δεδομένων
 */
class ValidationException extends BaseException
{
    /**
     * Ο κωδικός HTTP
     *
     * @var int
     */
    protected $httpCode = 400;

    /**
     * Ο τύπος του σφάλματος
     *
     * @var string
     */
    protected $errorType = 'Validation Error';

    /**
     * Τα σφάλματα επικύρωσης ανά πεδίο.
     *
     * Ο constructor του BaseException δέχεται τα σφάλματα ως $context —
     * αυτή η μέθοδος τα επιστρέφει με το όνομα που περιμένουν οι controllers.
     * Όσο έλειπε, κάθε αποτυχία επικύρωσης κατέληγε σε «Call to undefined
     * method» και ο χρήστης έβλεπε 500 αντί για το ποιο πεδίο έφταιγε.
     *
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->getContext() ?? [];
    }
}
