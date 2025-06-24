<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Core\Database;

// Get driver ID from URL
$driverId = $_GET['id'] ?? null;

if (!$driverId || !is_numeric($driverId)) {
    header('HTTP/1.0 404 Not Found');
    echo "<h1>404 - Ο οδηγός δεν βρέθηκε</h1>";
    exit();
}

$pdo = Database::getInstance()->getConnection();

// Get driver details with user info
$stmt = $pdo->prepare("
    SELECT 
        d.*,
        u.email as user_email,
        u.created_at as member_since,
        u.is_active as user_is_active
    FROM drivers d
    JOIN users u ON d.user_id = u.id
    WHERE d.id = ? AND u.is_active = 1
");
$stmt->execute([$driverId]);
$driver = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$driver) {
    header('HTTP/1.0 404 Not Found');
    echo "<h1>404 - Ο οδηγός δεν βρέθηκε</h1>";
    exit();
}

// Get driver's ratings
$stmt = $pdo->prepare("
    SELECT 
        AVG(overall_rating) as avg_rating,
        COUNT(*) as total_reviews,
        AVG(punctuality_rating) as avg_punctuality,
        AVG(communication_rating) as avg_communication,
        AVG(professionalism_rating) as avg_professionalism,
        AVG(vehicle_condition_rating) as avg_vehicle_condition
    FROM company_reviews
    WHERE driver_id = ?
");
$stmt->execute([$driverId]);
$ratings = $stmt->fetch(PDO::FETCH_ASSOC);

// Get driver's experience
$stmt = $pdo->prepare("
    SELECT * FROM driver_vehicle_experience
    WHERE driver_id = ?
    ORDER BY years_experience DESC
");
$stmt->execute([$driverId]);
$experience = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get driver's certifications
$stmt = $pdo->prepare("
    SELECT * FROM driver_certifications
    WHERE driver_id = ? AND expiry_date > NOW()
    ORDER BY issue_date DESC
");
$stmt->execute([$driverId]);
$certifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get driver's skills
$skills = !empty($driver['additional_skills']) ? json_decode($driver['additional_skills'], true) : [];

$pageTitle = $driver['first_name'] . ' ' . $driver['last_name'] . ' - Προφίλ Οδηγού';

include ROOT_DIR . '/src/Views/partials/header.php';
?>

<style>
    .driver-public-profile {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .profile-header {
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }

    .profile-image {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 5px solid #f0f0f0;
    }

    .profile-info h1 {
        margin-bottom: 10px;
    }

    .profile-badges {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }

    .badge-verified {
        background: #28a745;
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 14px;
    }

    .rating-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .rating-stars {
        color: #ffc107;
    }

    .info-section {
        background: white;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .skill-tag {
        display: inline-block;
        background: #e9ecef;
        padding: 5px 15px;
        border-radius: 20px;
        margin: 5px;
        font-size: 14px;
    }

    .experience-item {
        border-left: 3px solid #007bff;
        padding-left: 20px;
        margin-bottom: 20px;
    }

    .certification-item {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
    }

    .contact-section {
        background: #007bff;
        color: white;
        padding: 30px;
        border-radius: 10px;
        text-align: center;
    }
</style>

<div class="driver-public-profile">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="row align-items-center">
            <div class="col-md-3 text-center">
                <?php if ($driver['profile_image']): ?>
                    <img src="<?php echo BASE_URL . $driver['profile_image']; ?>"
                        alt="<?php echo htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']); ?>"
                        class="profile-image">
                <?php else: ?>
                    <div class="profile-image bg-secondary d-flex align-items-center justify-content-center">
                        <i class="fas fa-user fa-4x text-white"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-9 profile-info">
                <h1><?php echo htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']); ?></h1>
                <p class="text-muted mb-2">
                    <i class="fas fa-map-marker-alt"></i>
                    <?php echo htmlspecialchars($driver['city'] . ', ' . $driver['region']); ?>
                </p>
                <p class="text-muted">
                    <i class="fas fa-calendar-alt"></i>
                    Μέλος από <?php echo date('F Y', strtotime($driver['member_since'])); ?>
                </p>
                <div class="profile-badges">
                    <?php if ($driver['is_verified']): ?>
                        <span class="badge-verified">
                            <i class="fas fa-check-circle"></i> Επαληθευμένος
                        </span>
                    <?php endif; ?>
                    <?php if ($driver['available_for_work']): ?>
                        <span class="badge-verified" style="background: #17a2b8;">
                            <i class="fas fa-briefcase"></i> Διαθέσιμος
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Ratings Section -->
    <?php if ($ratings['total_reviews'] > 0): ?>
        <div class="rating-section">
            <h3><i class="fas fa-star"></i> Αξιολογήσεις</h3>
            <div class="row mt-3">
                <div class="col-md-4 text-center">
                    <h2 class="rating-stars">
                        <?php
                        $avgRating = round($ratings['avg_rating'], 1);
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $avgRating) {
                                echo '<i class="fas fa-star"></i>';
                            } elseif ($i - 0.5 <= $avgRating) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                    </h2>
                    <p><?php echo $avgRating; ?>/5 από <?php echo $ratings['total_reviews']; ?> αξιολογήσεις</p>
                </div>
                <div class="col-md-8">
                    <div class="mb-2">
                        <strong>Συνέπεια:</strong> <?php echo round($ratings['avg_punctuality'], 1); ?>/5
                    </div>
                    <div class="mb-2">
                        <strong>Επικοινωνία:</strong> <?php echo round($ratings['avg_communication'], 1); ?>/5
                    </div>
                    <div class="mb-2">
                        <strong>Επαγγελματισμός:</strong> <?php echo round($ratings['avg_professionalism'], 1); ?>/5
                    </div>
                    <div class="mb-2">
                        <strong>Κατάσταση Οχήματος:</strong> <?php echo round($ratings['avg_vehicle_condition'], 1); ?>/5
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- About Section -->
    <?php if (!empty($driver['about_me'])): ?>
        <div class="info-section">
            <h3><i class="fas fa-user"></i> Σχετικά με εμένα</h3>
            <p><?php echo nl2br(htmlspecialchars($driver['about_me'])); ?></p>
        </div>
    <?php endif; ?>

    <!-- Skills Section -->
    <?php if (!empty($skills)): ?>
        <div class="info-section">
            <h3><i class="fas fa-tools"></i> Δεξιότητες</h3>
            <div class="mt-3">
                <?php foreach ($skills as $skill): ?>
                    <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Experience Section -->
    <?php if (!empty($experience)): ?>
        <div class="info-section">
            <h3><i class="fas fa-truck"></i> Εμπειρία Οδήγησης</h3>
            <div class="mt-3">
                <?php foreach ($experience as $exp): ?>
                    <div class="experience-item">
                        <h5><?php echo htmlspecialchars($exp['vehicle_type']); ?></h5>
                        <p class="mb-1">
                            <strong><?php echo $exp['years_experience']; ?> χρόνια εμπειρίας</strong>
                        </p>
                        <?php if (!empty($exp['specific_experience'])): ?>
                            <p class="text-muted"><?php echo htmlspecialchars($exp['specific_experience']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Certifications Section -->
    <?php if (!empty($certifications)): ?>
        <div class="info-section">
            <h3><i class="fas fa-certificate"></i> Πιστοποιήσεις</h3>
            <div class="mt-3">
                <?php foreach ($certifications as $cert): ?>
                    <div class="certification-item">
                        <h5><?php echo htmlspecialchars($cert['certification_name']); ?></h5>
                        <p class="mb-1">
                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($cert['issuing_authority']); ?>
                        </p>
                        <p class="text-muted mb-0">
                            <i class="fas fa-calendar"></i>
                            Έκδοση: <?php echo date('d/m/Y', strtotime($cert['issue_date'])); ?> -
                            Λήξη: <?php echo date('d/m/Y', strtotime($cert['expiry_date'])); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Contact Section -->
    <div class="contact-section">
        <h3>Ενδιαφέρεστε να συνεργαστείτε;</h3>
        <p>Επικοινωνήστε μαζί μου μέσω της πλατφόρμας DriveJob</p>
        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'company'): ?>
            <a href="<?php echo BASE_URL; ?>companies/send-message?driver_id=<?php echo $driverId; ?>"
                class="btn btn-light btn-lg mt-3">
                <i class="fas fa-envelope"></i> Στείλτε Μήνυμα
            </a>
        <?php else: ?>
            <p class="mt-3">
                <a href="<?php echo BASE_URL; ?>login.php" class="text-white">
                    Συνδεθείτε ως επιχείρηση για να επικοινωνήσετε
                </a>
            </p>
        <?php endif; ?>
    </div>
</div>

<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>