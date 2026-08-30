<?php
/**
 * Επαναχρησιμοποιήσιμες κάρτες εικόνων εγγράφων (25/08).
 *
 * Ίδιο μοτίβο με το avatar: κλικ στην κάρτα = επιλογή αρχείου, ζωντανή
 * προεπισκόπηση (generic JS στο driver_edit_profile.js — .doc-upload),
 * «Αλλαγή» στο hover. CSS στο driver-edit-align.css.
 *
 * Περιμένει στο scope:
 *   $docImages = [['id' => ..., 'label' => ..., 'scan_id' => προαιρετικό], ...]
 *   $driverData (για τις τρέχουσες εικόνες)
 *
 * Χρήση: driving-licenses, tachograph-card, adr-certificates, operator-licenses.
 */
?>
                                <div class="doc-upload-grid">
                                    <?php foreach ($docImages as $image) :
                                        $hasImg = !empty($driverData[$image['id']]);
                                    ?>
                                        <div class="doc-upload">
                                            <span class="doc-upload-title"><?php echo $image['label']; ?></span>
                                            <label class="doc-drop <?php echo $hasImg ? 'has-image' : ''; ?>" for="<?php echo $image['id']; ?>" title="Κλικ για επιλογή εικόνας">
                                                <img class="doc-preview"
                                                     src="<?php echo $hasImg ? BASE_URL . htmlspecialchars($driverData[$image['id']]) : ''; ?>"
                                                     alt="<?php echo $image['label']; ?>"
                                                     <?php echo $hasImg ? '' : 'style="display:none;"'; ?>
                                                     onerror="this.style.display='none';var p=this.parentElement.querySelector('.doc-placeholder');if(p)p.style.display='';">
                                                <span class="doc-placeholder" <?php echo $hasImg ? 'style="display:none;"' : ''; ?>>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                                                    Κλικ για προσθήκη εικόνας
                                                </span>
                                                <span class="doc-change-overlay">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                                    Αλλαγή
                                                </span>
                                            </label>
                                            <input type="file" id="<?php echo $image['id']; ?>" name="<?php echo $image['id']; ?>" accept="image/jpeg, image/png, image/gif" class="doc-file-input">
                                            <?php if (!empty($image['scan_id'])) : ?>
                                                <button type="button" id="<?php echo $image['scan_id']; ?>" class="btn-scan">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><line x1="7" y1="12" x2="17" y2="12"/></svg>
                                                    Σκανάρισμα με OCR
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
