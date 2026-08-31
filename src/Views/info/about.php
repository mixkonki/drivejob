<?php

// src/Views/info/about.php — Σελίδα «Σχετικά με Εμάς» (route: /about)
// Ξαναγράφτηκε 01/09/2026: πραγματική ιστορία αντί για γενικόλογα.
include ROOT_DIR . '/src/Views/partials/header.php';
?>
<main>
    <div class="container about-page">
        <h1>Σχετικά με το DriveJob</h1>

        <p>Το DriveJob γεννήθηκε από μια απλή διαπίστωση: ο κλάδος των μεταφορών ψάχνει
            οδηγούς και οι οδηγοί ψάχνουν δουλειά — αλλά οι γενικές πλατφόρμες αγγελιών δεν
            καταλαβαίνουν τι σημαίνει ΠΕΙ, ADR, ψηφιακός ταχογράφος ή άδεια χειριστή
            μηχανημάτων έργου. Εδώ, αυτά είναι η γλώσσα της πλατφόρμας.</p>

        <h2>Ποιοι είμαστε</h2>
        <p>Πίσω από το DriveJob βρίσκεται ο <strong>Εκπαιδευτικός Όμιλος Thessdrive</strong>
            (ΕΚΠΑΙΔΕΥΤΙΚΟΣ ΟΜΙΛΟΣ THESSDRIVE ΙΚΕ), με έδρα τη Θεσσαλονίκη και χρόνια
            παρουσίας στην εκπαίδευση επαγγελματιών του κλάδου: κατάρτιση ΠΕΙ, πιστοποιήσεις
            ADR, άδειες χειριστών μηχανημάτων έργου. Εκπαιδεύουμε καθημερινά τους ανθρώπους
            που κρατούν τις μεταφορές σε κίνηση — και χτίσαμε την πλατφόρμα που θα θέλαμε
            να είχαν όταν ψάχνουν το επόμενο βήμα τους.</p>

        <h2>Τι πιστεύουμε</h2>
        <p><strong>Τα προσόντα μετράνε περισσότερο από τα λόγια.</strong> Το ταίριασμα στο
            DriveJob στηρίζεται σε επαληθεύσιμα στοιχεία — άδειες, πιστοποιητικά, ένσημα,
            επώνυμες συστάσεις — όχι σε κενές αυτοπεριγραφές.</p>
        <p><strong>Ο οδηγός ελέγχει τα δεδομένα του.</strong> Στοιχεία επικοινωνίας
            αποκαλύπτονται σταδιακά και μόνο όταν υπάρχει πραγματικό αμοιβαίο ενδιαφέρον·
            το τι εμφανίζεται δημόσια το αποφασίζει ο ίδιος.</p>
        <p><strong>Λέμε μόνο ό,τι ισχύει.</strong> Η πλατφόρμα βρίσκεται σε φάση beta και
            εξελίσσεται συνεχώς — ό,τι βλέπεις να λειτουργεί, λειτουργεί στα αλήθεια.</p>

        <h2>Ο όμιλος</h2>
        <p>Το DriveJob είναι μέλος του οικοσυστήματος Drive:
            <a href="https://thessdrive.gr" target="_blank" rel="noopener">thessdrive.gr</a> (εκπαίδευση οδηγών),
            <a href="https://thessadr.gr" target="_blank" rel="noopener">thessadr.gr</a> (ADR &amp; ΠΕΕ),
            <a href="https://xeiristis.gr" target="_blank" rel="noopener">xeiristis.gr</a> (άδειες χειριστών),
            <a href="https://drivenews.gr" target="_blank" rel="noopener">drivenews.gr</a> (νέα του κλάδου).</p>

        <h2>Επικοινωνία</h2>
        <p>Κλαυδιανού 29, 54632 Θεσσαλονίκη ·
            <a href="mailto:info@drivejob.gr">info@drivejob.gr</a> ·
            <a href="<?php echo BASE_URL; ?>contact">φόρμα επικοινωνίας</a></p>
    </div>
</main>

<style>
    .about-page { max-width: var(--dj-container-narrow, 900px); padding-bottom: 2.5rem; }
    .about-page h1 { text-align: left; margin: 1.5rem 0 1rem; }
    .about-page h2 { font-size: 1.15rem; color: var(--dj-brand, #aa3636); margin: 1.6rem 0 .5rem; }
    .about-page p { line-height: 1.7; color: var(--dj-ink-soft, #374151); margin: 0 0 .7rem; }
</style>

<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>
