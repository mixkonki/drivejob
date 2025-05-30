<?php

/**
 * AI Candidates Widget με Messaging System - Fixed Version
 * Χρησιμοποιεί custom modal και PHPMailer integration
 */

// Λήψη των αγγελιών από το parent scope
$availableListings = $listings['results'] ?? [];
?>

<div class="card shadow-sm mb-4" id="ai-candidates-widget">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-robot"></i> Προτεινόμενοι Υποψήφιοι με AI
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($availableListings)): ?>
            <div class="mb-3">
                <label for="job-selector" class="form-label">Επιλέξτε Αγγελία:</label>
                <select class="form-select" id="job-selector">
                    <option value="">-- Επιλέξτε μια αγγελία --</option>
                    <?php foreach ($availableListings as $listing): ?>
                        <?php if ($listing['is_active']): ?>
                            <option value="<?php echo $listing['id']; ?>">
                                <?php echo htmlspecialchars($listing['title']); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="candidates-container">
                <div class="text-center text-muted p-4">
                    <i class="fas fa-arrow-up fa-2x mb-2"></i>
                    <p>Επιλέξτε μια αγγελία για να δείτε τους προτεινόμενους υποψήφιους</p>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Δεν έχετε ενεργές αγγελίες.
                <a href="<?php echo BASE_URL; ?>job-listings/create" class="alert-link">Δημιουργήστε μια νέα αγγελία</a>
                για να δείτε προτεινόμενους υποψήφιους.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Custom Message Modal -->
<div class="custom-modal-overlay" id="messageModalOverlay" style="display: none;">
    <div class="custom-modal" id="messageModal">
        <div class="custom-modal-header">
            <h3>Αποστολή Μηνύματος</h3>
            <button type="button" class="custom-modal-close" onclick="closeMessageModal()">&times;</button>
        </div>
        <div class="custom-modal-body">
            <form id="messageForm">
                <input type="hidden" id="driverId" value="">
                <input type="hidden" id="jobId" value="">

                <div class="form-group">
                    <label>Προς:</label>
                    <input type="text" class="form-control" id="driverName" readonly>
                </div>

                <div class="form-group">
                    <label>Θέμα:</label>
                    <input type="text" class="form-control" id="messageSubject" required>
                </div>

                <div class="form-group">
                    <label>Επιλέξτε πρότυπο μήνυμα:</label>
                    <select class="form-control" id="templateSelect">
                        <option value="">-- Προσαρμοσμένο μήνυμα --</option>
                        <option value="interview">Πρόσκληση σε Συνέντευξη</option>
                        <option value="documents">Αίτημα Εγγράφων</option>
                        <option value="interest">Επιβεβαίωση Ενδιαφέροντος</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Μήνυμα:</label>
                    <textarea class="form-control" id="messageContent" rows="5" required></textarea>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="sendEmailCopy" checked>
                        Αποστολή αντιγράφου μέσω email
                    </label>
                </div>
            </form>
        </div>
        <div class="custom-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeMessageModal()">Ακύρωση</button>
            <button type="button" class="btn btn-primary" id="sendMessageBtn" onclick="sendMessage()">
                <i class="fas fa-paper-plane"></i> Αποστολή
            </button>
        </div>
    </div>
</div>

