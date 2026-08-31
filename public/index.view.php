<?php

/**
 * Αρχική σελίδα DriveJob. (ξαναγράφτηκε 01/09/2026)
 *
 * ΤΙ ΑΝΤΙΚΑΤΕΣΤΗΣΕ: δύο παραγράφους, εκ των οποίων η δεύτερη υποσχόταν
 * «Λογισμικό Διαχείρισης Ανθρώπινου Δυναμικού... διαχείριση πληρωμών» —
 * προϊόν που ΔΕΝ υπάρχει. Η αρχική είναι η πρώτη υπόσχεση προς τον
 * επισκέπτη· λέει μόνο αλήθειες και δείχνει πραγματικά νούμερα.
 *
 * Περιμένει (προαιρετικά): $homeStats [listings, companies, drivers]
 * από τον HomeController.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

include ROOT_DIR . '/src/Views/partials/header.php';

$homeStats = $homeStats ?? null;
?>

<main class="home-page">

    <?php /* ═══ HERO ═════════════════════════════════════════════════ */ ?>
    <section class="home-hero">
        <div class="home-hero-inner">
            <h1>Η δουλειά σου στον δρόμο<br>ξεκινά εδώ</h1>
            <p class="home-sub">Το DriveJob συνδέει επαγγελματίες οδηγούς και χειριστές μηχανημάτων έργου
                με μεταφορικές εταιρείες — με ταίριασμα βάσει πραγματικών προσόντων:
                διπλώματα, ΠΕΙ, ADR, ταχογράφος, εμπειρία, απόσταση.</p>
            <div class="home-cta">
                <a href="<?php echo BASE_URL; ?>drivers/register" class="btn-primary home-btn">Είμαι οδηγός — Εγγραφή</a>
                <a href="<?php echo BASE_URL; ?>companies/register" class="btn-secondary home-btn">Είμαι εταιρεία — Εγγραφή</a>
            </div>
            <p class="home-browse"><a href="<?php echo BASE_URL; ?>job-listings">Δες τις αγγελίες χωρίς εγγραφή →</a></p>
        </div>
    </section>

    <?php /* ═══ ΖΩΝΤΑΝΑ ΝΟΥΜΕΡΑ — μόνο αν υπάρχουν ═════════════════ */ ?>
    <?php if ($homeStats && (int) $homeStats['listings'] > 0) : ?>
        <section class="home-stats">
            <div class="home-stat">
                <strong><?php echo (int) $homeStats['listings']; ?></strong>
                <span>ανοιχτές θέσεις</span>
            </div>
            <div class="home-stat">
                <strong><?php echo (int) $homeStats['companies']; ?></strong>
                <span>εταιρείες</span>
            </div>
            <div class="home-stat">
                <strong><?php echo (int) $homeStats['drivers']; ?></strong>
                <span>οδηγοί</span>
            </div>
        </section>
    <?php endif; ?>

    <?php /* ═══ ΠΩΣ ΛΕΙΤΟΥΡΓΕΙ ══════════════════════════════════════ */ ?>
    <section class="home-how">
        <div class="home-how-col">
            <h2>Για οδηγούς</h2>
            <ol>
                <li><strong>Φτιάξε το προφίλ σου μία φορά.</strong> Διπλώματα, ΠΕΙ, πιστοποιητικά,
                    προϋπηρεσία — γίνονται αυτόματα και επαγγελματικό βιογραφικό.</li>
                <li><strong>Δες τι σου ταιριάζει.</strong> Η πλατφόρμα συγκρίνει τα προσόντα σου
                    με κάθε αγγελία και σου δείχνει πού καλύπτεις τις απαιτήσεις — και τι θα
                    σου άνοιγε περισσότερες πόρτες.</li>
                <li><strong>Κάνε αίτηση με ένα κλικ.</strong> Τα στοιχεία επικοινωνίας σου
                    αποκαλύπτονται μόνο όταν μια εταιρεία σε προεπιλέξει — εσύ ελέγχεις
                    τι φαίνεται.</li>
            </ol>
        </div>
        <div class="home-how-col">
            <h2>Για εταιρείες</h2>
            <ol>
                <li><strong>Δημοσίευσε την αγγελία με πραγματικές απαιτήσεις.</strong>
                    Κατηγορία διπλώματος, ΠΕΙ, ADR, ταχογράφος, εμπειρία, έδρα.</li>
                <li><strong>Δες υποψηφίους που όντως ταιριάζουν.</strong> Κάθε αίτηση έρχεται
                    με το προφίλ προσόντων του οδηγού — επαληθεύσιμα χαρτιά, όχι λόγια.</li>
                <li><strong>Προεπίλεξε και μίλησε.</strong> Συνομιλία μέσα στην πλατφόρμα,
                    πλήρη στοιχεία επικοινωνίας μετά την προεπιλογή.</li>
            </ol>
        </div>
    </section>

    <?php /* ═══ ΚΑTHΓΟΡΙΕΣ ══════════════════════════════════════════ */ ?>
    <section class="home-cats">
        <h2>Τι θέσεις θα βρεις</h2>
        <div class="home-cat-grid">
            <a href="<?php echo BASE_URL; ?>job-listings" class="home-cat">
                <span class="home-cat-title">Εμπορευματικές μεταφορές</span>
                <span class="home-cat-sub">Βαν, φορτηγά, νταλίκες, βυτιοφόρα, ψυγεία — εθνικά και διεθνή δρομολόγια</span>
            </a>
            <a href="<?php echo BASE_URL; ?>job-listings" class="home-cat">
                <span class="home-cat-title">Επιβατικές μεταφορές</span>
                <span class="home-cat-sub">Λεωφορεία, υπεραστικές γραμμές, τουριστικά πούλμαν, μεταφορά προσωπικού</span>
            </a>
            <a href="<?php echo BASE_URL; ?>job-listings" class="home-cat">
                <span class="home-cat-title">Μηχανήματα έργου</span>
                <span class="home-cat-sub">Χειριστές και βοηθοί — εκσκαφείς, φορτωτές, γερανοί, γκρέιντερ</span>
            </a>
        </div>
    </section>

    <?php /* ═══ ΓΙΑΤΙ DRIVEJOB ══════════════════════════════════════ */ ?>
    <section class="home-why">
        <h2>Φτιαγμένο από ανθρώπους του κλάδου</h2>
        <p>Το DriveJob δημιουργήθηκε από τον <strong>Εκπαιδευτικό Όμιλο Thessdrive</strong> —
            με χρόνια εμπειρίας στην εκπαίδευση επαγγελματιών οδηγών και χειριστών στη
            Θεσσαλονίκη. Ξέρουμε τι σημαίνει ΠΕΙ, ADR και άδεια χειριστή, γιατί τα
            διδάσκουμε καθημερινά. Γι' αυτό το ταίριασμα εδώ μετράει αυτά που μετράνε
            πραγματικά στον δρόμο.</p>
    </section>

