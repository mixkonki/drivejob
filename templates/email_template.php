<?php
function createEmailTemplate($role, $verificationLink) {
    return "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <div style='text-align: left;'>
            <h1>Επιβεβαίωση Εγγραφής</h1>
            <p>Καλωσήρθατε στο DriveJob ως <strong>" . ucfirst($role) . "</strong>.</p>
            <p>Παρακαλώ επιβεβαιώστε την εγγραφή σας πατώντας στον παρακάτω σύνδεσμο:</p>
            <a href='$verificationLink' style='color: #007BFF;'>Επιβεβαίωση Εγγραφής</a>
        </div>
        <div style='margin-top: 30px; text-align: left;'>
            <p>Με εκτίμηση,</p>
            <p><strong>Η ομάδα του DriveJob</strong></p>
            <!-- <img src='cid:logo_drivejob' alt='DriveJob Logo' style='width: 10%; margin-top: 20px;' /> -->
        </div>
    </body>
    </html>
    ";
}
?>
