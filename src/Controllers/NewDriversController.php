<?php

namespace Drivejob\Controllers;

use Drivejob\Core\Validator;
use Drivejob\Core\CSRF;
use Drivejob\Core\AuthMiddleware;
use Drivejob\Core\Logger;
use Drivejob\Core\Session;
use Drivejob\Core\Container;
use Drivejob\Core\Exceptions\ValidationException;
use Drivejob\Core\Exceptions\DatabaseException;
use Drivejob\Core\Exceptions\AuthException;
use Drivejob\Repositories\DriversRepository;
use function json_encode;

/**
 * Controller για τους οδηγούς
 * 
 * Νέα έκδοση που χρησιμοποιεί το Repository pattern
 */
class NewDriversController
{
    /**
     * @var DriversRepository Το repository για τους οδηγούς
     */
    private $driversRepository;

    /**
     * @var Container Το container για τις εξαρτήσεις
     */
    private $container;

    /**
     * Constructor
     *
     * @param PDO|null $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct($pdo = null)
    {
        // Λήψη του container
        $this->container = Container::getInstance();

        // Αν δεν έχει παραχθεί PDO, πάρε το από το container
        if ($pdo === null) {
            $pdo = $this->container->get('pdo');
        }

        // Αρχικοποίηση του repository
        $this->driversRepository = new DriversRepository($pdo);
    }

    /**
     * Προβάλλει τη σελίδα προφίλ του οδηγού
     */
    public function profile()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Λήψη των στοιχείων του οδηγού
        $driverId = Session::get('user_id');
        $driver = $this->driversRepository->find($driverId);

