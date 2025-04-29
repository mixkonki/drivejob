<?php 
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php'; 
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/job-listings.css">

<main>
    <div class="container">
        <h1>Αγγελίες Εταιρείας</h1>
        
        <div class="company-profile-header">
            <div class="company-info">
                <?php if (isset($company['logo']) && $company['logo']): ?>
                    <img src="<?php echo BASE_URL . htmlspecialchars($company['logo']); ?>" alt="Λογότυπο εταιρείας" class="company-logo">
                <?php else: ?>
                    <img src="<?php echo BASE_URL; ?>img/default_company_logo.png" alt="Προεπιλεγμένο λογότυπο" class="company-logo">
                <?php endif; ?>
                <div class="company-details">
                    <h2><?php echo htmlspecialchars($company['company_name']); ?></h2>
                    <?php if (isset($company['city']) && $company['city']): ?>
                        <p class="company-location">
                            <img src="<?php echo BASE_URL; ?>img/location_icon.png" alt="Τοποθεσία">
                            <?php echo htmlspecialchars($company['city'] . ', ' . $company['country']); ?>
                        </p>
                    <?php endif; ?>
                    <div class="company-meta">
                        <?php if (isset($company['company_size']) && $company['company_size']): ?>
                            <span class="company-size">
                                <img src="<?php echo BASE_URL; ?>img/employees_icon.png" alt="Μέγεθος">
                                <?php 
                                switch ($company['company_size']) {
                                    case 'micro': echo '1-9 εργαζόμενοι'; break;
                                    case 'small': echo '10-49 εργαζόμενοι'; break;
                                    case 'medium': echo '50-249 εργαζόμενοι'; break;
                                    case 'large': echo '250+ εργαζόμενοι'; break;
                                    default: echo htmlspecialchars($company['company_size']);
                                }
                                ?>
                            </span>
                        <?php endif; ?>
                        <?php if (isset($company['rating']) && $company['rating']): ?>
                            <span class="company-rating">
                                <img src="<?php echo BASE_URL; ?>img/rating_icon.png" alt="Αξιολόγηση">
                                <?php echo number_format($company['rating'], 1); ?>/5
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="company-actions">
                <a href="<?php echo BASE_URL; ?>companies/profile/<?php echo $company['id']; ?>" class="btn-secondary">Προβολή Προφίλ</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'driver'): ?>
                    <a href="<?php echo BASE_URL; ?>messages/create/<?php echo $company['id']; ?>" class="btn-primary">Επικοινωνία</a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
                <?php echo $_SESSION['success_message']; ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message">
                <?php echo $_SESSION['error_message']; ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Λίστα Αγγελιών -->
        <?php if (isset($listings) && count($listings['results']) > 0): ?>
            <div class="job-listings">
                <?php foreach ($listings['results'] as $listing): ?>
                    <div class="job-listing-card">
                        <div class="job-listing-header">
                            <h3><a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></a></h3>
                            <div>
                                <span class="job-type <?php echo $listing['job_type']; ?>">
                                    <?php 
                                    switch ($listing['job_type']) {
                                        case 'full_time': echo 'Πλήρης Απασχόληση'; break;
                                        case 'part_time': echo 'Μερική Απασχόληση'; break;
                                        case 'contract': echo 'Σύμβαση Έργου'; break;
                                        case 'temporary': echo 'Προσωρινή Απασχόληση'; break;
                                    }
                                    ?>
                                </span>
                                <span class="listing-type <?php echo $listing['listing_type']; ?>">
                                    <?php echo $listing['listing_type'] === 'job_offer' ? 'Προσφορά Εργασίας' : 'Αναζήτηση Εργασίας'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="job-listing-details">
                            <div class="job-listing-detail">
                                <img src="<?php echo BASE_URL; ?>img/location_icon.png" alt="Τοποθεσία">
                                <span><?php echo htmlspecialchars($listing['location']); ?></span>
                            </div>
                            
                            <div class="job-listing-detail">
                                <img src="<?php echo BASE_URL; ?>img/vehicle_icon.png" alt="Όχημα">
                                <span>
                                    <?php 
                                    switch ($listing['vehicle_type']) {
                                        case 'car': echo 'Αυτοκίνητο'; break;
                                        case 'van': echo 'Βαν'; break;
                                        case 'truck': echo 'Φορτηγό'; break;
                                        case 'bus': echo 'Λεωφορείο'; break;
                                        case 'machinery': echo 'Μηχάνημα Έργου'; break;
                                    }
                                    ?>
                                </span>
                            </div>
                            
                            <?php if ($listing['salary_min'] || $listing['salary_max']): ?>
                                <div class="job-listing-detail">
                                    <img src="<?php echo BASE_URL; ?>img/salary_icon.png" alt="Αμοιβή">
                                    <span>
                                        <?php 
                                        if ($listing['salary_min'] && $listing['salary_max']) {
                                            echo number_format($listing['salary_min']) . '€ - ' . number_format($listing['salary_max']) . '€';
                                        } elseif ($listing['salary_min']) {
                                            echo 'Από ' . number_format($listing['salary_min']) . '€';
                                        } elseif ($listing['salary_max']) {
                                            echo 'Έως ' . number_format($listing['salary_max']) . '€';
                                        }
                                        ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($listing['is_active']): ?>
                                <div class="job-listing-detail">
                                    <span class="listing-status active">Ενεργή</span>
                                </div>
                            <?php else: ?>
                                <div class="job-listing-detail">
                                    <span class="listing-status inactive">Ανενεργή</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="job-listing-description">
                            <?php echo nl2br(htmlspecialchars(substr($listing['description'], 0, 150) . (strlen($listing['description']) > 150 ? '...' : ''))); ?>
                        </div>
                        
                        <div class="job-listing-footer">
                            <span class="job-listing-date">Δημοσιεύτηκε: <?php echo date('d/m/Y', strtotime($listing['created_at'])); ?></span>
                            <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>" class="btn-primary">Περισσότερα</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Σελιδοποίηση -->
            <?php if ($listings['pagination']['pages'] > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $listings['pagination']['pages']; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="pagination-btn <?php echo $i === $listings['pagination']['page'] ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-results">
                <p>Η εταιρεία δεν έχει δημοσιεύσει αγγελίες.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
    .company-profile-header {
        background-color: #f9f9f9;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .company-info {
        display: flex;
        align-items: center;
    }
    
    .company-logo {
        width: 100px;
        height: 100px;
        object-fit: contain;
        margin-right: 20px;
        background-color: white;
        border-radius: 8px;
        padding: 10px;
    }
    
    .company-details h2 {
        margin: 0 0 5px 0;
        font-size: 24px;
    }
    
    .company-location {
        display: flex;
        align-items: center;
        color: #666;
        margin: 0 0 10px 0;
    }
    
    .company-location img {
        width: 16px;
        height: 16px;
        margin-right: 5px;
    }
    
    .company-meta {
        display: flex;
        gap: 15px;
    }
    
    .company-size, .company-rating {
        display: flex;
        align-items: center;
        font-size: 14px;
        color: #666;
    }
    
    .company-size img, .company-rating img {
        width: 16px;
        height: 16px;
        margin-right: 5px;
    }
    
    .company-actions {
        display: flex;
        gap: 10px;
    }
    
    .listing-status {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
    }
    
    .listing-status.active {
        background-color: #d4edda;
        color: #155724;
    }
    
    .listing-status.inactive {
        background-color: #f8d7da;
        color: #721c24;
    }
</style>

<?php 
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php'; 
?>