<style>
    /* Widget Styles */
    #ai-candidates-widget .candidate-item {
        border-bottom: 1px solid #e0e0e0;
        padding: 15px;
        transition: all 0.3s ease;
    }

    #ai-candidates-widget .candidate-item:last-child {
        border-bottom: none;
    }

    #ai-candidates-widget .candidate-item:hover {
        background-color: #f8f9fa;
    }

    #ai-candidates-widget .candidate-name {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    #ai-candidates-widget .candidate-details {
        font-size: 0.9rem;
        color: #7f8c8d;
    }

    #ai-candidates-widget .match-score {
        font-weight: bold;
        font-size: 1.1rem;
    }

    #ai-candidates-widget .match-score.high {
        color: #27ae60;
    }

    #ai-candidates-widget .match-score.medium {
        color: #f39c12;
    }

    #ai-candidates-widget .match-score.low {
        color: #e74c3c;
    }

    #ai-candidates-widget .candidate-actions {
        margin-top: 10px;
    }

    #ai-candidates-widget .loading-spinner {
        text-align: center;
        padding: 40px 20px;
    }

    /* Custom Modal Styles */
    .custom-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .custom-modal {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .custom-modal-header {
        padding: 20px;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .custom-modal-header h3 {
        margin: 0;
        color: #333;
    }

    .custom-modal-close {
        background: none;
        border: none;
        font-size: 28px;
        cursor: pointer;
        color: #999;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .custom-modal-close:hover {
        color: #333;
    }

    .custom-modal-body {
        padding: 20px;
        overflow-y: auto;
        flex: 1;
    }

    .custom-modal-footer {
        padding: 20px;
        border-top: 1px solid #e0e0e0;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #333;
    }

    .form-control {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .form-control:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
    }

    textarea.form-control {
        resize: vertical;
    }

    /* Notification Styles */
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 4px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
    }

    .notification.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .notification.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const jobSelector = document.getElementById('job-selector');
        const candidatesContainer = document.getElementById('candidates-container');
        let currentCandidates = [];

        if (jobSelector) {
            jobSelector.addEventListener('change', function() {
                const jobId = this.value;
                if (jobId) {
                    loadCandidates(jobId);
                } else {
                    candidatesContainer.innerHTML = `
                        <div class="text-center text-muted p-4">
                            <i class="fas fa-arrow-up fa-2x mb-2"></i>
                            <p>Επιλέξτε μια αγγελία για να δείτε τους προτεινόμενους υποψήφιους</p>
                        </div>
                    `;
                }
            });
        }

        function loadCandidates(jobId) {
            candidatesContainer.innerHTML = `
                <div class="loading-spinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Φόρτωση...</span>
                    </div>
                    <p class="mt-2 text-muted">Αναζήτηση υποψηφίων με AI...</p>
                </div>
            `;

            fetch(`<?php echo BASE_URL; ?>api/matching/job/candidates/get.php?job_id=${jobId}&limit=5`, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.candidates && data.data.candidates.length > 0) {
                        currentCandidates = data.data.candidates;
                        displayCandidates(data.data.candidates);
                    } else if (data.error) {
                        displayError(data.error);
                    } else {
                        displayNoCandidates();
                    }
                })
                .catch(error => {
                    console.error('Error loading candidates:', error);
                    displayError(error.message);
                });
        }

        function displayCandidates(candidates) {
            let html = '<div class="candidates-list">';

            candidates.forEach(candidate => {
                const score = candidate.match_score || 0;
                const scoreClass = score >= 70 ? 'high' : score >= 50 ? 'medium' : 'low';

                html += `
                    <div class="candidate-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="candidate-name">
                                    <a href="<?php echo BASE_URL; ?>drivers/profile/${candidate.driver_id}">
                                        ${candidate.name || 'Ανώνυμος Οδηγός'}
                                    </a>
                                </div>
                                <div class="candidate-details">
                                    <div><i class="fas fa-map-marker-alt"></i> ${candidate.city || 'Δεν έχει οριστεί'}</div>
                                    <div><i class="fas fa-briefcase"></i> ${candidate.experience_years || 0} έτη εμπειρίας</div>
                                    <div><i class="fas fa-star"></i> Βαθμολογία: ${candidate.rating || 'N/A'}/5</div>
                                </div>
                                <div class="candidate-actions">
                                    <a href="<?php echo BASE_URL; ?>drivers/profile/${candidate.driver_id}" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> Προφίλ
                                    </a>
                                    <button class="btn btn-sm btn-success" 
                                            onclick="openMessageModal(${candidate.driver_id}, '${candidate.name}')">
                                        <i class="fas fa-envelope"></i> Επικοινωνία
                                    </button>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="match-score ${scoreClass}">
                                    ${score}%
                                </div>
                                <div class="text-muted small">
                                    Ταίριασμα
                                </div>
                                <div class="text-muted small mt-1">
                                    ${candidate.recommendation || ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            candidatesContainer.innerHTML = html;
        }

        function displayNoCandidates() {
            candidatesContainer.innerHTML = `
                <div class="text-center text-muted p-4">
                    <i class="fas fa-users-slash fa-3x mb-3"></i>
                    <p>Δεν βρέθηκαν υποψήφιοι για αυτή την αγγελία.</p>
                    <p class="small">Δοκιμάστε να τροποποιήσετε τις απαιτήσεις της αγγελίας.</p>
                </div>
            `;
        }

        function displayError(errorMessage = '') {
            candidatesContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Σφάλμα κατά τη φόρτωση των υποψηφίων. 
                    ${errorMessage ? `<br><small>${errorMessage}</small>` : ''}
                    <br>
                    <a href="#" onclick="location.reload(); return false;">
                        Δοκιμάστε ξανά
                    </a>
                </div>
            `;
        }

        // Message Modal Functions
        window.openMessageModal = function(driverId, driverName) {
            const jobSelector = document.getElementById('job-selector');
            const jobId = jobSelector.value;
            const jobTitle = jobSelector.options[jobSelector.selectedIndex].text;

            // Set modal data
            document.getElementById('driverId').value = driverId;
            document.getElementById('jobId').value = jobId;
            document.getElementById('driverName').value = driverName;
            document.getElementById('messageSubject').value = `Σχετικά με τη θέση: ${jobTitle}`;
            document.getElementById('messageContent').value = '';
            document.getElementById('templateSelect').value = '';

            // Show modal
            document.getElementById('messageModalOverlay').style.display = 'flex';
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }

        window.closeMessageModal = function() {
            document.getElementById('messageModalOverlay').style.display = 'none';
            document.body.style.overflow = ''; // Restore scrolling
        }

        // Close modal when clicking outside
        document.getElementById('messageModalOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closeMessageModal();
            }
        });

        // Template selection
        document.getElementById('templateSelect').addEventListener('change', function() {
            const messageContent = document.getElementById('messageContent');
            const jobTitle = document.getElementById('job-selector').options[document.getElementById('job-selector').selectedIndex].text;

            switch (this.value) {
                case 'interview':
                    messageContent.value = `Γεια σας,\n\nΘα θέλαμε να σας προσκαλέσουμε σε συνέντευξη για τη θέση "${jobTitle}".\n\nΕίστε διαθέσιμος τις επόμενες ημέρες; Παρακαλώ ενημερώστε μας για τη διαθεσιμότητά σας.\n\nΜε εκτίμηση`;
                    break;
                case 'documents':
                    messageContent.value = `Γεια σας,\n\nΣας ευχαριστούμε για το ενδιαφέρον σας για τη θέση "${jobTitle}".\n\nΠαρακαλούμε στείλτε μας τα παρακάτω έγγραφα:\n- Αντίγραφο άδειας οδήγησης\n- ΠΕΙ (Πιστοποιητικό Επαγγελματικής Ικανότητας)\n- Βιογραφικό σημείωμα\n\nΜε εκτίμηση`;
                    break;
                case 'interest':
                    messageContent.value = `Γεια σας,\n\nΣας ευχαριστούμε για το ενδιαφέρον σας για τη θέση "${jobTitle}".\n\nΤο προφίλ σας φαίνεται πολύ ενδιαφέρον και θα θέλαμε να συζητήσουμε περαιτέρω μαζί σας.\n\nΘα επικοινωνήσουμε σύντομα για να κανονίσουμε μια συνάντηση.\n\nΜε εκτίμηση`;
                    break;
                default:
                    messageContent.value = '';
            }
        });

        // Send message
        window.sendMessage = function() {
            const driverId = document.getElementById('driverId').value;
            const jobId = document.getElementById('jobId').value;
            const subject = document.getElementById('messageSubject').value;
            const message = document.getElementById('messageContent').value;
            const sendEmail = document.getElementById('sendEmailCopy').checked;

            if (!subject || !message) {
                showNotification('error', 'Παρακαλώ συμπληρώστε όλα τα πεδία');
                return;
            }

            // Disable button and show loading
            const sendBtn = document.getElementById('sendMessageBtn');
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Αποστολή...';

            // Send message via API
            fetch('<?php echo BASE_URL; ?>api/messaging/send.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        driver_id: parseInt(driverId),
                        job_id: parseInt(jobId),
                        subject: subject,
                        message: message,
                        send_email: sendEmail
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal
                        closeMessageModal();

                        // Show success message
                        showNotification('success', 'Το μήνυμα στάλθηκε επιτυχώς!');

                        // Reset form
                        document.getElementById('messageForm').reset();
                    } else {
                        showNotification('error', 'Σφάλμα: ' + (data.error || 'Αποτυχία αποστολής'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', 'Σφάλμα κατά την αποστολή του μηνύματος');
                })
                .finally(() => {
                    // Re-enable button
                    sendBtn.disabled = false;
                    sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Αποστολή';
                });
        }

        // Notification function
        function showNotification(type, message) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                ${message}
            `;

            document.body.appendChild(notification);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out forwards';
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }
    });
</script>