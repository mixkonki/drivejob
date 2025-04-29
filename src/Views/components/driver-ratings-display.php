<!-- Τμήμα βαθμολογίας και αξιολογήσεων οδηγού -->
<div class="driver-rating-section">
    <?php if (isset($averageRating) && $averageRating > 0) : ?>
        <div class="driver-rating-overview">
            <div class="rating-header">
                <h3>Βαθμολογία Οδηγού</h3>
                <div class="rating-score">
                    <div class="rating-stars">
                        <?php
                        $rating = round($averageRating * 2) / 2; // Στρογγυλοποίηση στο πλησιέστερο 0.5
                        for ($i = 1; $i <= 5; $i++) :
                            if ($i <= $rating) : // Πλήρες αστέρι
                                ?>
                            <span class="star filled">★</span>
                            <?php elseif ($i - 0.5 == $rating) : // Μισό αστέρι ?>
                            <span class="star half">★</span>
                            <?php else : // Κενό αστέρι ?>
                            <span class="star">★</span>
                            <?php endif;
                        endfor; ?>
                    </div>
                    <div class="rating-value"><?php echo number_format($averageRating, 1); ?>/5</div>
                </div>
            </div>
            
            <?php if (isset($driverRatings) && !empty($driverRatings)) : ?>
                <div class="rating-details">
                    <div class="rating-categories">
                        <div class="rating-category">
                            <span class="category-label">Δεξιότητες Οδήγησης</span>
                            <div class="progress-bar">
                                <div class="progress" style="width: <?php echo ($driverRatings['skills_score'] / 30) * 100; ?>%"></div>
                            </div>
                            <span class="category-score"><?php echo number_format($driverRatings['skills_score'], 1); ?>/30</span>
                        </div>
                        
                        <div class="rating-category">
                            <span class="category-label">Ασφάλεια</span>
                            <div class="progress-bar">
                                <div class="progress" style="width: <?php echo ($driverRatings['safety_score'] / 30) * 100; ?>%"></div>
                            </div>
                            <span class="category-score"><?php echo number_format($driverRatings['safety_score'], 1); ?>/30</span>
                        </div>
                        
                        <div class="rating-category">
                            <span class="category-label">Επαγγελματισμός</span>
                            <div class="progress-bar">
                                <div class="progress" style="width: <?php echo ($driverRatings['professionalism_score'] / 20) * 100; ?>%"></div>
                            </div>
                            <span class="category-score"><?php echo number_format($driverRatings['professionalism_score'], 1); ?>/20</span>
                        </div>
                        
                        <div class="rating-category">
                            <span class="category-label">Τεχνικές Γνώσεις</span>
                            <div class="progress-bar">
                                <div class="progress" style="width: <?php echo ($driverRatings['technical_score'] / 20) * 100; ?>%"></div>
                            </div>
                            <span class="category-score"><?php echo number_format($driverRatings['technical_score'], 1); ?>/20</span>
                        </div>
                    </div>
                    
                    <div class="rating-total">
                        <span class="total-label">Συνολική Βαθμολογία</span>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo ($driverRatings['total_score'] / 100) * 100; ?>%"></div>
                        </div>
                        <span class="total-score"><?php echo number_format($driverRatings['total_score'], 1); ?>/100</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($driverReviews) && !empty($driverReviews)) : ?>
        <div class="driver-reviews">
            <h3>Αξιολογήσεις</h3>
            
            <div class="reviews-list">
                <?php foreach ($driverReviews as $review) : ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <h4><?php echo htmlspecialchars($review['company_name'] ?? 'Ανώνυμος'); ?></h4>
                                <span class="review-date">
                                    <?php
                                    echo isset($review['created_at']) ? date('d/m/Y', strtotime($review['created_at'])) : '';
                                    ?>
                                </span>
                            </div>
                            <div class="review-stars">
                                <?php
                                $reviewRating = isset($review['rating']) ? intval($review['rating']) : 0;
                                for ($i = 1; $i <= 5; $i++) :
                                    ?>
                                    <span class="star <?php echo $i <= $reviewRating ? 'filled' : ''; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="review-content">
                            <?php echo isset($review['comment']) ? nl2br(htmlspecialchars($review['comment'])) : ''; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($driverReviews) > 3) : ?>
                <div class="see-all-reviews">
                    <button id="load-more-reviews" class="btn-secondary">Δείτε περισσότερες αξιολογήσεις</button>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif (isset($averageRating) && $averageRating > 0) : ?>
        <p class="no-reviews-message">Δεν υπάρχουν ακόμη αναλυτικές αξιολογήσεις.</p>
    <?php endif; ?>
