<?php
// Αυτό πρέπει να υπάρχει στην αρχή του αρχείου
use Drivejob\Core\Session;

if (session_status() === PHP_SESSION_NONE) {
    Session::start();
}
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<!-- Σύνδεση με τα CSS αρχεία -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/company-profile.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/company-components.css">

<style>
    /* Main layout */
    .profile-container {
        display: flex;
        gap: 30px;
        margin-top: 30px;
    }

    .profile-main-content {
        flex: 1;
        min-width: 0;
    }

    .profile-sidebar {
        width: 350px;
        flex-shrink: 0;
    }

    /* Tabs styling similar to driver profile */
    .profile-tabs {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .tabs-nav {
        display: flex;
        background: #f8f9fa;
        border-bottom: 2px solid #e0e0e0;
        margin: 0;
        padding: 0;
    }

    .tab-btn {
        flex: 1;
        padding: 15px 20px;
        background: none;
        border: none;
        border-bottom: 3px solid transparent;
        color: #666;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
    }

    .tab-btn:hover {
        background: #e9ecef;
        color: #007bff;
    }

    .tab-btn.active {
        background: white;
        color: #007bff;
        border-bottom-color: #007bff;
    }

    .tab-content {
        padding: 30px;
        min-height: 500px;
    }

    .tab-pane {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .tab-pane.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .profile-header {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }

    .profile-stats-header {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
    }

    .profile-stats {
        display: flex;
        justify-content: space-around;
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .profile-stats li {
        text-align: center;
    }

    .stat-value {
        font-size: 28px;
        font-weight: bold;
        color: #007bff;
        display: block;
    }

    .stat-label {
        font-size: 14px;
        color: #666;
        margin-top: 5px;
    }

    /* Sidebar sections */
    .sidebar-section {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin-bottom: 20px;
    }

    .sidebar-section h3 {
        font-size: 18px;
        margin-bottom: 15px;
        color: #333;
    }

    .contact-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .contact-list li {
        padding: 8px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .contact-list i {
        color: #007bff;
        width: 20px;
    }

    @media (max-width: 1200px) {
        .profile-container {
            flex-direction: column;
        }

        .profile-sidebar {
            width: 100%;
        }
    }
</style>

<main>
    <div class="container">
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="profile-image">
                        <?php if (isset($companyData['company_logo']) && $companyData['company_logo']) : ?>
                            <img src="<?php echo BASE_URL . htmlspecialchars($companyData['company_logo']); ?>" alt="Λογότυπο εταιρείας" style="width: 120px; height: 120px; object-fit: cover;">
                        <?php else : ?>
                            <img src="<?php echo BASE_URL; ?>img/default_company_logo.png" alt="Προεπιλεγμένο λογότυπο" style="width: 120px; height: 120px; object-fit: cover;">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col">
                    <h1><?php echo htmlspecialchars($companyData['company_name']); ?></h1>

                    <?php if (isset($companyData['city']) && $companyData['city']) : ?>
                        <p class="profile-location mb-3">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($companyData['city'] . ', ' . $companyData['country']); ?>
                        </p>
                    <?php endif; ?>

                    <div class="profile-actions">
                        <a href="/drivejob/public/companies/edit-profile" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Επεξεργασία Προφίλ
                        </a>
                        <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn btn-secondary">
                            <i class="fas fa-plus"></i> Νέα Αγγελία
                        </a>
                        <a href="/drivejob/public/companies/messages" class="btn btn-secondary">
                            <i class="fas fa-envelope"></i> Μηνύματα
                        </a>
                    </div>
                </div>

                <div class="col-auto">
                    <div class="profile-stats-header">
                        <h3 class="mb-3">Στατιστικά</h3>
                        <ul class="profile-stats">
                            <li>
                                <span class="stat-value"><?php echo $companyStats['active_jobs'] ?? 0; ?></span>
                                <span class="stat-label">Ενεργές Αγγελίες</span>
                            </li>
                            <li>
                                <span class="stat-value"><?php echo $companyStats['total_applications'] ?? 0; ?></span>
                                <span class="stat-label">Αιτήσεις</span>
                            </li>
                            <li>
                                <span class="stat-value"><?php echo $companyStats['hired_drivers'] ?? 0; ?></span>
                                <span class="stat-label">Προσλήψεις</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])) : ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])) : ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Main Content with Sidebar Layout -->
        <div class="profile-container">
            <!-- Main Content Area -->
            <div class="profile-main-content">
                <!-- Tabs Navigation -->
                <div class="profile-tabs">
                    <nav class="tabs-nav">
                        <button class="tab-btn active" data-tab="overview">Επισκόπηση</button>
                        <button class="tab-btn" data-tab="job-listings">Αγγελίες</button>
                        <button class="tab-btn" data-tab="candidates">Υποψήφιοι</button>
                        <button class="tab-btn" data-tab="fleet">Στόλος & Οδηγοί</button>
                        <button class="tab-btn" data-tab="services">Υπηρεσίες</button>
                    </nav>

                    <div class="tab-content">
                        <!-- Overview Tab -->
                        <div class="tab-pane active" id="overview">
                            <section class="mb-4">
                                <h2>Σχετικά με την Εταιρεία</h2>
                                <div class="profile-about">
                                    <?php if (isset($companyData['description']) && $companyData['description']) : ?>
                                        <?php echo nl2br(htmlspecialchars($companyData['description'])); ?>
                                    <?php else : ?>
                                        <p class="text-muted">Δεν έχετε προσθέσει περιγραφή για την εταιρεία σας. <a href="/drivejob/public/companies/edit-profile" class="btn btn-primary">Προσθέστε τώρα!</a></p>
                                    <?php endif; ?>
                                </div>
                            </section>

                            <section>
                                <h2>Πληροφορίες Εταιρείας</h2>
                                <div class="row">
                                    <?php if (isset($companyData['industry']) && $companyData['industry']) : ?>
                                        <div class="col-md-6 mb-3">
                                            <strong>Κλάδος:</strong> <?php echo htmlspecialchars($companyData['industry']); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($companyData['company_size']) && $companyData['company_size']) : ?>
                                        <div class="col-md-6 mb-3">
                                            <strong>Μέγεθος Εταιρείας:</strong> <?php echo htmlspecialchars($companyData['company_size']); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($companyData['foundation_year']) && $companyData['foundation_year']) : ?>
                                        <div class="col-md-6 mb-3">
                                            <strong>Έτος Ίδρυσης:</strong> <?php echo htmlspecialchars($companyData['foundation_year']); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($companyData['vat_number']) && $companyData['vat_number']) : ?>
                                        <div class="col-md-6 mb-3">
                                            <strong>ΑΦΜ:</strong> <?php echo htmlspecialchars($companyData['vat_number']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </section>
                        </div>

                        <!-- Job Listings Tab -->
                        <div class="tab-pane" id="job-listings">
                            <h2>Οι Αγγελίες μας</h2>
                            <?php if (isset($listings['results']) && count($listings['results']) > 0) : ?>
                                <div class="profile-listings">
                                    <?php foreach ($listings['results'] as $listing) : ?>
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <h3><a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></a></h3>
                                                <div class="listing-meta mb-2">
                                                    <span class="badge bg-primary"><?php echo $listing['listing_type'] === 'job_offer' ? 'Προσφορά Εργασίας' : 'Αναζήτηση Εργασίας'; ?></span>
                                                    <span class="text-muted ms-3">Δημοσιεύτηκε: <?php echo date('d/m/Y', strtotime($listing['created_at'])); ?></span>
                                                    <span class="badge <?php echo $listing['is_active'] ? 'bg-success' : 'bg-secondary'; ?> ms-3">
                                                        <?php echo $listing['is_active'] ? 'Ενεργή' : 'Ανενεργή'; ?>
                                                    </span>
                                                </div>
                                                <p class="mt-2"><?php echo substr(htmlspecialchars($listing['description'] ?? ''), 0, 200); ?>...</p>
                                                <div class="mt-3">
                                                    <a href="<?php echo BASE_URL; ?>job-listings/edit/<?php echo $listing['id']; ?>" class="btn btn-sm btn-outline-primary">Επεξεργασία</a>
                                                    <a href="<?php echo BASE_URL; ?>api/matching/job/candidates?job_id=<?php echo $listing['id']; ?>" class="btn btn-sm btn-outline-info">Υποψήφιοι</a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-3">
                                    <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn btn-primary">Νέα αγγελία</a>
                                </div>
                            <?php else : ?>
                                <p class="text-muted">Δεν έχετε δημιουργήσει ακόμα αγγελίες.</p>
                                <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn btn-primary">Δημιουργία Πρώτης Αγγελίας</a>
                            <?php endif; ?>
                        </div>

                        <!-- Candidates Tab -->
                        <div class="tab-pane" id="candidates">
                            <h2>Προτεινόμενοι Υποψήφιοι</h2>
                            <!-- AI Matching Widget -->
                            <?php include __DIR__ . '/partials/ai-matching-widget.php'; ?>
                        </div>

                        <!-- Fleet & Drivers Tab -->
                        <div class="tab-pane" id="fleet">
                            <div class="row">
                                <div class="col-md-6">
                                    <!-- Fleet Management Component -->
                                    <?php include ROOT_DIR . '/src/Views/components/company/fleet-management-card.php'; ?>
                                </div>
                                <div class="col-md-6">
                                    <!-- Driver Management Component -->
                                    <?php include ROOT_DIR . '/src/Views/components/company/driver-management-card.php'; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Services Tab -->
                        <div class="tab-pane" id="services">
                            <div class="row">
                                <div class="col-md-4">
                                    <!-- Transport Types Component -->
                                    <?php include ROOT_DIR . '/src/Views/components/company/transport-types-card.php'; ?>
                                </div>
                                <div class="col-md-4">
                                    <!-- Compliance Component -->
                                    <?php include ROOT_DIR . '/src/Views/components/company/compliance-card.php'; ?>
                                </div>
                                <div class="col-md-4">
                                    <!-- Subscription Component -->
                                    <?php include ROOT_DIR . '/src/Views/components/company/subscription-card.php'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="profile-sidebar">
                <!-- Messages Widget -->
                <div class="sidebar-section">
                    <h3><i class="fas fa-envelope"></i> Μηνύματα</h3>
                    <p class="text-muted mb-3">Έχετε <strong>3</strong> νέα μηνύματα</p>
                    <a href="/drivejob/public/companies/messages" class="btn btn-secondary">
                        Προβολή Μηνυμάτων
                    </a>
                </div>

                <!-- Contact Information -->
                <div class="sidebar-section">
                    <h3><i class="fas fa-info-circle"></i> Στοιχεία Επικοινωνίας</h3>
                    <ul class="contact-list">
                        <li>
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($companyData['email']); ?></span>
                        </li>
                        <li>
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($companyData['phone']); ?></span>
                        </li>
                        <?php if (isset($companyData['website']) && $companyData['website']) : ?>
                            <li>
                                <i class="fas fa-globe"></i>
                                <a href="<?php echo htmlspecialchars($companyData['website']); ?>" target="_blank"><?php echo htmlspecialchars($companyData['website']); ?></a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Quick Actions -->
                <div class="sidebar-section">
                    <h3><i class="fas fa-bolt"></i> Γρήγορες Ενέργειες</h3>
                    <div class="d-grid gap-2">
                        <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn btn-success">
                            <i class="fas fa-plus"></i> Νέα Αγγελία
                        </a>
                        <a href="<?php echo BASE_URL; ?>drivers/search" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i> Αναζήτηση Οδηγών
                        </a>
                        <a href="/drivejob/public/companies/edit-profile" class="btn btn-primary">
                            <i class="fas fa-cog"></i> Ρυθμίσεις
                        </a>
                    </div>
                </div>

                <!-- Location Map -->
                <?php if (isset($companyData['address']) && $companyData['address'] && isset($companyData['city']) && $companyData['city']) : ?>
                    <div class="sidebar-section">
                        <h3><i class="fas fa-map-marker-alt"></i> Τοποθεσία</h3>
                        <div class="profile-map">
                            <iframe
                                width="100%"
                                height="200"
                                frameborder="0"
                                scrolling="no"
                                marginheight="0"
                                marginwidth="0"
                                src="https://maps.google.com/maps?q=<?php echo urlencode($companyData['address'] . ', ' . $companyData['city'] . ', ' . $companyData['country']); ?>&output=embed"></iframe>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
    // Tab functionality
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabPanes = document.querySelectorAll('.tab-pane');

        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');

                // Remove active class from all buttons and panes
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabPanes.forEach(pane => pane.classList.remove('active'));

                // Add active class to clicked button and corresponding pane
                this.classList.add('active');
                document.getElementById(targetTab).classList.add('active');
            });
        });
    });
</script>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>