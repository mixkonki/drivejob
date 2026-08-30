<?php
/**
 * Admin → Κατάλογοι Τιμών (30/08/2026).
 *
 * Εδώ ο διαχειριστής συντηρεί τις λίστες που βλέπουν οι οδηγοί, χωρίς
 * deploy. Δύο πράγματα είναι σκόπιμα «δύσκολα»:
 *  - ο κωδικός δεν αλλάζει μετά τη δημιουργία (τον δείχνουν οι εγγραφές)
 *  - η διαγραφή επιτρέπεται μόνο σε τιμή που δεν χρησιμοποιεί κανείς·
 *    αλλιώς προτείνεται απόσυρση, ώστε να μη μείνουν ορφανά προφίλ.
 *
 * Περιμένει: $domain, $domains, $values (με usage_count).
 */
include ROOT_DIR . '/src/Views/partials/admin-header.php';
?>

<style>
    .lookup-wrap { max-width: 1100px; margin: 0 auto; padding: 1rem; }
    .lookup-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:1.1rem 1.3rem; margin-bottom:1.1rem; }
    .lookup-card h2 { margin-top:0; font-size:1.1rem; padding-bottom:.55rem; border-bottom:1px solid #f3f4f6; }
    .lookup-table { width:100%; border-collapse:collapse; font-size:.9rem; }
    .lookup-table th { text-align:left; padding:.5rem .6rem; border-bottom:2px solid #e5e7eb; font-size:.8rem; color:#6b7280; }
    .lookup-table td { padding:.55rem .6rem; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
    .lookup-table tr.inactive td { opacity:.55; }
    .lookup-code { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-size:.82rem; color:#6b7280; }
    .lookup-badge { display:inline-block; font-size:.72rem; padding:.15rem .5rem; border-radius:999px; }
    .lookup-badge.active { background:#dcfce7; color:#166534; }
    .lookup-badge.off { background:#fee2e2; color:#991b1b; }
    .lookup-badge.system { background:#e0e7ff; color:#3730a3; }
    .lookup-row-form { display:grid; grid-template-columns: repeat(auto-fit, minmax(190px,1fr)); gap:.7rem; align-items:end; }
    .lookup-row-form label { display:block; font-size:.82rem; font-weight:600; color:#374151; margin-bottom:.25rem; }
    .lookup-row-form input { width:100%; box-sizing:border-box; padding:.5rem .65rem; border:1px solid #d1d5db; border-radius:7px; font-size:.9rem; }
    .lookup-actions { display:flex; gap:.4rem; flex-wrap:wrap; }
    .lookup-actions button { font-size:.8rem; padding:.35rem .7rem; border-radius:6px; border:1px solid #d1d5db; background:#fff; cursor:pointer; }
    .lookup-actions button.danger { color:#b3261e; border-color:#fecaca; }
    .lookup-actions button:disabled { opacity:.45; cursor:not-allowed; }
    .btn-add { background:#b3261e; color:#fff; border:0; border-radius:7px; padding:.55rem 1.2rem; font-weight:600; cursor:pointer; }
    .lookup-note { color:#6b7280; font-size:.85rem; }
</style>

<div class="lookup-wrap">
    <h1>Κατάλογοι Τιμών</h1>
    <p class="lookup-note">Οι λίστες που εμφανίζονται στις φόρμες των οδηγών. Οι αλλαγές ισχύουν αμέσως — χωρίς νέα έκδοση της εφαρμογής.</p>

    <?php if (isset($_SESSION['success_message'])) : ?>
        <div class="success-message"><?php echo htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])) : ?>
        <div class="error-message"><?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>

    <?php if (count($domains) > 1) : ?>
        <div class="lookup-card">
            <label for="domainSelect"><strong>Κατάλογος:</strong></label>
            <select id="domainSelect" onchange="window.location = '<?php echo BASE_URL; ?>admin/lookups/' + this.value;">
                <?php foreach ($domains as $dCode => $dLabel) : ?>
                    <option value="<?php echo $dCode; ?>" <?php echo $domain === $dCode ? 'selected' : ''; ?>><?php echo htmlspecialchars($dLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <div class="lookup-card">
        <h2><?php echo htmlspecialchars($domains[$domain], ENT_QUOTES, 'UTF-8'); ?></h2>

        <table class="lookup-table">
            <thead>
                <tr>
                    <th style="width:60px;">Σειρά</th>
                    <th>Ονομασία (όπως τη βλέπει ο οδηγός)</th>
                    <th style="width:170px;">Σύντομη</th>
                    <th style="width:130px;">Κωδικός</th>
                    <th style="width:90px;">Χρήση</th>
                    <th style="width:230px;">Ενέργειες</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($values as $value) :
                    $isActive = (int) $value['is_active'] === 1;
                    $isSystem = (int) $value['is_system'] === 1;
                    $usage = (int) ($value['usage_count'] ?? 0);
                ?>
                    <tr class="<?php echo $isActive ? '' : 'inactive'; ?>">
                        <form method="POST" action="<?php echo BASE_URL; ?>admin/lookups/<?php echo $domain; ?>/save">
                            <?php echo \Drivejob\Core\CSRF::tokenField(); ?>
                            <input type="hidden" name="id" value="<?php echo (int) $value['id']; ?>">
                            <td><input type="number" name="sort_order" value="<?php echo (int) $value['sort_order']; ?>" style="width:60px;"></td>
                            <td><input type="text" name="label" value="<?php echo htmlspecialchars($value['label'], ENT_QUOTES, 'UTF-8'); ?>" required></td>
                            <td><input type="text" name="short_label" value="<?php echo htmlspecialchars((string) $value['short_label'], ENT_QUOTES, 'UTF-8'); ?>"></td>
                            <td>
                                <span class="lookup-code"><?php echo htmlspecialchars($value['code'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if ($isSystem) : ?><br><span class="lookup-badge system">βασική</span><?php endif; ?>
                            </td>
                            <td>
                                <?php echo $usage; ?>
                                <br><span class="lookup-badge <?php echo $isActive ? 'active' : 'off'; ?>"><?php echo $isActive ? 'ενεργή' : 'ανενεργή'; ?></span>
                            </td>
                            <td>
                                <div class="lookup-actions">
                                    <button type="submit">Αποθήκευση</button>
                        </form>
                                    <form method="POST" action="<?php echo BASE_URL; ?>admin/lookups/<?php echo $domain; ?>/toggle/<?php echo (int) $value['id']; ?>" style="display:inline;">
                                        <?php echo \Drivejob\Core\CSRF::tokenField(); ?>
                                        <button type="submit" <?php echo $isSystem && $isActive ? 'disabled title="Βασική τιμή του συστήματος"' : ''; ?>>
                                            <?php echo $isActive ? 'Απόσυρση' : 'Επαναφορά'; ?>
                                        </button>
                                    </form>
                                    <form method="POST" action="<?php echo BASE_URL; ?>admin/lookups/<?php echo $domain; ?>/delete/<?php echo (int) $value['id']; ?>" style="display:inline;">
                                        <?php echo \Drivejob\Core\CSRF::tokenField(); ?>
                                        <button type="submit" class="danger"
                                            <?php echo ($usage > 0 || $isSystem) ? 'disabled title="Χρησιμοποιείται ή είναι βασική — κάντε Απόσυρση"' : ''; ?>>
                                            Διαγραφή
                                        </button>
                                    </form>
                                </div>
                            </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($values)) : ?>
                    <tr><td colspan="6" class="lookup-note">Δεν υπάρχουν τιμές σε αυτόν τον κατάλογο.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <p class="lookup-note" style="margin-top:.9rem;">
            <strong>Απόσυρση</strong>: η τιμή δεν προσφέρεται πια σε νέες καταχωρήσεις, αλλά όσοι την έχουν ήδη τη διατηρούν.
            <strong>Διαγραφή</strong>: μόνο για τιμές που δεν χρησιμοποιεί κανείς.
        </p>
    </div>

    <div class="lookup-card">
        <h2>Προσθήκη νέας τιμής</h2>
        <form method="POST" action="<?php echo BASE_URL; ?>admin/lookups/<?php echo $domain; ?>/save">
            <?php echo \Drivejob\Core\CSRF::tokenField(); ?>
            <div class="lookup-row-form">
                <div>
                    <label for="new_label">Ονομασία</label>
                    <input type="text" id="new_label" name="label" required placeholder="π.χ. Πιστοποιητικό μεταφοράς επικίνδυνων αποβλήτων">
                </div>
                <div>
                    <label for="new_short">Σύντομη ονομασία</label>
                    <input type="text" id="new_short" name="short_label" placeholder="π.χ. Επικίνδυνα απόβλητα">
                </div>
                <div>
                    <label for="new_code">Κωδικός</label>
                    <input type="text" id="new_code" name="code" required placeholder="π.χ. hazardous_waste" pattern="[A-Za-z0-9_ -]+">
                </div>
                <div>
                    <label for="new_sort">Σειρά</label>
                    <input type="number" id="new_sort" name="sort_order" value="<?php echo (count($values) + 1) * 10; ?>">
                </div>
                <div>
                    <button type="submit" class="btn-add">Προσθήκη</button>
                </div>
            </div>
            <p class="lookup-note" style="margin-top:.7rem;">
                Ο κωδικός γράφεται με λατινικά και δεν αλλάζει ποτέ μετά τη δημιουργία — τον αποθηκεύουν τα προφίλ των οδηγών.
                Η ονομασία διορθώνεται ελεύθερα όποτε θέλετε.
            </p>
        </form>
    </div>
</div>

<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>
