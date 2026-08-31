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
    /* Το job-listings.css (φορτώνεται καθολικά) ορίζει main > .container
       στο στενό πλάτος λίστας — το προφίλ θέλει το πλήρες (01/09). */
    main > .container { max-width: var(--dj-container, 1400px); }

    /* Ξαναγράφτηκε 01/09/2026 πάνω στο κεντρικό theme.css (design tokens):
       ίδια γλώσσα με το προφίλ οδηγού — κόκκινη ενεργή καρτέλα αντί για
       το ξένο μπλε, ίδιες γωνίες/σκιές/αναλογίες παντού. */

    /* Διάταξη Επισκόπησης: κύρια στήλη + πλαϊνή ΜΕΣΑ στην καρτέλα,
       όπως στο προφίλ οδηγού. Οι καρτέλες πιάνουν όλο το πλάτος. */
    .ov-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 25px;
        align-items: flex-start;
    }

    .ov-main {
        flex: 1 1 480px;
        min-width: 0;
    }

    .ov-side {
        flex: 0 1 350px;
        min-width: min(300px, 100%);
    }

    /* Καρτέλες — πανομοιότυπες με του οδηγού (driver-profile.css). */
    .profile-tabs {
        background: var(--dj-surface);
        border-radius: var(--dj-radius);
        box-shadow: var(--dj-shadow);
        border: 1px solid var(--dj-line);
        overflow: hidden;
    }

    .tabs-nav {
        display: flex;
        flex-wrap: wrap;
        background: var(--dj-surface-alt);
        border-bottom: 1px solid var(--dj-line);
        margin: 0;
        padding: 0;
    }

    .tab-count {
        display: inline-block;
        min-width: 1.4em;
        padding: .05rem .35rem;
        border-radius: var(--dj-radius-pill);
        background: var(--dj-brand);
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
        /* Μικρότερο padding από του οδηγού: εδώ οι καρτέλες ζουν σε στήλη
           ~440px και με 15px/20px το «Υποψήφιοι» έπεφτε σε δεύτερη σειρά. */
        padding: 12px 14px;
        background: none;
        border: none;
        color: var(--dj-muted);
        font: inherit;
        font-size: .95rem;
        font-weight: 500;
        cursor: pointer;
        position: relative;
        white-space: nowrap;
        transition: color .2s ease;
    }

    .tab-btn:hover {
        color: var(--dj-brand);
        background: var(--dj-line-soft);
    }

    .tab-btn.active {
        color: var(--dj-brand);
        font-weight: 600;
        background: var(--dj-surface);
    }

    .tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: var(--dj-brand);
    }

    .tab-content {
        padding: 25px;
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
        background: var(--dj-surface);
        padding: 25px;
        border-radius: var(--dj-radius);
        border: 1px solid var(--dj-line);
        box-shadow: var(--dj-shadow);
        margin-bottom: 25px;
    }

    /* Διάταξη κεφαλίδας όπως στον οδηγό (01/09): λογότυπο αριστερά,
       στοιχεία στη μέση, πάνελ «Στατιστικά» δεξιά. Οι .row/.col είναι
       bootstrap-ικά ονόματα χωρίς Bootstrap — τους δίνουμε εδώ το flex
       που περίμεναν, αλλιώς στοιβάζονται κάθετα κι αφήνουν κενό δεξιά. */
    .profile-header .row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 1rem 1.25rem;
        width: 100%;
    }
    .profile-header .col { flex: 1 1 180px; }
    .profile-header .col-auto { flex: 0 0 auto; }
    .profile-header .profile-actions { justify-content: flex-start; }
    .profile-stats-header {
        margin-top: 0;
        min-width: 300px;
        /* Χωρίς όριο, τα 4 κουμπιά σε μία σειρά πλάταιναν το πάνελ ~660px
           και το έριχναν ΚΑΤΩ από το λογότυπο — με όριο, αναδιπλώνονται
           2×2 και το πάνελ κάθεται δεξιά όπως στου οδηγού. */
        max-width: 430px;
    }
    .profile-actions--panel {
        margin-top: 18px;
        justify-content: center !important;
    }
    .profile-stats { gap: 1.5rem; }

    @media (max-width: 760px) {
        .profile-header .row { justify-content: center; text-align: center; }
        .profile-header .col h1 { text-align: center; }
        .profile-location { justify-content: center; }
        .profile-stats-header { width: 100%; min-width: 0; }
    }

    .profile-stats-header {
        background: var(--dj-surface-alt);
        padding: 20px;
        border-radius: var(--dj-radius-sm);
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
        color: var(--dj-brand);
        display: block;
    }

    .stat-label {
        font-size: 14px;
        color: var(--dj-muted);
        margin-top: 5px;
    }

    /* Sidebar sections */
    .sidebar-section {
        background: var(--dj-surface);
        border-radius: var(--dj-radius);
        border: 1px solid var(--dj-line);
        box-shadow: var(--dj-shadow);
        padding: 20px;
        margin-bottom: 20px;
    }

    .sidebar-section h3 {
        font-size: 1.05rem;
        margin: 0 0 15px;
        color: var(--dj-ink);
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
        color: var(--dj-brand);
        width: 20px;
    }

    .quick-actions { display: flex; flex-wrap: wrap; gap: .5rem; }

    /* Τα btn-outline-* είναι Bootstrap-ικά ονόματα χωρίς Bootstrap —
       τους δίνουμε όψη διακριτικού κουμπιού στο ύφος του site. */
    .btn-outline-primary,
    .btn-outline-info {
        display: inline-block;
        padding: .3rem .7rem;
        border: 1px solid var(--dj-brand);
        border-radius: var(--dj-radius-sm);
        color: var(--dj-brand);
        background: var(--dj-surface);
        text-decoration: none;
        font-size: .85rem;
        white-space: nowrap;
    }

    .btn-outline-primary:hover,
    .btn-outline-info:hover {
        background: var(--dj-brand-soft);
        text-decoration: none;
    }

    @media (max-width: 900px) {
        .ov-side {
            flex: 1 1 100%;
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
                        <?php /* Τα κύρια κουμπιά ΜΕΣΑ στο πάνελ, όπως το
                           «Στατιστικά Προφίλ» του οδηγού — η μεσαία στήλη
                           μένει καθαρή για όνομα/έδρα (01/09). */ ?>
                        <div class="profile-actions profile-actions--panel">
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

        <?php /* Αναδιάρθρωση 01/09 (αίτημα Κώστα): οι καρτέλες πιάνουν ΟΛΟ το
           πλάτος όπως στο προφίλ οδηγού, και η πλαϊνή στήλη (Μηνύματα/
           Επικοινωνία/Ενέργειες/Χάρτης) ζει ΜΕΣΑ στην Επισκόπηση —
           ακριβώς όπως η στήλη Επικοινωνία/Περιοχή Εργασίας του οδηγού. */ ?>
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
                        <!-- Overview Tab: κύρια στήλη + πλαϊνή, όπως στον οδηγό -->
                        <div class="tab-pane active" id="overview">
                        <div class="ov-grid">
                        <div class="ov-main">
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
                        </div><!-- /.ov-main -->

                        <aside class="ov-side">
                            <?php include __DIR__ . '/partials/_profile-side.php'; ?>
                        </aside>
                        </div><!-- /.ov-grid -->
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