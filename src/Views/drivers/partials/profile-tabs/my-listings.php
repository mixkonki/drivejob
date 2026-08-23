<?php /* Καρτέλα «my-listings» του προφίλ οδηγού — αποσπάστηκε από το driver-profile.php (Πακέτο 5.4).
   Μοιράζεται το scope μεταβλητών του γονικού view (include). */ ?>
            <!-- Καρτέλα Αγγελιών -->
            <div class="tab-pane" id="my-listings">
                <div class="profile-section">
                    <h2>Δημοσιευμένες Αγγελίες</h2>

                    <?php
                    // Εδώ θα χρησιμοποιηθούν μόνο οι αγγελίες που έχει δημιουργήσει ο χρήστης
                    if (isset($myListings) && count($myListings['results']) > 0) :
                    ?>
                        <div class="driver-listings">
                            <?php foreach ($myListings['results'] as $listing) : ?>
                                <div class="listing-card">
                                    <div class="listing-title">
                                        <h3><a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></a></h3>
                                        <span class="listing-type <?php echo $listing['listing_type']; ?>">
                                            <?php echo $listing['listing_type'] === 'job_offer' ? 'Προσφορά Εργασίας' : 'Αναζήτηση Εργασίας'; ?>
                                        </span>
                                    </div>

                                    <div class="listing-details">
                                        <!-- [Περιεχόμενο αγγελίας παραμένει ως έχει] -->
                                    </div>

                                    <div class="listing-actions">
                                        <a href="<?php echo BASE_URL; ?>job-listings/edit/<?php echo $listing['id']; ?>" class="btn-secondary">Επεξεργασία</a>
                                        <form action="<?php echo BASE_URL; ?>job-listings/delete/<?php echo $listing['id']; ?>" method="post" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo \Drivejob\Core\CSRF::token(); ?>">
                                            <button type="submit" class="btn-danger" onclick="return confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτή την αγγελία;')">Διαγραφή</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p class="no-listings">Δεν έχετε δημοσιεύσει ακόμα αγγελίες.</p>
                    <?php endif; ?>

                    <div class="add-listing">
                        <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn-primary">Δημιουργία Νέας Αγγελίας</a>
                    </div>
                </div>
            </div>
