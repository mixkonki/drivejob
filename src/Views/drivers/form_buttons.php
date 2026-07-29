<!-- Κουμπιά αποθήκευσης και ακύρωσης -->
<div class="form-actions">
    <div class="availability-toggle-container">
        <label class="toggle-switch-label">
            <span class="toggle-label-text">Διαθεσιμότητα για εργασία:</span>
            <span class="toggle-switch">
                <input type="checkbox" name="available_for_work" id="available_for_work" class="toggle-switch-input" value="1" <?php echo $driverData['available_for_work'] ? 'checked' : ''; ?>>
                <span class="toggle-switch-slider"></span>
            </span>
            <span class="toggle-switch-text">
                <?php echo $driverData['available_for_work'] ? 'Διαθέσιμος/η για εργασία' : 'Μη διαθέσιμος/η για εργασία'; ?>
            </span>
        </label>
    </div>

    <div class="form-buttons">
        <button type="submit" class="btn-primary btn-save">Αποθήκευση Αλλαγών</button>
        <a href="<?php echo BASE_URL; ?>drivers/driver_profile" class="btn-secondary">Ακύρωση</a>
    </div>
</div>