<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Σφάλμα Ασφαλείας - DriveJob</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/styles.css">
    <style>
        .error-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .error-icon {
            color: #aa3636;
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .error-title {
            font-size: 24px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .error-message {
            font-size: 16px;
            margin-bottom: 25px;
            color: #666;
            line-height: 1.5;
        }
        
        .error-actions {
            margin-top: 20px;
        }
        
        .btn-primary {
            display: inline-block;
            padding: 10px 20px;
            background-color: #aa3636;
            color: white;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
        }
        
        .btn-primary:hover {
            background-color: #bb4747;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <h1 class="error-title">Σφάλμα Ασφαλείας CSRF</h1>
        <div class="error-message">
            <p>Παρουσιάστηκε ένα πρόβλημα ασφαλείας κατά την επεξεργασία του αιτήματός σας.</p>
            <p>Αυτό μπορεί να συμβεί αν η συνεδρία σας έχει λήξει ή αν προσπαθείτε να υποβάλετε μια φόρμα από άλλη σελίδα.</p>
            <p>Παρακαλούμε επιστρέψτε στην προηγούμενη σελίδα και δοκιμάστε ξανά.</p>
        </div>
        <div class="error-actions">
            <a href="<?php echo BASE_URL; ?>" class="btn-primary">Επιστροφή στην Αρχική</a>
        </div>
    </div>
</body>
</html>