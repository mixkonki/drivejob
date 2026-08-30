<?php
/**
 * Δημόσια φόρμα σύστασης — η πλευρά του ΕΡΓΟΔΟΤΗ. (01/09/2026)
 *
 * ΣΧΕΔΙΑΣΤΙΚΗ ΑΡΧΗ: ο άνθρωπος που την ανοίγει κάνει ΧΑΡΗ. Δεν έχει
 * λογαριασμό, δεν ξέρει την πλατφόρμα, και πιθανότατα την ανοίγει από
 * κινητό μέσα από Viber. Άρα: τρία υποχρεωτικά πεδία (σύνολο, θα τον
 * ξαναπροσλάμβανες, τίποτε άλλο), τα υπόλοιπα προαιρετικά και διπλωμένα.
 * Κάθε πεδίο παραπάνω κοστίζει απαντήσεις.
 *
 * Περιμένει στο scope: $invite (ή null), $refError, $alreadyDone
 */

$invite = $invite ?? null;
$refError = $refError ?? null;
$alreadyDone = $alreadyDone ?? false;

$driverName = $invite ? trim($invite['first_name'] . ' ' . $invite['last_name']) : '';

/* Το λεκτικό ακολουθεί τη σχέση: ο πελάτης δεν «απασχόλησε» τον οδηγό —
   συνεργάστηκε μαζί του. (01/09) */
$relation = $invite['reviewer_relation'] ?? 'employer';
$relationText = [
    'employer' => 'εργάστηκε στην επιχείρησή σας',
    'client' => 'συνεργάστηκε μαζί σας ως επαγγελματίας οδηγός',
    'supervisor' => 'εργάστηκε υπό την εποπτεία σας',
][$relation] ?? 'εργάστηκε στην επιχείρησή σας';
?>
<?= \Drivejob\Helpers\Asset::css('css/driver-references.css') ?>

