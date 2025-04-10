<?php
// src/Core/Validator.php

namespace Drivejob\Core;

class Validator
{
    private $errors = [];
    private $data;
    
    public function __construct($data)
    {
        $this->data = $data;
    }
    
    /**
     * Ελέγχει αν ένα πεδίο είναι υποχρεωτικό
     */
    public function required($field, $message = null)
    {
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field] = $message ?? "Το πεδίο {$field} είναι υποχρεωτικό.";
        }
        
        return $this;
    }
    
    /**
     * Ελέγχει αν ένα πεδίο είναι έγκυρο email
     */
    public function email($field, $message = null)
    {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?? "Το πεδίο {$field} πρέπει να είναι έγκυρο email.";
        }
        
        return $this;
    }
    
    /**
     * Ελέγχει το ελάχιστο μήκος ενός πεδίου
     */
    public function minLength($field, $length, $message = null)
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field] = $message ?? "Το πεδίο {$field} πρέπει να έχει τουλάχιστον {$length} χαρακτήρες.";
        }
        
        return $this;
    }
    
    /**
     * Ελέγχει το μέγιστο μήκος ενός πεδίου
     */
    public function maxLength($field, $length, $message = null)
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field] = $message ?? "Το πεδίο {$field} πρέπει να έχει το πολύ {$length} χαρακτήρες.";
        }
        
        return $this;
    }
    
    /**
     * Ελέγχει αν ένα πεδίο είναι αριθμός
     */
    public function numeric($field, $message = null)
    {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = $message ?? "Το πεδίο {$field} πρέπει να είναι αριθμός.";
        }
        
        return $this;
    }
    
    /**
     * Ελέγχει αν ένα πεδίο ταιριάζει με ένα άλλο (π.χ. για έλεγχο κωδικού)
     */
    public function matches($field, $matchField, $message = null)
    {
        if (isset($this->data[$field], $this->data[$matchField]) && 
            $this->data[$field] !== $this->data[$matchField]) {
            $this->errors[$field] = $message ?? "Το πεδίο {$field} πρέπει να ταιριάζει με το {$matchField}.";
        }
        
        return $this;
    }
    
    /**
     * Ελέγχει αν ένα πεδίο ταιριάζει με μια έκφραση regex
     */
    public function pattern($field, $pattern, $message = null)
    {
        if (isset($this->data[$field]) && !preg_match($pattern, $this->data[$field])) {
            $this->errors[$field] = $message ?? "Το πεδίο {$field} δεν είναι σε έγκυρη μορφή.";
        }
        
        return $this;
    }
    
    /**
     * Ελέγχει αν το πεδίο είναι μέσα σε ένα σύνολο επιτρεπόμενων τιμών
     */
    public function inList($field, $list, $message = null)
    {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $list)) {
            $this->errors[$field] = $message ?? "Το πεδίο {$field} περιέχει μη αποδεκτή τιμή.";
        }
        
        return $this;
    }
    
    /**
     * Ελέγχει αν η επικύρωση είναι επιτυχής
     */
    public function isValid()
    {
        return empty($this->errors);
    }
    
    /**
     * Επιστρέφει τα σφάλματα επικύρωσης
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Επιστρέφει το μήνυμα σφάλματος για ένα συγκεκριμένο πεδίο
     */
    public function getError($field)
    {
        return $this->errors[$field] ?? null;
    }
    
    /**
     * Επιστρέφει τα επικυρωμένα δεδομένα
     */
    public function getValidData()
    {
        return $this->data;
    }
    
    /**
     * Καθαρίζει τα δεδομένα από επικίνδυνους χαρακτήρες
     */
    public static function sanitize($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitize($value);
            }
        } else {
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        
        return $data;
    }
}