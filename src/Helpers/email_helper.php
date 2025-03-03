<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/autoload.php';

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Ρυθμίσεις SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.thessdrive.gr';
        $mail->SMTPAuth = true;
        $mail->Username = 'info@thessdrive.gr';
        $mail->Password = 'inf1q2w!Q@W';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->CharSet = 'UTF-8'; // Ορισμός της σωστής κωδικοποίησης χαρακτήρων
        $mail->Encoding = 'base64'; // Χρήση base64 για σωστή μεταφορά χαρακτήρων

        // Ενεργοποίηση εντοπισμού σφαλμάτων
        $mail->SMTPDebug = 0; // Εμφάνιση λεπτομερειών αποστολής
        $mail->Debugoutput = 'html'; // Μορφή εξόδου σφαλμάτων
        
        // Πληροφορίες αποστολέα
        $mail->setFrom('info@thessdrive.gr', 'DriveJob');
        $mail->addAddress($to);

        // Προσθήκη εικόνας ως embedded
        //$mail->addEmbeddedImage('C:/wamp64/www/drivejob/public/img/logo.png', 'logo_drivejob');

        // Περιεχόμενο email
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        // Αποστολή email
        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Σφάλμα κατά την αποστολή: {$mail->ErrorInfo}";
        return false;
    }
}

?>
