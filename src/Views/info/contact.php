<?php

// src/Views/info/contact.php — Σελίδα Επικοινωνίας (route: GET /contact, υποβολή: POST /contact)
// Ξαναγράφτηκε 01/09/2026: η φόρμα δεν είχε ΚΑΝΕΝΑ στυλ — ετικέτες και
// πεδία κυλούσαν inline σε μία γραμμή. Τώρα: κάρτα φόρμας + κάρτα
// στοιχείων επικοινωνίας, στη γλώσσα του theme.css.

use Drivejob\Core\Session;

include ROOT_DIR . '/src/Views/partials/header.php';

$successMessage = Session::get('success_message');
$errorMessage = Session::get('error_message');
$old = Session::get('old_input') ?? [];
Session::remove('success_message');
Session::remove('error_message');
Session::remove('old_input');
?>
<main>
    <div class="container contact-page">
        <h1>Επικοινωνία</h1>
        <p class="contact-lead">Ερώτηση, πρόβλημα ή ιδέα για την πλατφόρμα; Γράψε μας —
            απαντάμε το συντομότερο δυνατό.</p>

        <?php if ($successMessage) : ?>
            <div class="success-message"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($errorMessage) : ?>
            <div class="error-message"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="contact-grid">
            <form action="<?= BASE_URL ?>contact" method="POST" class="contact-card contact-form">
                <input type="hidden" name="csrf_token" value="<?= Session::get('csrf_token') ?>">

                <div class="cfield">
                    <label for="name">Όνομα</label>
                    <input type="text" id="name" name="name" placeholder="Το όνομά σας" required
                           value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="cfield">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Το email σας" required
                           value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="cfield">
                    <label for="message">Μήνυμα</label>
                    <textarea id="message" name="message" placeholder="Το μήνυμά σας" rows="6" required><?= htmlspecialchars($old['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <button type="submit" class="btn-primary">Αποστολή μηνύματος</button>
            </form>

            <aside class="contact-card cinfo-card">
                <h2>Στοιχεία επικοινωνίας</h2>
                <p><strong>ΕΚΠΑΙΔΕΥΤΙΚΟΣ ΟΜΙΛΟΣ THESSDRIVE ΙΚΕ</strong></p>
                <p>Κλαυδιανού 29, 54632<br>Θεσσαλονίκη</p>
                <p><a href="mailto:info@drivejob.gr">info@drivejob.gr</a></p>
                <h2>Πριν γράψεις</h2>
                <p>Ίσως η απάντηση υπάρχει ήδη στις
                    <a href="<?= BASE_URL ?>faq">Συχνές Ερωτήσεις</a>.</p>
            </aside>
        </div>
    </div>
</main>

<style>
    .contact-page { max-width: var(--dj-container-narrow, 1000px); padding-bottom: 2.5rem; }
    .contact-page h1 { text-align: left; margin: 1.5rem 0 .4rem; }
    .contact-lead { color: var(--dj-muted, #6b7280); margin: 0 0 1.4rem; }

    .contact-grid { display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: flex-start; }

    .contact-card {
        background: var(--dj-surface, #fff);
        border: 1px solid var(--dj-line, #e5e7eb);
        border-radius: var(--dj-radius, 10px);
        box-shadow: var(--dj-shadow);
        padding: 1.5rem 1.75rem;
    }

    .contact-form { flex: 1 1 420px; }
    .cinfo-card { flex: 1 1 260px; max-width: 340px; }

    .cfield { margin-bottom: 1rem; }
    .cfield label {
        display: block; margin-bottom: .35rem;
        font-weight: 500; font-size: .93rem; color: var(--dj-ink-soft, #374151);
    }
    .cfield input, .cfield textarea {
        width: 100%; box-sizing: border-box;
        padding: .6rem .75rem;
        border: 1px solid #d1d5db;
        border-radius: var(--dj-radius-sm, 7px);
        font: inherit; background: #fff;
    }
    .cfield textarea { resize: vertical; }
    .cfield input:focus, .cfield textarea:focus {
        outline: 2px solid var(--dj-brand-soft, #f9eded);
        border-color: var(--dj-brand, #aa3636);
    }

    .cinfo-card h2 { font-size: 1rem; color: var(--dj-brand, #aa3636); margin: 0 0 .5rem; }
    .cinfo-card h2 + p { margin-top: 0; }
    .cinfo-card h2:not(:first-child) { margin-top: 1.3rem; }
    .cinfo-card p { margin: 0 0 .5rem; line-height: 1.55; color: var(--dj-ink-soft, #374151); }
    .cinfo-card a { color: var(--dj-brand, #aa3636); }
</style>

<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>
