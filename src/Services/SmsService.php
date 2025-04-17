<?php
namespace Drivejob\Services;

/**
 * Υπηρεσία για την αποστολή SMS
 */
class SmsService {
    private $apiKey;
    private $apiUrl;
    private $sender;
    private $debug;
    
    /**
     * Κατασκευαστής της κλάσης
     * 
     * @param string $apiKey Κλειδί API της υπηρεσίας SMS
     * @param string $apiUrl URL του API
     * @param string $sender Αναγνωριστικό αποστολέα
     * @param bool $debug Ενεργοποίηση debug mode
     */
    public function __construct($apiKey, $apiUrl, $sender = 'DriveJob', $debug = false) {
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
        $this->sender = $sender;
        $this->debug = $debug;
    }
    
    /**
     * Αποστολή SMS
     * 
     * @param string $phoneNumber Αριθμός τηλεφώνου παραλήπτη
     * @param string $message Μήνυμα
     * @return bool Επιτυχία/αποτυχία
     */
    public function sendSms($phoneNumber, $message) {
        try {
            // Καταγραφή για αποσφαλμάτωση
            if ($this->debug) {
                error_log("Αποστολή SMS προς: {$phoneNumber}, Μήνυμα: {$message}");
            }
            
            // Προετοιμασία του αριθμού τηλεφώνου
            $phoneNumber = $this->formatPhoneNumber($phoneNumber);
            
            // Προετοιμασία των δεδομένων για το API
            $data = [
                'apikey' => $this->apiKey,
                'to' => $phoneNumber,
                'message' => $message,
                'sender' => $this->sender
            ];
            
            // Ρυθμίσεις για το CURL
            $options = [
                CURLOPT_URL => $this->apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json'
                ]
            ];
            
            // Αποστολή του αιτήματος με CURL
            $ch = curl_init();
            curl_setopt_array($ch, $options);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            // Έλεγχος για σφάλματα CURL
            if ($curlError) {
                error_log("CURL Error: {$curlError}");
                return false;
            }
            
            // Έλεγχος κωδικού απόκρισης HTTP
            if ($httpCode < 200 || $httpCode >= 300) {
                error_log("HTTP Error Code: {$httpCode}, Response: {$response}");
                return false;
            }
            
            // Έλεγχος της απόκρισης του API
            $responseData = json_decode($response, true);
            
            if ($this->debug) {
                error_log("API Response: " . print_r($responseData, true));
            }
            
            // Έλεγχος επιτυχίας αποστολής
            if (isset($responseData['success']) && $responseData['success']) {
                return true;
            } else {
                $errorMessage = isset($responseData['message']) ? $responseData['message'] : 'Unknown error';
                error_log("API Error: {$errorMessage}");
                return false;
            }
        } catch (\Exception $e) {
            error_log('Σφάλμα κατά την αποστολή SMS: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Μορφοποίηση αριθμού τηλεφώνου σε διεθνή μορφή
     * 
     * @param string $phoneNumber Αριθμός τηλεφώνου
     * @return string Μορφοποιημένος αριθμός τηλεφώνου
     */
    private function formatPhoneNumber($phoneNumber) {
        // Αφαίρεση όλων των χαρακτήρων εκτός από ψηφία
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Προσθήκη διεθνούς κωδικού αν λείπει
        if (strlen($phoneNumber) === 10 && substr($phoneNumber, 0, 1) === '6') {
            // Ελληνικό κινητό χωρίς κωδικό χώρας
            $phoneNumber = '30' . $phoneNumber;
        }
        
        return $phoneNumber;
    }
    
    /**
     * Αποστολή μαζικών SMS
     * 
     * @param array $recipients Λίστα αριθμών τηλεφώνου
     * @param string $message Μήνυμα
     * @return array Αποτελέσματα αποστολής (αριθμός => επιτυχία/αποτυχία)
     */
    public function sendBulkSms($recipients, $message) {
        $results = [];
        
        foreach ($recipients as $phoneNumber) {
            $results[$phoneNumber] = $this->sendSms($phoneNumber, $message);
        }
        
        return $results;
    }
}