</div>

<style>
    .driver-rating-section {
        margin-bottom: 30px;
    }
    
    .driver-rating-overview {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 25px;
    }
    
    .rating-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .rating-header h3 {
        margin: 0;
        font-size: 20px;
        color: #343a40;
    }
    
    .rating-score {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .rating-stars {
        font-size: 24px;
        margin-bottom: 5px;
    }
    
    .star {
        color: #e0e0e0;
        margin: 0 2px;
    }
    
    .star.filled {
        color: #ffc107;
    }
    
    .star.half {
        position: relative;
        color: #e0e0e0;
    }
    
    .star.half:before {
        content: '★';
        position: absolute;
        left: 0;
        top: 0;
        width: 50%;
        overflow: hidden;
        color: #ffc107;
    }
    
    .rating-value {
        font-size: 18px;
        font-weight: bold;
        color: #343a40;
    }
    
    .rating-details {
        margin-top: 20px;
    }
    
    .rating-categories {
        margin-bottom: 15px;
    }
    
    .rating-category, .rating-total {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .category-label, .total-label {
        flex: 0 0 150px;
        font-size: 14px;
        color: #495057;
    }
    
    .progress-bar {
        flex: 1;
        height: 10px;
        background-color: #e9ecef;
        border-radius: 5px;
        overflow: hidden;
        margin: 0 15px;
    }
    
    .progress {
        height: 100%;
        background-color: #4285F4;
        border-radius: 5px;
    }
    
    .category-score, .total-score {
        flex: 0 0 50px;
        text-align: right;
        font-size: 14px;
        font-weight: bold;
        color: #343a40;
    }
    
    .rating-total {
        border-top: 1px solid #dee2e6;
        padding-top: 15px;
        margin-top: 15px;
    }
    
    .rating-total .total-label {
        font-weight: bold;
    }
    
    .rating-total .progress {
        background-color: #28a745;
    }
    
    .driver-reviews {
        margin-top: 30px;
    }
    
    .driver-reviews h3 {
        margin-bottom: 20px;
        font-size: 20px;
        color: #343a40;
    }
    
    .reviews-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .review-item {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }
    
    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }
    
    .reviewer-info h4 {
        margin: 0 0 5px 0;
        font-size: 16px;
        color: #343a40;
    }
    
    .review-date {
        font-size: 14px;
        color: #6c757d;
    }
    
    .review-stars {
        font-size: 18px;
    }
    
    .review-content {
        font-size: 15px;
        line-height: 1.5;
        color: #495057;
    }
    
    .see-all-reviews {
        margin-top: 20px;
        text-align: center;
    }
    
    .no-reviews-message {
        font-style: italic;
        color: #6c757d;
        text-align: center;
    }
    
    /* Προσαρμογή για μικρότερες οθόνες */
    @media (max-width: 768px) {
        .rating-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .rating-score {
            margin-top: 15px;
            flex-direction: row;
            align-items: center;
            gap: 15px;
        }
        
        .rating-category, .rating-total {
            flex-wrap: wrap;
        }
        
        .category-label, .total-label {
            flex: 0 0 100%;
            margin-bottom: 5px;
        }
        
        .progress-bar {
            flex: 1;
            margin-left: 0;
        }
        
        .category-score, .total-score {
            flex: 0 0 50px;
        }
        
        .review-header {
            flex-direction: column;
        }
        
        .review-stars {
            margin-top: 10px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Αρχικοποίηση: εμφάνιση μόνο των πρώτων 3 αξιολογήσεων
        const reviewItems = document.querySelectorAll('.review-item');
        const loadMoreButton = document.getElementById('load-more-reviews');
        
        if (reviewItems.length > 3 && loadMoreButton) {
            // Απόκρυψη των υπολοίπων αξιολογήσεων
            for (let i = 3; i < reviewItems.length; i++) {
                reviewItems[i].style.display = 'none';
            }
            
            // Event listener για το κουμπί "Δείτε περισσότερες"
            loadMoreButton.addEventListener('click', function() {
                for (let i = 3; i < reviewItems.length; i++) {
                    reviewItems[i].style.display = 'block';
                }
                loadMoreButton.style.display = 'none';
            });
        }
    });
</script>
