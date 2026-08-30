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
<?php /* Deep links καρτελών, όπως στο προφίλ οδηγού: το #candidates στη
   διεύθυνση ανοίγει την καρτέλα, το refresh μένει εκεί. (01/09) */ ?>
<?= \Drivejob\Helpers\Asset::js('js/tab-deeplink.js', true) ?>

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
        /* Αναδίπλωση αντί για στρίμωγμα: το «Στόλος & Οδηγοί» έσπαγε
           σε τρεις γραμμές μέσα στο κουμπί του. (01/09) */
        flex-wrap: wrap;
        background: #f8f9fa;
        border-bottom: 2px solid #e0e0e0;
        margin: 0;
        padding: 0;
    }

    .tab-count {
        display: inline-block;
        min-width: 1.4em;
        padding: .05rem .35rem;
        border-radius: 999px;
        background: #b3261e;
        color: #fff;
        font-size: .72rem;
        font-weight: 700;
    }

    /* Λίστα αιτήσεων στην καρτέλα «Υποψήφιοι» */
    .cand-list { display: flex; flex-direction: column; }
    .cand-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem 1rem;
        padding: .65rem 0;
        border-top: 1px solid #f0f1f3;
    }
    .cand-row:first-child { border-top: 0; }
    .cand-main { flex: 1; min-width: min(260px, 100%); display: flex; flex-wrap: wrap; gap: .2rem .6rem; align-items: baseline; }
    .cand-name { font-weight: 600; color: #1f2937; text-decoration: none; }
    .cand-name:hover { text-decoration: underline; }
    .cand-listing { font-size: .84rem; color: #6b7280; }
    .cand-date { font-size: .76rem; color: #94a3b8; font-style: normal; }
    .cand-status {
        font-size: .74rem; font-weight: 700;
        padding: .18rem .6rem; border-radius: 999px; white-space: nowrap;
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
                            <img src="<?= \Drivejob\Helpers\Asset::url('img/default_company_logo.png') ?>" alt="Προεπιλεγμένο λογότυπο" style="width: 120px; height: 120px; object-fit: cover;">
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
                        <?php
                        /*
                         * ΟΙ ΑΙΤΗΣΕΙΣ ΔΕΝ ΕΙΧΑΝ ΣΥΝΔΕΣΜΟ ΠΟΥΘΕΝΑ.
                         *
                         * Η σελίδα /job-applications/company-applications υπήρχε και
                         * λειτουργούσε — αλλά κανένα κουμπί, μενού ή καρτέλα δεν
                         * οδηγούσε σε αυτήν. Ο μόνος τρόπος να τη δει η εταιρεία ήταν
                         * να πληκτρολογήσει τη διεύθυνση.
                         *
                         * Είναι η σημαντικότερη σελίδα της: εκεί βρίσκονται οι οδηγοί
                         * που περιμένουν απάντηση. Το πλήθος μπαίνει πάνω στο κουμπί,
                         * ώστε να φαίνεται ότι κάτι περιμένει χωρίς να χρειάζεται κλικ.
                         */
                        $pendingCount = (int) ($companyStats['pending_applications'] ?? 0);
                        ?>
                        <a href="<?php echo BASE_URL; ?>job-applications/company-applications"
                           class="btn <?php echo $pendingCount > 0 ? 'btn-primary' : 'btn-secondary'; ?>">
                            <i class="fas fa-inbox"></i> Αιτήσεις<?php
                                echo $pendingCount > 0 ? ' (' . $pendingCount . ' νέες)' : ''; ?>
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

        <!-- Main Content with Sidebar Layout -->
        <div class="profile-container">
            <!-- Main Content Area -->
            <div class="profile-main-content">
                <!-- Tabs Navigation -->
                <div class="profile-tabs">
                    <nav class="tabs-nav">
                        <?php /* Οι καρτέλες «Στόλος & Οδηγοί» και «Υπηρεσίες»
                           ΑΦΑΙΡΕΘΗΚΑΝ (01/09): έδειχναν κάρτες ανύπαρκτων
                           modules (DriveFleet Solutions, subscriptions, API) —
                           προϊόντα που δεν υπάρχουν, παρουσιασμένα σαν να
                           υπάρχουν. Μια πλατφόρμα που υπόσχεται ψέματα στην
                           πρώτη οθόνη της εταιρείας δεν ξαναπείθει εύκολα.
                           Όταν χτιστούν, θα ξαναμπούν — με περιεχόμενο. */ ?>
                        <button class="tab-btn active" data-tab="overview">Επισκόπηση</button>
                        <button class="tab-btn" data-tab="job-listings">Αγγελίες</button>
                        <button class="tab-btn" data-tab="candidates">Υποψήφιοι<?php
                            echo !empty($companyStats['pending_applications'])
                                ? ' <span class="tab-count">' . (int) $companyStats['pending_applications'] . '</span>'
                                : ''; ?></button>
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
                                        <p class="text-muted">Δεν έχετε προσθέσει περιγραφή για την εταιρεία σας. <a href="<?php echo BASE_URL; ?>companies/edit-profile" class="btn btn-primary">Προσθέστε τώρα!</a></p>
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
                                                    <?php /* Πριν έδειχνε στο api/matching/... — η εταιρεία
                                                       πατούσε «Υποψήφιοι» και έβλεπε ωμό JSON. */ ?>
                                                    <a href="<?php echo BASE_URL; ?>job-applications/listing/<?php echo $listing['id']; ?>" class="btn btn-sm btn-outline-info">Αιτήσεις</a>
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

                        <!-- Candidates Tab: ΠΡΑΓΜΑΤΙΚΕΣ αιτήσεις (01/09) -->
                        <?php /* Πριν: «AI Matching Widget» — μακέτα. Τώρα: οι
                           αιτήσεις που έκαναν οδηγοί στις αγγελίες της
                           εταιρείας, με κατάσταση και σύνδεσμο στο προφίλ
                           του οδηγού. Αυτό είναι το ακροατήριο όλης της
                           δουλειάς του προφίλ οδηγού — εδώ την βλέπει
                           επιτέλους κάποιος. */ ?>
                        <div class="tab-pane" id="candidates">
                            <h2>Αιτήσεις Υποψηφίων</h2>
                            <?php if (empty($recentApplications)) : ?>
                                <p class="text-muted">Καμία αίτηση ακόμη. Όταν οδηγός κάνει αίτηση σε αγγελία σας, θα εμφανιστεί εδώ.</p>
                            <?php else : ?>
                                <?php
                                $appStatus = [
                                    'pending' => ['Νέα', '#b45309', '#fffcf5'],
                                    'viewed' => ['Διαβασμένη', '#475569', '#f8fafc'],
                                    'shortlisted' => ['Σε λίστα', '#1d4ed8', '#f5f8ff'],
                                    'hired' => ['Πρόσληψη', '#15803d', '#f5fdf7'],
                                    'rejected' => ['Απορρίφθηκε', '#b3261e', '#fff7f6'],
                                    'withdrawn' => ['Αποσύρθηκε', '#6b7280', '#f8f9fa'],
                                ];
                                ?>
                                <div class="cand-list">
                                    <?php foreach ($recentApplications as $app) : ?>
                                        <?php $st = $appStatus[$app['status']] ?? [$app['status'], '#6b7280', '#f8f9fa']; ?>
                                        <div class="cand-row">
                                            <div class="cand-main">
                                                <a class="cand-name" href="<?php echo BASE_URL; ?>drivers/profile/<?php echo (int) $app['driver_id']; ?>">
                                                    <?php echo htmlspecialchars(trim($app['first_name'] . ' ' . $app['last_name'])); ?>
                                                </a>
                                                <span class="cand-listing">για: <?php echo htmlspecialchars($app['listing_title']); ?></span>
                                                <em class="cand-date"><?php echo date('d/m/Y', strtotime($app['created_at'])); ?></em>
                                            </div>
                                            <span class="cand-status" style="color: <?php echo $st[1]; ?>; background: <?php echo $st[2]; ?>;">
                                                <?php echo $st[0]; ?>
                                            </span>
                                            <a class="btn btn-sm btn-outline-primary" href="<?php echo BASE_URL; ?>job-applications/listing/<?php echo (int) $app['job_listing_id']; ?>">Διαχείριση</a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-3">
                                    <a href="<?php echo BASE_URL; ?>job-applications/company-applications" class="btn btn-primary">Όλες οι αιτήσεις</a>
                                </div>
                            <?php endif; ?>
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
                    <a href="<?php echo BASE_URL; ?>companies/messages" class="btn btn-secondary">
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
                        <a href="<?php echo BASE_URL; ?>companies/edit-profile" class="btn btn-primary">
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