</main>

<style>
    .home-page { margin-left: 0; }

    .home-hero {
        background: linear-gradient(180deg, var(--dj-brand, #aa3636) 0%, var(--dj-brand-strong, #8f2b2b) 100%);
        color: #fff;
        padding: 3.5rem 1rem 3rem;
        text-align: center;
    }
    .home-hero-inner { max-width: var(--dj-container-narrow, 1180px); margin: 0 auto; }
    .home-hero h1 { margin: 0 0 1rem; font-size: clamp(1.8rem, 4vw, 2.8rem); line-height: 1.2; color: #fff; }
    .home-sub {
        max-width: 720px; margin: 0 auto 1.8rem; font-size: 1.05rem;
        line-height: 1.6; color: #f6dcdc;
    }
    .home-cta { display: flex; justify-content: center; gap: .8rem; flex-wrap: wrap; }
    .home-btn { font-size: 1.02rem; padding: .8rem 1.6rem; }
    .home-cta .btn-secondary { background: #fff; color: var(--dj-brand, #aa3636); }
    .home-cta .btn-secondary:hover { background: var(--dj-brand-soft, #f9eded); }
    .home-browse { margin: 1.2rem 0 0; }
    .home-browse a { color: #fff; text-decoration: underline; font-size: .95rem; }

    .home-stats {
        display: flex; justify-content: center; gap: clamp(1.5rem, 6vw, 5rem);
        flex-wrap: wrap; padding: 1.6rem 1rem;
        background: var(--dj-surface, #fff);
        border-bottom: 1px solid var(--dj-line, #e5e7eb);
    }
    .home-stat { text-align: center; }
    .home-stat strong { display: block; font-size: 2rem; color: var(--dj-brand, #aa3636); }
    .home-stat span { color: var(--dj-muted, #6b7280); font-size: .9rem; }

    .home-how {
        max-width: var(--dj-container-narrow, 1180px); margin: 0 auto;
        padding: 2.5rem 1rem 1rem;
        display: flex; gap: 2rem; flex-wrap: wrap;
    }
    .home-how-col {
        flex: 1 1 380px;
        background: var(--dj-surface, #fff);
        border: 1px solid var(--dj-line, #e5e7eb);
        border-radius: var(--dj-radius, 10px);
        box-shadow: var(--dj-shadow);
        padding: 1.5rem 1.75rem;
    }
    .home-how-col h2 { margin: 0 0 1rem; font-size: 1.25rem; color: var(--dj-brand, #aa3636); }
    .home-how-col ol { margin: 0; padding-left: 1.2rem; display: flex; flex-direction: column; gap: .8rem; }
    .home-how-col li { line-height: 1.55; color: var(--dj-ink-soft, #374151); }

    .home-cats { max-width: var(--dj-container-narrow, 1180px); margin: 0 auto; padding: 2rem 1rem 1rem; }
    .home-cats h2, .home-why h2 { text-align: center; font-size: 1.4rem; margin: 0 0 1.3rem; }
    .home-cat-grid { display: flex; gap: 1rem; flex-wrap: wrap; }
    .home-cat {
        flex: 1 1 280px;
        background: var(--dj-surface, #fff);
        border: 1px solid var(--dj-line, #e5e7eb);
        border-radius: var(--dj-radius, 10px);
        box-shadow: var(--dj-shadow);
        padding: 1.3rem 1.4rem;
        text-decoration: none;
        transition: box-shadow .15s ease;
    }
    .home-cat:hover { box-shadow: var(--dj-shadow-hover); }
    .home-cat-title { display: block; font-weight: 700; color: var(--dj-brand, #aa3636); margin-bottom: .35rem; }
    .home-cat-sub { color: var(--dj-muted, #6b7280); font-size: .9rem; line-height: 1.5; }

    .home-why {
        max-width: 820px; margin: 0 auto; padding: 2rem 1rem 3rem; text-align: center;
    }
    .home-why p { color: var(--dj-ink-soft, #374151); line-height: 1.7; margin: 0; }
</style>

<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>
