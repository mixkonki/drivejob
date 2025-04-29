<?php
namespace Tests\Helpers;

class HeaderMock
{
    private static $enabled = false;
    private static $headers = [];
    private static $expectedHeaders = [];
    
    /**
     * Ενεργοποιεί το mock της συνάρτησης header
     */
    public static function enable()
    {
        self::$enabled = true;
        self::$headers = [];
    }
    
    /**
     * Απενεργοποιεί το mock της συνάρτησης header
     */
    public static function disable()
    {
        self::$enabled = false;
    }
    
    /**
     * Ορίζει έναν προσδοκώμενο header
     */
    public static function expectHeader($header)
    {
        self::$expectedHeaders[] = $header;
    }
    
    /**
     * Ελέγχει αν ένας συγκεκριμένος header έχει οριστεί
     */
    public static function hasHeader($header)
    {
        return in_array($header, self::$headers);
    }
    
    /**
     * Ελέγχει αν όλοι οι προσδοκώμενοι headers έχουν οριστεί
     */
    public static function hasExpectedHeaders()
    {
        foreach (self::$expectedHeaders as $header) {
            if (!self::hasHeader($header)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Επιστρέφει όλους τους headers που έχουν οριστεί
     */
    public static function getHeaders()
    {
        return self::$headers;
    }
    
    /**
     * Καθαρίζει όλους τους headers και τις προσδοκίες
     */
    public static function clearHeaders()
    {
        self::$headers = [];
        self::$expectedHeaders = [];
    }
    
    /**
     * Προσθέτει έναν header στη λίστα
     * 
     * @param string $header Header που προστέθηκε
     */
    public static function addHeader($header)
    {
        if (self::$enabled) {
            self::$headers[] = $header;
        }
    }
}

// Αντικατάσταση της συνάρτησης header
namespace {
    // Αποθήκευση της αρχικής συνάρτησης
    if (!function_exists('_original_header')) {
        function _original_header($header, $replace = true, $http_response_code = null)
        {
            return \header($header, $replace, $http_response_code);
        }
    }
    
    // Αντικατάσταση της συνάρτησης header
    function header($header, $replace = true, $http_response_code = null)
    {
        if (\Tests\Helpers\HeaderMock::$enabled) {
            \Tests\Helpers\HeaderMock::addHeader($header);
            
            // Προσομοίωση συμπεριφοράς exit() για τις δοκιμές
            if (strpos($header, 'Location:') === 0) {
                throw new \Exception('Exit called after redirect');
            }
        } else {
            return _original_header($header, $replace, $http_response_code);
        }
    }
}