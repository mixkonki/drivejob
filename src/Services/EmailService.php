<?php
namespace Drivejob\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Υπηρεσία αποστολής email
 */
class EmailService {
    /**
     * @var string $host SMTP host
     */
    private $host;
    
    /**
     * @var int $port SMTP port
     */
    private $port;
    
    /**
     * @var string $username SMTP username
     */
    private $username;
    
    /**
     * @var string $password SMTP password
     */
    private $password;
    
    /**
     * @var string $senderEmail Διεύθυνση email αποστολέα
     */
    private $senderEmail;
    
    /**
     * @var string $senderName Όνομα αποστολέα
     */
    private $senderName;
    
    /**
     * @var bool $debugMode Λειτουργία αποσφαλμάτωσης
     */
    private $debugMode;
    
    /**
     * Constructor
     * 
     * @param string $host SMTP host
     * @param int $port SMTP port
     * @param string $username SMTP username
     * @param string $password SMTP password
     * @param string $senderEmail Διεύθυνση email αποστολέα
     * @param string $senderName Όνομα αποστολέα
     * @param bool $debugMode Λειτουργία αποσφαλμάτωσης
     */
    public function __construct($host, $port, $username, $password, $senderEmail, $senderName, $debugMode = false) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
        $this->debugMode = $debugMode;
    }
    
    /**
     * Αποστολή email
     * 
     * @param string|array $to Παραλήπτης ή λίστα παραληπτών
     * @param string $subject Θέμα
     * @param string $message Μήνυμα HTML
     * @param array $attachments Συνημμένα αρχεία [['path' => '/path/to/file', 'name' => 'filename']]
     * @param array $cc Διευθύνσεις κοινοποίησης (CC)
     * @param array $bcc Διευθύνσεις κρυφής κοινοποίησης (BCC)
     * @return bool Επιτυχία/αποτυχία
     */
    public function send($to, $subject, $message, $attachments = [], $cc = [], $bcc = []) {
        try {
            // Έλεγχος αν η βιβλιοθήκη PHPMailer είναι διαθέσιμη
            if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                throw new \Exception('Η βιβλιοθήκη PHPMailer δεν είναι διαθέσιμη.');
            }
            
            // Αρχικοποίηση του PHPMailer
            $mail = new PHPMailer(true); // true ενεργοποιεί τις εξαιρέσεις
            
            // Ρυθμίσεις Debug
            $mail->SMTPDebug = $this->debugMode ? 2 : 0; // 0 = off, 1 = client messages, 2 = client & server messages
            
            // Ρυθμίσεις Server
            $mail->isSMTP();
            $mail->Host = $this->host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->username;
            $mail->Password = $this->password;
            $mail->SMTPSecure = $this->port == 465 ? 'ssl' : 'tls';
            $mail->Port = $this->port;
            $mail->CharSet = 'UTF-8';
            
            // Αποστολέας
            $mail->setFrom($this->senderEmail, $this->senderName);
            
            // Παραλήπτες
            if (is_array($to)) {
                foreach ($to as $email) {
                    $mail->addAddress($email);
                }
            } else {
                $mail->addAddress($to);
            }
            
            // Προσθήκη CC
            if (!empty($cc)) {
                foreach ($cc as $email) {
                    $mail->addCC($email);
                }
            }
            
            // Προσθήκη BCC
            if (!empty($bcc)) {
                foreach ($bcc as $email) {
                    $mail->addBCC($email);
                }
            }
            
            // Προσθήκη συνημμένων
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    if (isset($attachment['path']) && file_exists($attachment['path'])) {
                        $mail->addAttachment(
                            $attachment['path'],
                            isset($attachment['name']) ? $attachment['name'] : ''
                        );
                    }
                }
            }
            
            // Περιεχόμενο
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            // Δημιουργία απλού κειμένου για clients που δεν υποστηρίζουν HTML
            $mail->AltBody = $this->html2text($message);
            
            // Αποστολή
            $result = $mail->send();
            
            if ($this->debugMode) {
                error_log('Email εστάλη στο: ' . ($is_array($to) ? implode(', ', $to) : $to));
                error_log('Θέμα: ' . $subject);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('Σφάλμα αποστολής email: ' . $e->getMessage());
            
            if ($this->debugMode) {
                // Debug του μηνύματος και του λάθους
                error_log('Email failed to: ' . (is_array($to) ? implode(', ', $to) : $to));
                error_log('Subject: ' . $subject);
                error_log('Error: ' . $e->getMessage());
            }
            
            return false;
        }
    }
    
    /**
     * Μετατροπή HTML σε απλό κείμενο
     * 
     * @param string $html HTML περιεχόμενο
     * @return string Απλό κείμενο
     */
    private function html2text($html) {
        // Αφαίρεση CSS
        $html = preg_replace('/<style(.*?)>(.*?)<\/style>/is', '', $html);
        
        // Αφαίρεση JavaScript
        $html = preg_replace('/<script(.*?)>(.*?)<\/script>/is', '', $html);
        
        // Αντικατάσταση βασικών HTML στοιχείων με αντίστοιχα σε απλό κείμενο
        $html = str_replace(['<br>', '<br />', '<br/>'], "\n", $html);
        $html = str_replace(['<p>', '<div>'], "\n\n", $html);
        $html = str_replace(['</p>', '</div>'], "", $html);
        $html = str_replace(['<h1>', '<h2>', '<h3>', '<h4>', '<h5>', '<h6>'], "\n\n", $html);
        $html = str_replace(['</h1>', '</h2>', '</h3>', '</h4>', '</h5>', '</h6>'], "\n\n", $html);
        $html = str_replace(['<hr>', '<hr />'], "\n" . str_repeat('-', 50) . "\n", $html);
        $html = str_replace(['<ul>', '</ul>', '<ol>', '</ol>'], "\n\n", $html);
        $html = str_replace(['<li>'], "• ", $html);
        $html = str_replace(['</li>'], "\n", $html);
        
        // Αφαίρεση όλων των υπόλοιπων HTML tags
        $html = strip_tags($html);
        
        // Αντικατάσταση πολλαπλών κενών γραμμών με μία κενή γραμμή
        $html = preg_replace("/(\n\s*){3,}/", "\n\n", $html);
        
        // Αποκωδικοποίηση HTML entities
        $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
        
        return trim($html);
    }
    
    /**
     * Επιστρέφει αν η λειτουργία αποσφαλμάτωσης είναι ενεργή
     * 
     * @return bool
     */
    public function isDebugModeEnabled() {
        return $this->debugMode;
    }
    
    /**
     * Ενεργοποιεί/απενεργοποιεί τη λειτουργία αποσφαλμάτωσης
     * 
     * @param bool $enabled
     */
    public function setDebugMode($enabled) {
        $this->debugMode = (bool)$enabled;
    }
}