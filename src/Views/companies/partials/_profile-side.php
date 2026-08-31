<?php

/**
 * Πλαϊνή στήλη προφίλ εταιρείας — Μηνύματα, Επικοινωνία, Ενέργειες, Χάρτης.
 * (εξήχθη από το company-profile.php στην αναδιάρθρωση 01/09/2026 — ζει
 * πλέον ΜΕΣΑ στην καρτέλα «Επισκόπηση», όπως η αντίστοιχη του οδηγού)
 * Περιμένει: $companyData, $companyStats
 */
?>
<!-- Messages Widget -->
<div class="sidebar-section">
    <h3><i class="fas fa-envelope"></i> Μηνύματα</h3>
    <?php $unreadMsg = (int) ($companyStats['unread_messages'] ?? 0); ?>
    <?php if ($unreadMsg > 0) : ?>
        <p class="text-muted mb-3">Έχετε <strong><?php echo $unreadMsg; ?></strong> αδιάβαστ<?php echo $unreadMsg === 1 ? 'ο μήνυμα' : 'α μηνύματα'; ?></p>
    <?php else : ?>
        <p class="text-muted mb-3">Κανένα νέο μήνυμα.</p>
    <?php endif; ?>
    <a href="<?php echo BASE_URL; ?>companies/messages" class="btn btn-secondary">
        Προβολή Μηνυμάτων
    </a>
</div>

<!-- Contact Information -->
<div class="sidebar-section">
    <h3><i class="fas fa-info-circle"></i> Στοιχεία Επικοινωνίας</h3>
    <ul class="contact-list">
        <li>
            <i class="fas fa-envelope"></i>
            <span><?php echo htmlspecialchars($companyData['email']); ?></span>
        </li>
        <li>
            <i class="fas fa-phone"></i>
            <span><?php echo htmlspecialchars($companyData['phone']); ?></span>
        </li>
        <?php if (isset($companyData['website']) && $companyData['website']) : ?>
            <li>
                <i class="fas fa-globe"></i>
                <a href="<?php echo htmlspecialchars($companyData['website']); ?>" target="_blank"><?php echo htmlspecialchars($companyData['website']); ?></a>
            </li>
        <?php endif; ?>
    </ul>
</div>

<!-- Quick Actions -->
<div class="sidebar-section">
    <h3><i class="fas fa-bolt"></i> Γρήγορες Ενέργειες</h3>
    <?php /* Το «Αναζήτηση Οδηγών» εκτός προσωρινά: το /drivers/search
       επιστρέφει πάντα κενή λίστα (καταγεγραμμένο σφάλμα). */ ?>
    <div class="quick-actions">
        <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn-primary">
            <i class="fas fa-plus"></i> Νέα Αγγελία
        </a>
        <a href="<?php echo BASE_URL; ?>companies/edit-profile" class="btn-secondary">
            <i class="fas fa-cog"></i> Ρυθμίσεις
        </a>
    </div>
</div>

<!-- Location Map -->
<?php if (isset($companyData['address']) && $companyData['address'] && isset($companyData['city']) && $companyData['city']) : ?>
    <div class="sidebar-section">
        <h3><i class="fas fa-map-marker-alt"></i> Τοποθεσία</h3>
        <div class="profile-map">
            <iframe
                width="100%"
                height="200"
                frameborder="0"
                scrolling="no"
                marginheight="0"
                marginwidth="0"
                src="https://maps.google.com/maps?q=<?php echo urlencode($companyData['address'] . ', ' . $companyData['city'] . ', ' . $companyData['country']); ?>&output=embed"></iframe>
        </div>
    </div>
<?php endif; ?>
