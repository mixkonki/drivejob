<?php 
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php'; 
?>

<main>
    <div class="container">
        <h1>Διαγραφή Αγγελίας</h1>
        
        <div class="confirmation-box">
            <p>Είστε βέβαιοι ότι θέλετε να διαγράψετε την αγγελία με τίτλο:</p>
            <h2><?php echo htmlspecialchars($listing['title']); ?></h2>
            
            <p class="warning">ΠΡΟΣΟΧΗ: Η διαγραφή αυτή είναι μόνιμη και δεν μπορεί να αναιρεθεί!</p>
            
            <div class="listing-preview">
                <div class="listing-preview-item">
                    <strong>Τύπος Αγγελίας:</strong> 
                    <span><?php echo $listing['listing_type'] === 'job_offer' ? 'Προσφορά Εργασίας' : 'Αναζήτηση Εργασίας'; ?></span>
                </div>
                
                <div class="listing-preview-item">
                    <strong>Τύπος Απασχόλησης:</strong> 
                    <span>
                        <?php 
                        switch ($listing['job_type']) {
                            case 'full_time': echo 'Πλήρης Απασχόληση'; break;
                            case 'part_time': echo 'Μερική Απασχόληση'; break;
                            case 'contract': echo 'Σύμβαση Έργου'; break;
                            case 'temporary': echo 'Προσωρινή Απασχόληση'; break;
                        }
                        ?>
                    </span>
                </div>
                
                <div class="listing-preview-item">
                    <strong>Ημερομηνία Δημοσίευσης:</strong> 
                    <span><?php echo date('d/m/Y', strtotime($listing['created_at'])); ?></span>
                </div>
            </div>
            
            <form action="<?php echo BASE_URL; ?>job-listings/destroy/<?php echo $listing['id']; ?>" method="POST" class="delete-form">
    <?php echo \Drivejob\Core\CSRF::tokenField(); ?>
    
    <div class="form-actions">
        <button type="submit" class="btn-danger">Διαγραφή Αγγελίας</button>
        <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>" class="btn-secondary">Ακύρωση</a>
    </div>
</form>
        </div>
    </div>
</main>

<style>
    .confirmation-box {
        background-color: #f8f8f8;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 30px;
        margin-bottom: 30px;
        text-align: center;
    }
    
    .warning {
        color: #dc3545;
        font-weight: bold;
        margin: 20px 0;
    }
    
    .listing-preview {
        background-color: #fff;
        border: 1px solid #eee;
        border-radius: 5px;
        padding: 20px;
        margin: 20px 0;
        text-align: left;
    }
    
    .listing-preview-item {
        margin-bottom: 10px;
    }
    
    .form-actions {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 30px;
    }
</style>

<?php 
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php'; 
?>