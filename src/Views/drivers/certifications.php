<?php

/**
 * Σεμινάρια & Πιστοποιητικά — GET /drivers/certifications
 *
 * Μεταβλητές από τον DriversController::certifications():
 *   $rows        — εγγραφές driver_certifications
 *   $categories  — κωδικός → ετικέτα θεματολογίας
 *   $transports  — κωδικός → ετικέτα είδους μεταφορών
 *
 * Ίδια φιλοσοφία με την προϋπηρεσία και τις γλώσσες: κάθε πιστοποίηση
 * αποθηκεύεται τη στιγμή της «Προσθήκης» (μαζί με το αρχείο της),
 * διαγράφεται επιτόπου, και η λίστα ζωγραφίζεται από τη βάση. Αυτή η
 * σελίδα αντικαθιστά το κουμπί «Διαχείριση Πιστοποιητικών» που έδειχνε
 * σε ανύπαρκτη διαδρομή (404) από την πρώτη μέρα.
 */

$rows = $rows ?? [];
$categories = $categories ?? [];
$transports = $transports ?? [];
$today = date('Y-m-d');
?>

<style>
    .crt-wrap { max-width: 1150px; margin: 1.5rem auto; padding: 0 1rem; }
    .crt-grid { display: grid; grid-template-columns: minmax(300px, 430px) 1fr; gap: 1.5rem; align-items: start; }
    .crt-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 1.2rem 1.4rem; }
    .crt-card h2 { font-size: 1.05rem; margin: 0 0 1rem; }
    .crt-f { margin-bottom: .85rem; }
    .crt-f label { display: block; font-size: .85rem; font-weight: 600; color: #374151; margin-bottom: .3rem; }
    .crt-f input, .crt-f select, .crt-f textarea {
        width: 100%; padding: .5rem .65rem; border: 1px solid #d1d5db; border-radius: 6px;
        font-family: inherit; font-size: .92rem; box-sizing: border-box; background: #fff; }
    .crt-two { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
    .crt-dates { display: flex; align-items: center; gap: .35rem; }
    .crt-dates input { flex: 1; min-width: 0; }
    .crt-cal { border: 1px solid #d1d5db; background: #f9fafb; border-radius: 6px;
               padding: .4rem .5rem; cursor: pointer; }
    .crt-hint { font-size: .78rem; color: #6b7280; margin-top: .25rem; }
    .crt-add { background: #b3261e; color: #fff; border: 0; border-radius: 6px;
               padding: .6rem 1.3rem; font-weight: 600; cursor: pointer; font-size: .95rem; }
    .crt-add:disabled { opacity: .6; cursor: wait; }
    .crt-msg { margin-top: .8rem; padding: .55rem .8rem; border-radius: 6px; font-size: .88rem; display: none; }
    .crt-msg.ok { background: #dcfce7; color: #166534; display: block; }
    .crt-msg.err { background: #fee2e2; color: #991b1b; display: block; }
    .crt-item { border: 1px solid #e5e7eb; border-radius: 8px; padding: .8rem 1rem; margin-bottom: .7rem; }
    .crt-item .t { display: flex; justify-content: space-between; align-items: start; gap: .8rem; }
    .crt-item h3 { margin: 0; font-size: .98rem; }
    .crt-item .meta { color: #6b7280; font-size: .83rem; margin-top: .3rem; display: flex; flex-wrap: wrap; gap: .4rem 1rem; }
    .crt-badge { font-size: .74rem; font-weight: 600; padding: 2px 8px; border-radius: 999px; background: #eef2ff; color: #3730a3; }
    .crt-badge.expired { background: #fee2e2; color: #991b1b; }
    .crt-badge.valid { background: #dcfce7; color: #166534; }
    .crt-del { border: 1px solid #fca5a5; background: #fff; color: #b91c1c; border-radius: 6px;
               padding: .25rem .6rem; cursor: pointer; font-size: .8rem; white-space: nowrap; }
    .crt-file { font-size: .83rem; }
    .crt-empty { color: #6b7280; padding: 1rem 0; }
    @media (max-width: 880px) { .crt-grid { grid-template-columns: 1fr; } .crt-two { grid-template-columns: 1fr; } }
</style>

<main class="crt-wrap">
    <h1 style="font-size:1.3rem;">Σεμινάρια & Πιστοποιητικά</h1>
    <p style="color:#6b7280;">Κάθε πιστοποίηση αποθηκεύεται αμέσως με το «Προσθήκη» — μαζί με τη βεβαίωσή της αν τη διαθέτεις.</p>

    <div class="crt-grid">
        <div class="crt-card">
            <h2>Προσθήκη Πιστοποίησης</h2>

            <div class="crt-f">
                <label for="crt_title">Τίτλος *</label>
                <input type="text" id="crt_title" maxlength="255" placeholder="π.χ. Σεμινάριο ασφαλούς φόρτωσης">
            </div>

            <div class="crt-f">
                <label for="crt_provider">Πάροχος / Οργανισμός</label>
                <input type="text" id="crt_provider" maxlength="255" placeholder="π.χ. ΣΕΚΑΜ, IRU, ΚΕΚ">
            </div>

            <div class="crt-two">
                <div class="crt-f">
                    <label for="crt_category">Θεματολογία</label>
                    <select id="crt_category">
                        <option value="">Επιλέξτε...</option>
                        <?php foreach ($categories as $code => $label) : ?>
                            <option value="<?= $code ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crt-f">
                    <label for="crt_transport">Αφορά</label>
                    <select id="crt_transport">
                        <?php foreach ($transports as $code => $label) : ?>
                            <option value="<?= $code ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="crt-two">
                <div class="crt-f">
                    <label for="crt_date">Ημ. Απόκτησης</label>
                    <div class="crt-dates">
                        <input type="date" id="crt_date" max="<?= $today ?>">
                        <button type="button" class="crt-cal" data-for="crt_date" title="Ημερολόγιο">📅</button>
                    </div>
                </div>
                <div class="crt-f">
                    <label for="crt_expiry">Ημ. Λήξης</label>
                    <div class="crt-dates">
                        <input type="date" id="crt_expiry">
                        <button type="button" class="crt-cal" data-for="crt_expiry" title="Ημερολόγιο">📅</button>
                    </div>
                    <div class="crt-hint">Κενό αν δεν λήγει.</div>
                </div>
            </div>

            <div class="crt-two">
                <div class="crt-f">
                    <label for="crt_duration">Διάρκεια (ώρες)</label>
                    <input type="number" id="crt_duration" min="0" max="2000">
                </div>
                <div class="crt-f">
                    <label for="crt_file">Βεβαίωση (PDF/εικόνα)</label>
                    <input type="file" id="crt_file" accept=".pdf,.jpg,.jpeg,.png">
                </div>
            </div>

            <div class="crt-f">
                <label for="crt_description">Περιγραφή <span style="font-weight:400;color:#9ca3af;">(προαιρετικό)</span></label>
                <textarea id="crt_description" rows="2"></textarea>
            </div>

            <button type="button" id="crt-add-btn" class="crt-add">Προσθήκη</button>
            <div id="crt-msg" class="crt-msg"></div>
        </div>

        <div class="crt-card">
            <h2>Καταχωρημένες Πιστοποιήσεις</h2>

            <div id="crt-list">
                <?php foreach ($rows as $row) : ?>
                    <?php $expired = !empty($row['expiry']) && $row['expiry'] < $today; ?>
                    <div class="crt-item" data-id="<?= (int) $row['id'] ?>">
                        <div class="t">
                            <h3><?= htmlspecialchars((string) $row['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <button type="button" class="crt-del" data-id="<?= (int) $row['id'] ?>">Διαγραφή</button>
                        </div>
                        <div class="meta">
                            <?php if (!empty($row['provider'])) : ?>
                                <span><?= htmlspecialchars((string) $row['provider'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <?php if (!empty($row['category']) && isset($categories[$row['category']])) : ?>
                                <span class="crt-badge"><?= htmlspecialchars($categories[$row['category']], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($transports[$row['transport_type']] ?? (string) $row['transport_type'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if (!empty($row['date'])) : ?>
                                <span>Απόκτηση: <?= date('d/m/Y', strtotime($row['date'])) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($row['expiry'])) : ?>
                                <span class="crt-badge <?= $expired ? 'expired' : 'valid' ?>">
                                    <?= $expired ? 'Έληξε' : 'Λήξη' ?>: <?= date('d/m/Y', strtotime($row['expiry'])) ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($row['duration'])) : ?>
                                <span><?= (int) $row['duration'] ?> ώρες</span>
                            <?php endif; ?>
                            <?php if (!empty($row['certificate_file'])) : ?>
                                <span class="crt-file"><a href="<?= BASE_URL . ltrim((string) $row['certificate_file'], '/') ?>" target="_blank" rel="noopener">Βεβαίωση ↗</a></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <p class="crt-empty" id="crt-empty" <?= !empty($rows) ? 'style="display:none;"' : '' ?>>
                Δεν έχεις καταχωρήσει ακόμη σεμινάρια ή πιστοποιητικά.
            </p>
        </div>
    </div>

    <?php /* Πίσω ΕΚΕΙ ΑΠ' ΟΠΟΥ ΗΡΘΕ — βλ. ReturnTo. (31/08) */ ?>
    <div style="margin-top:1.2rem;">
        <a href="<?= \Drivejob\Helpers\ReturnTo::url() ?>" class="btn-secondary" style="text-decoration:none;">← <?= htmlspecialchars(\Drivejob\Helpers\ReturnTo::label(), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</main>

<?= \Drivejob\Helpers\Asset::js('js/driver-certifications.js', false) ?>
