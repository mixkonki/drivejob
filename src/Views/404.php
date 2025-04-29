<?php
// src/Views/404.php
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php';
?>

<main>
    <div class="container">
        <div class="error-page">
            <h1>404</h1>
            <h2>Η σελίδα δεν βρέθηκε</h2>
            <p>Η σελίδα που αναζητάτε δεν υπάρχει ή έχει μετακινηθεί.</p>
            <a href="<?php echo BASE_URL; ?>" class="btn-primary">Επιστροφή στην αρχική σελίδα</a>
        </div>
    </div>
</main>

<style>
    .error-page {
        text-align: center;
        padding: 50px 0;
    }
    
    .error-page h1 {
        font-size: 72px;
        color: #aa3636;
        margin-bottom: 10px;
    }
    
    .error-page h2 {
        font-size: 32px;
        margin-bottom: 20px;
    }
    
    .error-page p {
        font-size: 18px;
        color: #666;
        margin-bottom: 30px;
    }
</style>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php';
?>