        if (!$driver) {
            Session::set('error_message', 'Τα στοιχεία του οδηγού δεν βρέθηκαν.');
            header('Location: ' . BASE_URL);
            exit();
        }

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/drivers/profile.php';
    }

    /**
     * Προβάλλει τη φόρμα επεξεργασίας προφίλ
     */
    public function edit()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Λήψη των στοιχείων του οδηγού
        $driverId = Session::get('user_id');
        $driver = $this->driversRepository->find($driverId);

        if (!$driver) {
            Session::set('error_message', 'Τα στοιχεία του οδηγού δεν βρέθηκαν.');
            header('Location: ' . BASE_URL);
            exit();
        }

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/drivers/edit_profile.php';
    }

    /**
     * Αποθηκεύει τις αλλαγές στο προφίλ
     */
    public function update()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in profile update');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'drivers/edit-profile');
            exit();
        }

        // Επικύρωση βασικών δεδομένων
        $validator = new Validator($_POST);
        $validator->required('first_name', 'Το όνομα είναι υποχρεωτικό.')
            ->required('last_name', 'Το επώνυμο είναι υποχρεωτικό.')
            ->required('phone', 'Το τηλέφωνο είναι υποχρεωτικό.')
            ->pattern('phone', '/^[0-9+\s()-]{10,15}$/', 'Παρακαλώ εισάγετε ένα έγκυρο τηλέφωνο.');

        if (!$validator->isValid()) {
            Logger::error('Validation failed in profile update', [
                'errors' => $validator->getErrors(),
                'post_data' => $_POST
            ]);
            Session::set('errors', $validator->getErrors());
            Session::set('old_input', $_POST);
            header('Location: ' . BASE_URL . 'drivers/edit-profile');
            exit();
        }

        // Λήψη ID του συνδεδεμένου οδηγού
        $driverId = Session::get('user_id');
        Logger::info('Starting profile update for driver', ['driver_id' => $driverId]);

        // Συλλογή των δεδομένων από τη φόρμα
        $data = $this->collectFormData();
        Logger::info('Collected form data for update', ['data_keys' => array_keys($data)]);

        try {
            // Ενημέρωση του προφίλ με το repository
            $updateResult = $this->driversRepository->update($driverId, $data);

            if ($updateResult) {
                Logger::info('Profile update successful');
                Session::set('success_message', 'Το προφίλ σας ενημερώθηκε με επιτυχία.');
            } else {
                Logger::error('Profile update failed', [
                    'driver_id' => $driverId,
                    'data' => $data
                ]);
                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την ενημέρωση του προφίλ σας. Παρακαλώ δοκιμάστε ξανά.');
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in profile update', [
                'driver_id' => $driverId,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
        } catch (\Exception $e) {
            Logger::error('Exception in profile update', [
                'driver_id' => $driverId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
        }

        header('Location: ' . BASE_URL . 'drivers/profile');
        exit();
    }

    /**
     * Εναλλαγή διαθεσιμότητας οδηγού για εργασία
     */
    public function toggleAvailability()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Άκυρο αίτημα.']);
            exit();
        }

        try {
            // Λήψη του τρέχοντος οδηγού
            $driverId = Session::get('user_id');
            $driver = $this->driversRepository->find($driverId);

            if (!$driver) {
                echo json_encode(['success' => false, 'message' => 'Δεν βρέθηκε ο οδηγός.']);
                exit();
            }

            // Αλλαγή της κατάστασης διαθεσιμότητας
            $currentStatus = isset($driver['is_available']) ? (int)$driver['is_available'] : 0;
            $newStatus = $currentStatus ? 0 : 1;

            // Καταγραφή για εντοπισμό σφαλμάτων
            Logger::info("Εναλλαγή διαθεσιμότητας για οδηγό $driverId από $currentStatus σε $newStatus");

            $success = $this->driversRepository->updateAvailability($driverId, $newStatus);

            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Η διαθεσιμότητα ενημερώθηκε με επιτυχία',
                    'newStatus' => $newStatus,
                    'statusText' => $newStatus ? 'Διαθέσιμος/η για εργασία' : 'Μη διαθέσιμος/η για εργασία'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Αποτυχία ενημέρωσης διαθεσιμότητας']);
            }
        } catch (DatabaseException $e) {
            Logger::error("Σφάλμα βάσης δεδομένων κατά την εναλλαγή διαθεσιμότητας: " . $e->getMessage(), $e->getContext());
            echo json_encode(['success' => false, 'message' => 'Σφάλμα βάσης δεδομένων']);
        } catch (\Exception $e) {
            Logger::error("Σφάλμα κατά την εναλλαγή διαθεσιμότητας: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Σφάλμα επεξεργασίας αιτήματος']);
        }

        exit();
    }

    /**
     * Εμφανίζει το δημόσιο προφίλ ενός οδηγού
     * 
     * @param int $id Το ID του οδηγού
     */
    public function publicProfile($id)
    {
        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό οδηγού');
            header('Location: ' . BASE_URL . 'home');
            exit;
        }

        // Ανάκτηση των στοιχείων του οδηγού
        $driver = $this->driversRepository->find($id);

        if (!$driver) {
            Session::set('error_message', 'Ο οδηγός δεν βρέθηκε');
            header('Location: ' . BASE_URL . 'home');
            exit;
        }

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/drivers/public-profile.php';
    }

    /**
     * Προσθήκη της μεθόδου collectFormData για το sanitization
     * 
     * @return array Τα καθαρισμένα δεδομένα της φόρμας
     */
    private function collectFormData()
    {
        return [
            'email' => $this->sanitize($_POST['email'] ?? null), // Προσθήκη του email
            'first_name' => $this->sanitize($_POST['first_name'] ?? null),
            'last_name' => $this->sanitize($_POST['last_name'] ?? null),
            'phone' => $this->sanitize($_POST['phone'] ?? null),
            'address' => $this->sanitize($_POST['address'] ?? null),
            'city' => $this->sanitize($_POST['city'] ?? null),
            'country' => $this->sanitize($_POST['country'] ?? null),
            'postal_code' => $this->sanitize($_POST['postal_code'] ?? null),
            'date_of_birth' => $this->sanitizeDate($_POST['birth_date'] ?? null),
            'is_available' => isset($_POST['available_for_work']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Καθαρίζει μια τιμή εισόδου
     * 
     * @param string|null $input Η τιμή εισόδου
     * @return string|null Η καθαρισμένη τιμή
     */
    private function sanitize($input)
    {
        if ($input === null) {
            return null;
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Καθαρίζει μια ημερομηνία
     * 
     * @param string|null $date Η ημερομηνία
     * @return string|null Η καθαρισμένη ημερομηνία
     */
    private function sanitizeDate($date)
    {
        if ($date === null || empty($date)) {
            return null;
        }
        $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
        if ($dateObj && $dateObj->format('Y-m-d') === $date) {
            return $date;
        }
        return null;
    }

    /**
     * Καθαρίζει HTML
     * 
     * @param string|null $input Η τιμή εισόδου
     * @return string|null Η καθαρισμένη τιμή
     */
    private function sanitizeHtml($input)
    {
        if ($input === null) {
            return null;
        }
        // Επιτρέπουμε βασικά HTML tags
        $allowedTags = '<p><br><strong><em><ul><ol><li><h2><h3><h4>';
        return strip_tags(trim($input), $allowedTags);
    }

    /**
     * Καθαρίζει ένα URL
     * 
     * @param string|null $url Το URL
     * @return string|null Το καθαρισμένο URL
     */
    private function sanitizeUrl($url)
    {
        if (empty($url)) {
            return null;
        }
        $sanitizedUrl = filter_var($url, FILTER_SANITIZE_URL);
        if (filter_var($sanitizedUrl, FILTER_VALIDATE_URL)) {
            return $sanitizedUrl;
        }
        return null;
    }
}
