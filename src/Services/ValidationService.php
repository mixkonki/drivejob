<?php

namespace Drivejob\Services;

/**
 * Centralized Validation Service
 * 
 * Παρέχει centralized validation logic για το DriveJob project
 * Εξαλείφει code duplication και παρέχει Greek-specific validations
 * 
 * @package Drivejob\Services
 * @version 1.0.0
 * @author DriveJob Team
 */
class ValidationService
{
    /**
     * Validates email address
     * 
     * @param string $email The email to validate
     * @return bool True if valid, false otherwise
     */
    public static function validateEmail(string $email): bool
    {
        if (empty($email)) {
            return false;
        }

        // Sanitize first
        $sanitized = filter_var($email, FILTER_SANITIZE_EMAIL);

        // Validate
        return filter_var($sanitized, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validates and sanitizes email address
     * 
     * @param string $email The email to validate and sanitize
     * @return string|false Sanitized email if valid, false otherwise
     */
    public static function sanitizeAndValidateEmail(string $email)
    {
        if (empty($email)) {
            return false;
        }

        $sanitized = filter_var($email, FILTER_SANITIZE_EMAIL);

        if (filter_var($sanitized, FILTER_VALIDATE_EMAIL)) {
            return $sanitized;
        }

        return false;
    }

    /**
     * Validates Greek mobile phone number
     * 
     * Accepts formats:
     * - 69XXXXXXXX (10 digits starting with 69)
     * - +3069XXXXXXXX (with country code)
     * - 003069XXXXXXXX (with 00 prefix)
     * - 6 9XX XXX XXX (with spaces)
     * 
     * @param string $phone The phone number to validate
     * @return bool True if valid Greek mobile, false otherwise
     */
    public static function validateGreekPhone(string $phone): bool
    {
        if (empty($phone)) {
            return false;
        }

        // Remove spaces, dashes, parentheses
        $cleaned = preg_replace('/[\s\-\(\)]/', '', $phone);

        // Pattern 1: 69XXXXXXXX (10 digits starting with 69)
        if (preg_match('/^69\d{8}$/', $cleaned)) {
            return true;
        }

        // Pattern 2: +3069XXXXXXXX (with +30 country code)
        if (preg_match('/^\+3069\d{8}$/', $cleaned)) {
            return true;
        }

        // Pattern 3: 003069XXXXXXXX (with 0030 prefix)
        if (preg_match('/^003069\d{8}$/', $cleaned)) {
            return true;
        }

        return false;
    }

    /**
     * Normalizes Greek phone number to standard format (69XXXXXXXX)
     * 
     * @param string $phone The phone number to normalize
     * @return string|false Normalized phone (69XXXXXXXX) or false if invalid
     */
    public static function normalizeGreekPhone(string $phone)
    {
        if (!self::validateGreekPhone($phone)) {
            return false;
        }

        // Remove spaces, dashes, parentheses
        $cleaned = preg_replace('/[\s\-\(\)]/', '', $phone);

        // Remove country code if present
        $cleaned = preg_replace('/^(\+30|0030)/', '', $cleaned);

        // Should now be 69XXXXXXXX
        if (preg_match('/^69\d{8}$/', $cleaned)) {
            return $cleaned;
        }

        return false;
    }

    /**
     * Validates Greek AFM (Tax Identification Number)
     * 
     * AFM is 9 digits with a specific checksum algorithm
     * 
     * @param string $afm The AFM to validate
     * @return bool True if valid AFM, false otherwise
     */
    public static function validateAFM(string $afm): bool
    {
        if (empty($afm)) {
            return false;
        }

        // Remove spaces
        $afm = preg_replace('/\s/', '', $afm);

        // Must be exactly 9 digits
        if (!preg_match('/^\d{9}$/', $afm)) {
            return false;
        }

        // Validate checksum using Luhn-like algorithm for Greek AFM
        return self::validateAFMChecksum($afm);
    }

    /**
     * Validates AFM checksum
     * 
     * Greek AFM uses a weighted sum algorithm:
     * Each digit is multiplied by 2^(8-position) and summed
     * The last digit is the checksum
     * 
     * @param string $afm The 9-digit AFM
     * @return bool True if checksum is valid
     */
    private static function validateAFMChecksum(string $afm): bool
    {
        $sum = 0;

        // Calculate weighted sum for first 8 digits
        for ($i = 0; $i < 8; $i++) {
            $digit = (int)$afm[$i];
            $weight = pow(2, 8 - $i);
            $sum += $digit * $weight;
        }

        // Calculate checksum (last digit)
        $checksum = $sum % 11;

        // If checksum is 10, it becomes 0
        if ($checksum === 10) {
            $checksum = 0;
        }

        // Compare with the last digit
        return $checksum === (int)$afm[8];
    }

    /**
     * Validates Greek AMKA (Social Security Number)
     * 
     * AMKA is 11 digits: DDMMYYXXXXX
     * - First 6 digits: Date of birth (DDMMYY)
     * - Next 4 digits: Sequential number
     * - Last digit: Checksum
     * 
     * @param string $amka The AMKA to validate
     * @return bool True if valid AMKA, false otherwise
     */
    public static function validateAMKA(string $amka): bool
    {
        if (empty($amka)) {
            return false;
        }

        // Remove spaces
        $amka = preg_replace('/\s/', '', $amka);

        // Must be exactly 11 digits
        if (!preg_match('/^\d{11}$/', $amka)) {
            return false;
        }

        // Validate date part (DDMMYY)
        $day = (int)substr($amka, 0, 2);
        $month = (int)substr($amka, 2, 2);
        $year = (int)substr($amka, 4, 2);

        // Basic date validation
        if ($day < 1 || $day > 31 || $month < 1 || $month > 12) {
            return false;
        }

        // Validate checksum
        return self::validateAMKAChecksum($amka);
    }

    /**
     * Validates AMKA checksum
     * 
     * AMKA checksum algorithm:
     * Sum of (digit * (11 - position)) for first 10 digits
     * Checksum = (sum % 11) % 10
     * 
     * @param string $amka The 11-digit AMKA
     * @return bool True if checksum is valid
     */
    private static function validateAMKAChecksum(string $amka): bool
    {
        $sum = 0;

        // Calculate weighted sum for first 10 digits
        for ($i = 0; $i < 10; $i++) {
            $digit = (int)$amka[$i];
            $weight = 11 - $i;
            $sum += $digit * $weight;
        }

        // Calculate checksum
        $checksum = ($sum % 11) % 10;

        // Compare with the last digit
        return $checksum === (int)$amka[10];
    }

    /**
     * Validates Greek license plate
     * 
     * Formats:
     * - XXX-9999 (3 letters, dash, 4 numbers) - Old format
     * - XXX9999 (3 letters, 4 numbers) - Old format without dash
     * - XXX-9999 (New format, similar)
     * 
     * @param string $plate The license plate to validate
     * @return bool True if valid Greek license plate
     */
    public static function validateGreekLicensePlate(string $plate): bool
    {
        if (empty($plate)) {
            return false;
        }

        // Remove spaces
        $plate = preg_replace('/\s/', '', $plate);
        $plate = strtoupper($plate);

        // Pattern 1: XXX-9999 or XXX9999
        if (preg_match('/^[A-Z]{3}-?\d{4}$/', $plate)) {
            return true;
        }

        // Pattern 2: XX-99999 (motorcycle format)
        if (preg_match('/^[A-Z]{2}-?\d{5}$/', $plate)) {
            return true;
        }

        return false;
    }

    /**
     * Validates Greek postal code
     * 
     * Greek postal codes are 5 digits: XXXXX
     * First 3 digits indicate area, last 2 are sequential
     * 
     * @param string $postalCode The postal code to validate
     * @return bool True if valid Greek postal code
     */
    public static function validateGreekPostalCode(string $postalCode): bool
    {
        if (empty($postalCode)) {
            return false;
        }

        // Remove spaces
        $postalCode = preg_replace('/\s/', '', $postalCode);

        // Must be exactly 5 digits
        return preg_match('/^\d{5}$/', $postalCode) === 1;
    }

    /**
     * Validates URL
     * 
     * @param string $url The URL to validate
     * @return bool True if valid URL
     */
    public static function validateURL(string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validates date in Y-m-d format
     * 
     * @param string $date The date to validate
     * @return bool True if valid date
     */
    public static function validateDate(string $date): bool
    {
        if (empty($date)) {
            return false;
        }

        $parts = explode('-', $date);

        if (count($parts) !== 3) {
            return false;
        }

        return checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
    }

    /**
     * Validates that a date is in the past
     * 
     * @param string $date The date to validate (Y-m-d format)
     * @return bool True if date is in the past
     */
    public static function isDateInPast(string $date): bool
    {
        if (!self::validateDate($date)) {
            return false;
        }

        $timestamp = strtotime($date);
        return $timestamp < time();
    }

    /**
     * Validates that a date is in the future
     * 
     * @param string $date The date to validate (Y-m-d format)
     * @return bool True if date is in the future
     */
    public static function isDateInFuture(string $date): bool
    {
        if (!self::validateDate($date)) {
            return false;
        }

        $timestamp = strtotime($date);
        return $timestamp > time();
    }

    /**
     * Validates age (must be at least minimum age)
     * 
     * @param string $birthDate Birth date in Y-m-d format
     * @param int $minAge Minimum required age
     * @return bool True if age requirement is met
     */
    public static function validateMinAge(string $birthDate, int $minAge = 18): bool
    {
        if (!self::validateDate($birthDate)) {
            return false;
        }

        $birth = new \DateTime($birthDate);
        $today = new \DateTime();
        $age = $today->diff($birth)->y;

        return $age >= $minAge;
    }

    /**
     * Validates strong password
     * 
     * Requirements:
     * - At least 8 characters
     * - At least one uppercase letter
     * - At least one lowercase letter
     * - At least one number
     * - At least one special character
     * 
     * @param string $password The password to validate
     * @return bool True if password is strong
     */
    public static function validateStrongPassword(string $password): bool
    {
        if (strlen($password) < 8) {
            return false;
        }

        // Check for uppercase
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        // Check for lowercase
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        // Check for number
        if (!preg_match('/\d/', $password)) {
            return false;
        }

        // Check for special character
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return false;
        }

        return true;
    }

    /**
     * Validates coordinates (latitude, longitude)
     * 
     * @param float $latitude Latitude (-90 to 90)
     * @param float $longitude Longitude (-180 to 180)
     * @return bool True if valid coordinates
     */
    public static function validateCoordinates(float $latitude, float $longitude): bool
    {
        return ($latitude >= -90 && $latitude <= 90) &&
            ($longitude >= -180 && $longitude <= 180);
    }

    /**
     * Validates Greek coordinates (Greece bounds)
     * 
     * Greece approximate bounds:
     * Latitude: 34.8 to 41.7
     * Longitude: 19.4 to 29.6
     * 
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @return bool True if coordinates are within Greece
     */
    public static function validateGreekCoordinates(float $latitude, float $longitude): bool
    {
        return ($latitude >= 34.8 && $latitude <= 41.7) &&
            ($longitude >= 19.4 && $longitude <= 29.6);
    }

    /**
     * Get validation error message in Greek
     * 
     * @param string $field Field name
     * @param string $rule Validation rule that failed
     * @return string Error message in Greek
     */
    public static function getErrorMessage(string $field, string $rule): string
    {
        $messages = [
            'email' => "Το πεδίο {$field} πρέπει να είναι έγκυρο email.",
            'phone' => "Το πεδίο {$field} πρέπει να είναι έγκυρος ελληνικός αριθμός κινητού (69XXXXXXXX).",
            'afm' => "Το πεδίο {$field} πρέπει να είναι έγκυρο ΑΦΜ (9 ψηφία).",
            'amka' => "Το πεδίο {$field} πρέπει να είναι έγκυρο ΑΜΚΑ (11 ψηφία).",
            'license_plate' => "Το πεδίο {$field} πρέπει να είναι έγκυρη ελληνική πινακίδα.",
            'postal_code' => "Το πεδίο {$field} πρέπει να είναι έγκυρος ταχυδρομικός κώδικας (5 ψηφία).",
            'url' => "Το πεδίο {$field} πρέπει να είναι έγκυρο URL.",
            'date' => "Το πεδίο {$field} πρέπει να είναι έγκυρη ημερομηνία.",
            'min_age' => "Πρέπει να είστε τουλάχιστον 18 ετών.",
            'strong_password' => "Ο κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες, ένα κεφαλαίο, ένα πεζό, έναν αριθμό και έναν ειδικό χαρακτήρα.",
            'coordinates' => "Οι συντεταγμένες δεν είναι έγκυρες.",
        ];

        return $messages[$rule] ?? "Το πεδίο {$field} δεν είναι έγκυρο.";
    }
}
