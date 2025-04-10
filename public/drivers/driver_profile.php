<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Συμπερίληψη του config.php για σταθερές BASE_URL και ROOT_DIR
require_once __DIR__ . '/../../config/config.php';
require_once ROOT_DIR . '/config/database.php';



// Αν η συνεδρία δεν έχει ξεκινήσει, την ξεκινάμε
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ανάκτηση δεδομένων του οδηγού από τη βάση δεδομένων
$userId = $_SESSION['user_id'] ?? null;
$driverData = [];
if ($userId) {
    $stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = ?");
    $stmt->execute([$userId]);
    $driverData = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];
}

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php';
?>
<!-- Σύνδεση με το CSS αρχείο του προφίλ εταιρείας -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver_profile.css">
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Προφίλ Οδηγού</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver_profile.css">
    <script src="<?php echo BASE_URL; ?>js/driver_profile.js" defer></script>
    <script
      src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCgZpJWVYyrY0U8U1jBGelEWryur3vIrzc&libraries=places"
      defer
    ></script>
</head>
<body>
    <main>
        <section class="profile-section">
            <h1>Προφίλ Οδηγού</h1>
            <form action="<?php echo BASE_URL; ?>drivers/drivers_register_process.php" method="POST">
                <div class="form-columns">
                    <!-- Στήλη 1: Βασικά Στοιχεία -->
                    <fieldset>
                        <legend>Βασικά Στοιχεία</legend>
                        <div class="form-group">
                            <label for="last_name">Επώνυμο:</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($driverData['last_name'] ?? ''); ?>" required>
                            <div id="last_name_tooltip" class="tooltip"></div>
                        </div>
                        <div class="form-group">
                            <label for="first_name">Όνομα:</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($driverData['first_name'] ?? ''); ?>" required>
                            <div id="first_name_tooltip" class="tooltip"></div>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($driverData['email'] ?? ''); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="phone">Κινητό τηλέφωνο:</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($driverData['phone'] ?? ''); ?>" required>
                            <div id="phone_tooltip" class="tooltip"></div>
                        </div>
                        <div class="form-group">
                            <label for="landline">Σταθερό τηλέφωνο:</label>
                            <input type="tel" id="landline" name="landline" placeholder="Εισάγετε το σταθερό σας τηλέφωνο (προαιρετικό)">
                        </div>
                        <div class="form-group">
                            <label for="birth_date">Ημερομηνία γέννησης:</label>
                            <input type="date" id="birth_date" name="birth_date">
                            <span id="age_display"></span>
                        </div>
                    </fieldset>

                    <!-- Στήλη 2: Διεύθυνση -->
                    <fieldset>
                        <legend>Διεύθυνση</legend>
                        <div class="form-group">
                            <label for="address">Διεύθυνση κατοικίας:</label>
                            <input type="text" id="address" name="address">
                        </div>
                        <div class="form-group">
                            <label for="house_number">Αριθμός:</label>
                            <input type="number" id="house_number" name="house_number">
                        </div>
                        <div class="form-group">
                            <label for="country">Χώρα:</label>
                            <input type="text" id="country" name="country">
                        </div>
                        <div class="form-group">
                            <label for="postal_code">ΤΚ:</label>
                            <input type="text" id="postal_code" name="postal_code">
                        </div>
                        <div class="form-group">
                            <label for="city">Πόλη:</label>
                            <input type="text" id="city" name="city">
                        </div>
                    </fieldset>

                    <!-- Στήλη 3: Επιλογές και χάρτης -->
                    <fieldset>
                        <legend>Επιλογές</legend>
                        <div class="form-group">
                            <label for="driving_license">
                                <input type="checkbox" id="driving_license" name="driving_license">
                                Άδεια οδήγησης
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="adr_certificate">
                                <input type="checkbox" id="adr_certificate" name="adr_certificate">
                                Πιστοποιητικό ADR
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="operator_license">
                                <input type="checkbox" id="operator_license" name="operator_license">
                                Άδεια χειριστή μηχανημάτων έργου
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="training_seminars">
                                <input type="checkbox" id="training_seminars" name="training_seminars">
                                Σεμινάρια κατάρτισης
                            </label>
                        </div>
                        <div class="map-container">
                            <iframe src="https://maps.google.com/maps?q=<?php echo urlencode($driverData['address'] ?? ''); ?>&output=embed" frameborder="0"></iframe>
                        </div>
                    </fieldset>
                </div>

                <!-- Υποβολή -->
                <div class="form-actions">
                    <button type="button" id="editButton" class="btn-edit">Επεξεργασία</button>
                    <button type="submit" class="btn-save">Αποθήκευση</button>
                </div>
            </form>
        </section>
    </main>
    <?php 
    // Συμπερίληψη του footer
    include ROOT_DIR . '/src/Views/footer.php'; // Footer
    ?>
</body>
</html>