<main>
    <div class="container refs-container refs-public">

        <?php if ($refError !== null) : ?>

            <div class="refs-card refs-card--error">
                <h1>Ο σύνδεσμος δεν ισχύει</h1>
                <p><?php echo htmlspecialchars($refError, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

        <?php elseif ($alreadyDone) : ?>

            <div class="refs-card refs-card--ok">
                <h1>Η σύσταση έχει καταχωρηθεί</h1>
                <p>Έχετε ήδη απαντήσει για τον οδηγό
                   <strong><?php echo htmlspecialchars($driverName, ENT_QUOTES, 'UTF-8'); ?></strong>.
                   Ευχαριστούμε για τον χρόνο σας!</p>
            </div>

        <?php else : ?>

            <div class="refs-card">
                <h1>Σύσταση για τον οδηγό <?php echo htmlspecialchars($driverName, ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="refs-lead">
                    Ο/η <strong><?php echo htmlspecialchars($driverName, ENT_QUOTES, 'UTF-8'); ?></strong>
                    δήλωσε ότι <?php echo $relationText; ?>
                    <?php if (!empty($invite['reviewer_company'])) : ?>
                        (<strong><?php echo htmlspecialchars($invite['reviewer_company'], ENT_QUOTES, 'UTF-8'); ?></strong>)
                    <?php endif; ?>
                    <?php if (!empty($invite['employment_from'])) : ?>
                        (<?php echo date('m/Y', strtotime($invite['employment_from'])); ?>
                        – <?php echo !empty($invite['employment_to']) ? date('m/Y', strtotime($invite['employment_to'])) : 'σήμερα'; ?>)
                    <?php endif; ?>
                    και ζητά την επαγγελματική σας γνώμη.
                    Χρειάζονται <strong>2 λεπτά</strong> — δεν απαιτείται λογαριασμός.
                </p>

                <form id="ref-public-form" autocomplete="off">

                    <?php /* ── 1. Συνολική εντύπωση ─────────────────────── */ ?>
                    <fieldset class="refs-field">
                        <legend>Πώς θα αξιολογούσατε συνολικά τον οδηγό; <i>*</i></legend>
                        <div class="refs-stars" role="radiogroup" aria-label="Συνολική βαθμολογία">
                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                <label class="refs-star">
                                    <input type="radio" name="rating" value="<?php echo $i; ?>" required>
                                    <span aria-hidden="true">★</span>
                                    <em><?php echo ['Κακός', 'Μέτριος', 'Καλός', 'Πολύ καλός', 'Εξαιρετικός'][$i - 1]; ?></em>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </fieldset>

                    <?php /* ── 2. Το ερώτημα που μετράει ────────────────────
                       Δεσμευτικό ερώτημα = ειλικρινής απάντηση. Στα αστέρια
                       όλοι βάζουν 4-5· στο «θα τον έπαιρνες πίσω;» όχι. */ ?>
                    <fieldset class="refs-field">
                        <legend><?php echo $relation === 'client' ? 'Θα του αναθέτατε ξανά δουλειά;' : 'Θα τον ξαναπροσλαμβάνατε;'; ?> <i>*</i></legend>
                        <div class="refs-rehire">
                            <label><input type="radio" name="would_rehire" value="1" required> <span>Ναι, χωρίς δεύτερη σκέψη</span></label>
                            <label><input type="radio" name="would_rehire" value="0"> <span>Όχι / Έχω επιφυλάξεις</span></label>
                        </div>
                    </fieldset>

                    <?php /* ── 3. Προαιρετικά, διπλωμένα ─────────────────── */ ?>
                    <details class="refs-more">
                        <summary>Θέλετε να πείτε περισσότερα; (προαιρετικό)</summary>

                        <div class="refs-facets">
                            <?php
                            $facets = [
                                'reliability_rating' => 'Αξιοπιστία & συνέπεια',
                                'driving_skills_rating' => 'Οδηγική ικανότητα',
                                'professionalism_rating' => 'Επαγγελματισμός',
                                'communication_rating' => 'Επικοινωνία & συνεργασία',
                                'technical_skills_rating' => 'Φροντίδα οχήματος',
                            ];
                            ?>
                            <?php foreach ($facets as $fname => $flabel) : ?>
                                <div class="refs-facet">
                                    <span><?php echo $flabel; ?></span>
                                    <div class="refs-stars refs-stars--small" role="radiogroup" aria-label="<?php echo $flabel; ?>">
                                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                                            <label class="refs-star"><input type="radio" name="<?php echo $fname; ?>" value="<?php echo $i; ?>"><span aria-hidden="true">★</span></label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <label class="refs-comment">
                            Σχόλιο
                            <textarea name="comment" rows="3" maxlength="2000"
                                      placeholder="Ό,τι θα λέγατε σε συνάδελφό σας που ρωτά για τον οδηγό."></textarea>
                        </label>
                    </details>

                    <div class="refs-actions">
                        <button type="submit" class="btn-primary" id="ref-public-submit">Υποβολή σύστασης</button>
                    </div>

                    <div id="ref-public-msg" class="refs-msg" hidden></div>

                    <p class="refs-fineprint">
                        Η σύσταση καταχωρείται επώνυμα
                        (<?php echo htmlspecialchars($invite['reviewer_name'], ENT_QUOTES, 'UTF-8'); ?>,
                        <?php echo htmlspecialchars($invite['reviewer_company'], ENT_QUOTES, 'UTF-8'); ?>)
                        και συνυπολογίζεται στη συνολική εικόνα του οδηγού μαζί με άλλες πηγές.
                        Ο οδηγός δεν βλέπει την επιμέρους βαθμολογία που δώσατε — μόνο τον συνολικό
                        μέσο όρο όλων των συστάσεων.
                    </p>
                </form>
            </div>

        <?php endif; ?>
    </div>
</main>

<?= \Drivejob\Helpers\Asset::js('js/reference-form.js', false) ?>
