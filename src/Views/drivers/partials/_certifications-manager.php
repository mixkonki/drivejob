<?php
/**
 * Διαχείριση σεμιναρίων & πιστοποιητικών — προσθήκη, ΔΙΟΡΘΩΣΗ, διαγραφή
 * χωρίς αλλαγή σελίδας (30/08/2026).
 *
 * ΓΙΑΤΙ ΕΔΩ ΚΑΙ ΟΧΙ ΣΕ ΞΕΧΩΡΙΣΤΗ ΣΕΛΙΔΑ: η καρτέλα «Προσόντα» έδειχνε
 * μόνο έναν πίνακα ανάγνωσης και έστελνε τον οδηγό σε άλλη σελίδα για
 * να προσθέσει — ακριβώς η εναλλαγή σελίδων που ο Κώστας ζήτησε να
 * σταματήσει. Τώρα η διαχείριση γίνεται επιτόπου.
 *
 * ΓΙΑΤΙ AJAX ΚΑΙ ΟΧΙ ΠΕΔΙΑ ΤΗΣ ΜΕΓΑΛΗΣ ΦΟΡΜΑΣ: η καρτέλα ζει μέσα στο
 * <form> του προφίλ και η HTML δεν επιτρέπει ένθετη φόρμα. Επιπλέον τα
 * πιστοποιητικά είναι πολλά αυτόνομα αντικείμενα με αρχείο το καθένα —
 * ισχύει η καθιερωμένη αρχή: άμεση αποθήκευση ανά πράξη.
 *
 * Το ίδιο partial χρησιμοποιείται και στη σελίδα /drivers/certifications:
 * ένα markup, μία συμπεριφορά.
 *
 * Περιμένει στο scope: $driverCertifications (λίστα).
 */

$certCategories = \Drivejob\Helpers\CertificationCategories::options();
$certTransports = \Drivejob\Helpers\CertificationCategories::TRANSPORT;
?>

