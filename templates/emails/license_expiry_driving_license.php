<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ειδοποίηση Λήξης Άδειας Οδήγησης - DriveJob</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
        .header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        .warning { color: #e74c3c; font-weight: bold; }
        .button { display: inline-block; background-color: #3498db; color: white; padding: 10px 20px; 
                  text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .info-box { background-color: #f8f9fa; border-left: 4px solid #3498db; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>DriveJob - Ειδοποίηση Λήξης Άδειας Οδήγησης</h1>
    </div>
    <div class="content">
        <p>Αγαπητέ/ή <?php echo $first_name; ?>,</p>
        
        <p>Σας ενημερώνουμε ότι η <strong>άδεια οδήγησής</strong> σας <strong>κατηγορίας <?php echo $license_type; ?></strong> 
        πρόκειται να λήξει σε <span class="warning"><?php echo $days_before_expiry == 1 ? "μία ημέρα" : $days_before_expiry . " ημέρες"; ?></span>, 
        στις <strong><?php echo date("d/m/Y", strtotime($expiry_date)); ?></strong>.</p>
        
        <div class="info-box">
            <h3>Στοιχεία Άδειας</h3>
            <p><strong>Τύπος:</strong> Άδεια οδήγησης κατηγορίας <?php echo $license_type; ?><br>
            <strong>Ημερομηνία Λήξης:</strong> <?php echo date("d/m/Y", strtotime($expiry_date)); ?><br>
            <strong>Υπολειπόμενες ημέρες:</strong> <?php echo $days_before_expiry; ?></p>
        </div>
        
        <p>Παρακαλούμε φροντίστε να ανανεώσετε έγκαιρα την άδειά σας για να αποφύγετε τυχόν προβλήματα 
        στην επαγγελματική σας δραστηριότητα.</p>
        
        <p>Για να ενημερώσετε τα στοιχεία σας στο προφίλ σας στο DriveJob, πατήστε το παρακάτω κουμπί:</p>
        
        <a href="<?php echo $base_url; ?>/drivers/edit-profile" class="button">Ενημέρωση Προφίλ</a>
        
        <p style="margin-top: 20px;">Σας ευχαριστούμε που χρησιμοποιείτε την πλατφόρμα DriveJob.</p>
        
        <p>Με εκτίμηση,<br>
        Η ομάδα του DriveJob</p>
    </div>
    <div class="footer">
        <p>Αυτό το email είναι αυτοματοποιημένο. Παρακαλούμε μην απαντήσετε σε αυτό το μήνυμα.</p>
        <p>Αν έχετε οποιαδήποτε απορία, επικοινωνήστε μαζί μας στο <a href="mailto:info@drivejob.gr">info@drivejob.gr</a>.</p>
        <p>&copy; <?php echo $year; ?> DriveJob. Με επιφύλαξη παντός δικαιώματος.</p>
    </div>
</body>
</html>