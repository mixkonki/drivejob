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
<?= \Drivejob\Helpers\Asset::css('css/company-profile.css') ?>
<?= \Drivejob\Helpers\Asset::css('css/company-components.css') ?>

<style>
    /* Tabs styling similar to driver profile */
    .profile-tabs {
        margin-top: 30px;
    }

    .tabs-nav {
        display: flex;
        border-bottom: 2px solid #e0e0e0;
        margin-bottom: 30px;
        gap: 10px;
    }

    .tab-btn {
        padding: 12px 24px;
        background: none;
        border: none;
        border-bottom: 3px solid transparent;
        color: #666;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        white-space: nowrap;
    }

    .tab-btn:hover {
        color: #007bff;
    }

    .tab-btn.active {
        color: #007bff;
        border-bottom-color: #007bff;
    }

    .tab-content {
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
                        <a href="<?php echo BASE_URL; ?>companies/edit-profile" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Επεξεργασία Προφίλ
                        </a>
                        <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn btn-secondary">
                            <i class="fas fa-plus"></i> Νέα Αγγελία
                        </a>
                        <a href="<?php echo BASE_URL; ?>companies/messages" class="btn btn-secondary">
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
                    <div class="profile-content">
                        <div class="profile-main">
                            <section class="profile-section">
                                <h2>Σχετικά με την Εταιρεία</h2>
                                <div class="profile-about">
                                    <?php if (isset($companyData['description']) && $companyData['description']) : ?>
                                        <?php echo nl2br(htmlspecialchars($companyData['description'])); ?>
                                    <?php else : ?>
                                        <p class="profile-empty">Δεν έχετε προσθέσει περιγραφή για την εταιρεία σας. <a href="<?php echo BASE_URL; ?>companies/edit-profile">Προσθέστε τώρα!</a></p>
                                    <?php endif; ?>
                                </div>
                            </section>

                            <section class="profile-section">
                                <h2>Πληροφορίες Εταιρείας</h2>
                                <div class="company-info">
                                    <div class="info-grid">
                                        <?php if (isset($companyData['industry']) && $companyData['industry']) : ?>
                                            <div class="info-item">
                                                <div class="info-label">Κλάδος</div>
                                                <div class="info-value"><?php echo htmlspecialchars($companyData['industry']); ?></div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (isset($companyData['company_size']) && $companyData['company_size']) : ?>
                                            <div class="info-item">
                                                <div class="info-label">Μέγεθος Εταιρείας</div>
                                                <div class="info-value"><?php echo htmlspecialchars($companyData['company_size']); ?></div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (isset($companyData['foundation_year']) && $companyData['foundation_year']) : ?>
                                            <div class="info-item">
                                                <div class="info-label">Έτος Ίδρυσης</div>
                                                <div class="info-value"><?php echo htmlspecialchars($companyData['foundation_year']); ?></div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (isset($companyData['vat_number']) && $companyData['vat_number']) : ?>
                                            <div class="info-item">
                                                <div class="info-label">ΑΦΜ</div>
                                                <div class="info-value"><?php echo htmlspecialchars($companyData['vat_number']); ?></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="profile-sidebar">
                            <section class="profile-section">
                                <h2>Στοιχεία Επικοινωνίας</h2>
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
                            </section>

                            <?php if (isset($companyData['address']) && $companyData['address'] && isset($companyData['city']) && $companyData['city']) : ?>
                                <section class="profile-section">
                                    <h2>Τοποθεσία</h2>
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
                                </section>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Job Listings Tab -->
                <div class="tab-pane" id="job-listings">
                    <section class="profile-section">
                        <h2>Οι Αγγελίες μας</h2>
                        <?php if (count($listings['results']) > 0) : ?>
                            <div class="profile-listings">
                                <?php foreach ($listings['results'] as $listing) : ?>
                                    <div class="listing-item card mb-3">
                                        <div class="card-body">
                                            <h3><a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></a></h3>
                                            <div class="listing-meta">
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
                            <div class="profile-section-footer">
                                <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn btn-primary">Νέα αγγελία</a>
                            </div>
                        <?php else : ?>
                            <p class="profile-empty">Δεν έχετε δημιουργήσει ακόμα αγγελίες. <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn btn-primary">Νέα αγγελία</a></p>
                        <?php endif; ?>
                    </section>
                </div>

                <!-- Candidates Tab -->
                <div class="tab-pane" id="candidates">
                    <div class="profile-section">
                        <h2>Προτεινόμενοι Υποψήφιοι</h2>
                        <!-- AI Candidates Widget -->
                        <?php include __DIR__ . '/partials/candidates-widget-with-messaging.php'; ?>
                    </div>
                </div>

                <!-- Fleet & Drivers Tab -->
                <div class="tab-pane" id="fleet">
                    <div class="features-grid">
                        <!-- Fleet Management Component -->
                        <?php include ROOT_DIR . '/src/Views/components/company/fleet-management-card.php'; ?>

                        <!-- Driver Management Component -->
                        <?php include ROOT_DIR . '/src/Views/components/company/driver-management-card.php'; ?>
                    </div>
                </div>

                <!-- Services Tab -->
                <div class="tab-pane" id="services">
                    <div class="features-grid">
                        <!-- Transport Types Component -->
                        <?php include ROOT_DIR . '/src/Views/components/company/transport-types-card.php'; ?>

                        <!-- Compliance Component -->
                        <?php include ROOT_DIR . '/src/Views/components/company/compliance-card.php'; ?>

                        <!-- Subscription Component -->
                        <?php include ROOT_DIR . '/src/Views/components/company/subscription-card.php'; ?>
                    </div>
                </div>
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