<?php /* Καρτέλα «job-matches» του προφίλ οδηγού — αποσπάστηκε από το driver-profile.php (Πακέτο 5.4).
   Μοιράζεται το scope μεταβλητών του γονικού view (include). */ ?>
            <!-- Καρτέλα Ταιριασμάτων Εργασίας -->
            <div class="tab-pane" id="job-matches">
                <div class="job-matches-container">
                    <h2>Προτεινόμενες Θέσεις Εργασίας</h2>

                    <?php
                    // Χρήση του EnhancedMatchingService για να πάρουμε τα matches
                    try {
                        require_once ROOT_DIR . '/src/Services/EnhancedMatchingService.php';
                        $enhancedService = new \Drivejob\Services\Matching\MatchingEngine();
                        $enhancedMatches = $enhancedService->topMatchesForDriver($_SESSION['user_id'], 10);

                        // Convert to expected format
                        $matches = [];
                        foreach ($enhancedMatches as $match) {
                            $matches[] = [
                                'company_listing_id' => $match['id'],
                                'title' => $match['title'],
                                'description' => $match['description'] ?? '',
                                'location' => $match['location'] ?? $match['company_city'],
                                'company_name' => $match['company_name'],
                                'match_score' => $match['overall_score'] ?? 0,
                                'created_at' => $match['created_at'] ?? date('Y-m-d H:i:s')
                            ];
                        }

                        $matchResult = [
                            'results' => $matches,
                            'pagination' => [
                                'total' => count($matches)
                            ]
                        ];
                    } catch (Exception $e) {
                        $matches = [];
                        $matchResult = ['results' => [], 'pagination' => ['total' => 0]];
                        error_log("Enhanced Job matches tab error: " . $e->getMessage());
                    }

                    if (!empty($matches)) :
                    ?>
                        <div class="matched-listings">
                            <?php foreach ($matches as $match) : ?>
                                <div class="job-match-card">
                                    <div class="match-percentage <?php echo $match['match_score'] >= 90 ? 'high' : ($match['match_score'] >= 70 ? 'medium' : 'low'); ?>">
                                        <?php echo round($match['match_score']); ?>% ταίριασμα
                                    </div>
                                    <div class="match-details">
                                        <h3><a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $match['company_listing_id']; ?>"><?php echo htmlspecialchars($match['title']); ?></a></h3>
                                        <div class="match-meta">
                                            <span class="company"><i class="fas fa-building"></i> <?php echo htmlspecialchars($match['company_name']); ?></span>
                                            <span class="location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($match['location']); ?></span>
                                        </div>
                                        <p class="match-description"><?php echo htmlspecialchars(mb_substr($match['description'], 0, 200, 'UTF-8')) . '...'; ?></p>
                                        <div class="match-actions">
                                            <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $match['company_listing_id']; ?>" class="btn-primary">Προβολή Λεπτομερειών</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="text-center mt-4">
                            <a href="<?php echo BASE_URL; ?>drivers/job-matches" class="btn-secondary">
                                <i class="fas fa-eye"></i> Δείτε Όλες τις Προτάσεις (<?php echo $matchResult['pagination']['total']; ?>)
                            </a>
                        </div>
                    <?php else : ?>
                        <div class="no-matches">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <p>Δεν βρέθηκαν ταιριάσματα με το προφίλ σας αυτή τη στιγμή.</p>
                            <p>Συμπληρώστε περισσότερες πληροφορίες στο προφίλ σας για καλύτερα αποτελέσματα.</p>
                            <a href="<?php echo BASE_URL; ?>drivers/edit-profile" class="btn-primary">Ενημέρωση Προφίλ</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