<div class="crt-manager" id="crtManager"
     data-add-url="<?php echo BASE_URL; ?>drivers/certifications"
     data-update-url="<?php echo BASE_URL; ?>drivers/certifications/update/"
     data-delete-url="<?php echo BASE_URL; ?>drivers/certifications/delete/"
     data-csrf="<?php echo htmlspecialchars(\Drivejob\Core\CSRF::token(), ENT_QUOTES, 'UTF-8'); ?>">

    <div class="crt-msg" id="crtMsg" hidden></div>

    <div class="crt-list" id="crtList">
        <?php foreach (($driverCertifications ?? []) as $cert) :
            $certId = (int) $cert['id'];
            $expiry = $cert['expiry'] ?? null;
            $isExpired = $expiry && strtotime($expiry) < time();
        ?>
            <div class="crt-item" data-id="<?php echo $certId; ?>">
                <div class="crt-view">
                    <div class="crt-view-main">
                        <strong class="crt-title"><?php echo htmlspecialchars($cert['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <?php if (!empty($cert['category'])) : ?>
                            <span class="crt-chip"><?php echo htmlspecialchars(\Drivejob\Helpers\CertificationCategories::label($cert['category']), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                        <span class="crt-chip crt-chip-soft"><?php echo htmlspecialchars(\Drivejob\Helpers\CertificationCategories::transportLabel($cert['transport_type'] ?? 'both'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php if ($expiry) : ?>
                            <span class="crt-chip <?php echo $isExpired ? 'crt-chip-expired' : 'crt-chip-ok'; ?>">
                                <?php echo $isExpired ? 'Έληξε' : 'Λήξη'; ?> <?php echo date('d/m/Y', strtotime($expiry)); ?>
                            </span>
                        <?php else : ?>
                            <span class="crt-chip crt-chip-soft">Χωρίς λήξη</span>
                        <?php endif; ?>
                    </div>
                    <div class="crt-view-meta">
                        <?php if (!empty($cert['provider'])) : ?><span><?php echo htmlspecialchars($cert['provider'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                        <?php if (!empty($cert['date'])) : ?><span>Απόκτηση: <?php echo date('d/m/Y', strtotime($cert['date'])); ?></span><?php endif; ?>
                        <?php if (!empty($cert['duration'])) : ?><span><?php echo (int) $cert['duration']; ?> ώρες</span><?php endif; ?>
                        <?php if (!empty($cert['certificate_file'])) : ?>
                            <a href="<?php echo BASE_URL . htmlspecialchars($cert['certificate_file'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Βεβαίωση ↗</a>
                        <?php endif; ?>
                    </div>
                    <div class="crt-view-actions">
                        <button type="button" class="btn-secondary crt-edit">Διόρθωση</button>
                        <button type="button" class="btn-secondary crt-delete">Διαγραφή</button>
                    </div>
                </div>

                <?php /* Η φόρμα διόρθωσης: κρυφή μέχρι να πατηθεί «Διόρθωση». */ ?>
                <div class="crt-edit-form" hidden>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Τίτλος</label>
                            <input type="text" class="crt-f-title" value="<?php echo htmlspecialchars($cert['title'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="255">
                        </div>
                        <div class="form-group">
                            <label>Φορέας / Σχολή</label>
                            <input type="text" class="crt-f-provider" value="<?php echo htmlspecialchars((string) ($cert['provider'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Θεματολογία</label>
                            <select class="crt-f-category">
                                <option value="">—</option>
                                <?php foreach ($certCategories as $code => $label) : ?>
                                    <option value="<?php echo htmlspecialchars((string) $code, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($cert['category'] ?? '') === $code ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Είδος μεταφορών</label>
                            <select class="crt-f-transport">
                                <?php foreach ($certTransports as $code => $label) : ?>
                                    <option value="<?php echo $code; ?>" <?php echo ($cert['transport_type'] ?? 'both') === $code ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Ημερομηνία Απόκτησης</label>
                            <input type="date" class="crt-f-date" value="<?php echo htmlspecialchars((string) ($cert['date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Ημερομηνία Λήξης</label>
                            <input type="date" class="crt-f-expiry" value="<?php echo htmlspecialchars((string) ($cert['expiry'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            <p class="form-hint">Κενό = χωρίς λήξη</p>
                        </div>
                        <div class="form-group">
                            <label>Διάρκεια (ώρες)</label>
                            <input type="number" class="crt-f-duration" min="0" value="<?php echo htmlspecialchars((string) ($cert['duration'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Αντικατάσταση αρχείου</label>
                            <input type="file" class="crt-f-file" accept=".pdf,.jpg,.jpeg,.png">
                            <p class="form-hint">Κενό = μένει το υπάρχον</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Περιγραφή</label>
                        <input type="text" class="crt-f-description" value="<?php echo htmlspecialchars((string) ($cert['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="crt-edit-actions">
                        <button type="button" class="btn-primary crt-save">Αποθήκευση</button>
                        <button type="button" class="btn-secondary crt-cancel">Ακύρωση</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="crt-empty" id="crtEmpty" <?php echo !empty($driverCertifications) ? 'hidden' : ''; ?>>
        Δεν έχετε καταχωρήσει σεμινάρια ή πιστοποιητικά.
    </p>

    <div class="form-section crt-add">
        <h4>Προσθήκη σεμιναρίου / πιστοποιητικού</h4>
        <div class="form-row">
            <div class="form-group">
                <label for="crtNewTitle">Τίτλος</label>
                <input type="text" id="crtNewTitle" maxlength="255" placeholder="π.χ. Ασφαλής φόρτωση και πρόσδεση φορτίου">
            </div>
            <div class="form-group">
                <label for="crtNewProvider">Φορέας / Σχολή</label>
                <input type="text" id="crtNewProvider" placeholder="π.χ. ΚΕΚ Thessdrive">
            </div>
            <div class="form-group">
                <label for="crtNewCategory">Θεματολογία</label>
                <select id="crtNewCategory">
                    <option value="">—</option>
                    <?php foreach ($certCategories as $code => $label) : ?>
                        <option value="<?php echo htmlspecialchars((string) $code, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="crtNewTransport">Είδος μεταφορών</label>
                <select id="crtNewTransport">
                    <?php foreach ($certTransports as $code => $label) : ?>
                        <option value="<?php echo $code; ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="crtNewDate">Ημερομηνία Απόκτησης</label>
                <input type="date" id="crtNewDate">
            </div>
            <div class="form-group">
                <label for="crtNewExpiry">Ημερομηνία Λήξης</label>
                <input type="date" id="crtNewExpiry">
                <p class="form-hint">Κενό = χωρίς λήξη</p>
            </div>
            <div class="form-group">
                <label for="crtNewDuration">Διάρκεια (ώρες)</label>
                <input type="number" id="crtNewDuration" min="0" placeholder="π.χ. 35">
            </div>
            <div class="form-group">
                <label for="crtNewFile">Βεβαίωση (προαιρετικά)</label>
                <input type="file" id="crtNewFile" accept=".pdf,.jpg,.jpeg,.png">
            </div>
        </div>

        <div class="form-group">
            <label for="crtNewDescription">Περιγραφή</label>
            <input type="text" id="crtNewDescription" placeholder="Σύντομη περιγραφή περιεχομένου">
        </div>

        <button type="button" class="btn-primary" id="crtAdd">+ Προσθήκη</button>
    </div>
</div>

<?= \Drivejob\Helpers\Asset::js('js/certifications-manager.js', true) ?>
