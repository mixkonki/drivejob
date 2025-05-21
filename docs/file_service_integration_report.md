# Αναφορά Εφαρμογής Service Layer Pattern

## Εισαγωγή

Το Service Layer Pattern είναι ένα αρχιτεκτονικό πρότυπο που ορίζει ένα επίπεδο υπηρεσιών το οποίο παρέχει μια διεπαφή για τις λειτουργίες της εφαρμογής. Αυτό το επίπεδο ενθυλακώνει την επιχειρηματική λογική της εφαρμογής, διαχωρίζοντάς την από τους controllers και τα μοντέλα. Η εφαρμογή του Service Layer Pattern στο DriveJob θα βελτιώσει την οργάνωση του κώδικα, θα διευκολύνει τη συντήρηση και θα επιτρέψει την ευκολότερη επέκταση της εφαρμογής.

## Υπάρχουσα Κατάσταση

Αυτή τη στιγμή, η εφαρμογή DriveJob χρησιμοποιεί ένα μοντέλο MVC (Model-View-Controller) όπου:

- **Models**: Διαχειρίζονται την πρόσβαση στα δεδομένα και τις βασικές λειτουργίες CRUD
- **Views**: Παρουσιάζουν τα δεδομένα στους χρήστες
- **Controllers**: Διαχειρίζονται τις αιτήσεις των χρηστών, επεξεργάζονται τα δεδομένα και καλούν τα κατάλληλα views

Ωστόσο, η επιχειρηματική λογική είναι διασκορπισμένη μεταξύ των controllers και των models, γεγονός που δυσκολεύει τη συντήρηση και την επέκταση της εφαρμογής. Επιπλέον, υπάρχει επανάληψη κώδικα σε διάφορους controllers που εκτελούν παρόμοιες λειτουργίες.

## Προτεινόμενες Αλλαγές

### 1. Δημιουργία Service Layer

Προτείνεται η δημιουργία ενός νέου επιπέδου υπηρεσιών (Service Layer) που θα περιέχει την επιχειρηματική λογική της εφαρμογής. Αυτό το επίπεδο θα αποτελείται από διάφορες κλάσεις υπηρεσιών, κάθε μία από τις οποίες θα είναι υπεύθυνη για ένα συγκεκριμένο τομέα της εφαρμογής.

#### 1.1 Δομή Φακέλων

```
src/
  ├── Services/
  │   ├── User/
  │   │   ├── UserService.php
  │   │   ├── DriverService.php
  │   │   └── CompanyService.php
  │   ├── JobListing/
  │   │   ├── JobListingService.php
  │   │   ├── JobApplicationService.php
  │   │   └── JobOfferService.php
  │   ├── Rating/
  │   │   ├── DriverRatingService.php
  │   │   └── CompanyRatingService.php
  │   ├── Matching/
  │   │   └── MatchingService.php
  │   ├── Notification/
  │   │   └── NotificationService.php
  │   ├── File/
  │   │   └── FileService.php
  │   └── Auth/
  │       └── AuthService.php
```

#### 1.2 Παράδειγμα Υπηρεσίας

```php
namespace Drivejob\Services\User;

use Drivejob\Repositories\DriversRepository;
use Drivejob\Repositories\DriverLicenseRepository;
use Drivejob\Repositories\DriverSkillsRepository;
use Drivejob\Core\Exceptions\ValidationException;
use Drivejob\Core\Exceptions\DatabaseException;

class DriverService
{
    private $driversRepository;
    private $driverLicenseRepository;
    private $driverSkillsRepository;
    
    public function __construct(
        DriversRepository $driversRepository,
        DriverLicenseRepository $driverLicenseRepository,
        DriverSkillsRepository $driverSkillsRepository
    ) {
        $this->driversRepository = $driversRepository;
        $this->driverLicenseRepository = $driverLicenseRepository;
        $this->driverSkillsRepository = $driverSkillsRepository;
    }
    
    /**
     * Δημιουργεί έναν νέο οδηγό
     *
     * @param array $data Τα δεδομένα του οδηγού
     * @return int Το ID του νέου οδηγού
     * @throws ValidationException Αν τα δεδομένα δεν είναι έγκυρα
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function createDriver(array $data)
    {
        // Επικύρωση δεδομένων
        $this->validateDriverData($data);
        
        // Δημιουργία του οδηγού
        $driverId = $this->driversRepository->create($data);
        
        // Δημιουργία των αδειών οδήγησης
        if (isset($data['licenses']) && is_array($data['licenses'])) {
            foreach ($data['licenses'] as $license) {
                $license['driver_id'] = $driverId;
                $this->driverLicenseRepository->create($license);
            }
        }
        
        // Δημιουργία των δεξιοτήτων
        if (isset($data['skills']) && is_array($data['skills'])) {
            foreach ($data['skills'] as $skill) {
                $skill['driver_id'] = $driverId;
                $this->driverSkillsRepository->create($skill);
            }
        }
        
        return $driverId;
    }
    
    /**
     * Ενημερώνει τα στοιχεία ενός οδηγού
     *
     * @param int $driverId Το ID του οδηγού
     * @param array $data Τα νέα δεδομένα του οδηγού
     * @return bool Αν η ενημέρωση ήταν επιτυχής
     * @throws ValidationException Αν τα δεδομένα δεν είναι έγκυρα
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function updateDriver(int $driverId, array $data)
    {
        // Επικύρωση δεδομένων
        $this->validateDriverData($data);
        
        // Ενημέρωση του οδηγού
        $result = $this->driversRepository->update($driverId, $data);
        
        // Ενημέρωση των αδειών οδήγησης
        if (isset($data['licenses']) && is_array($data['licenses'])) {
            // Διαγραφή των υπαρχουσών αδειών
            $this->driverLicenseRepository->deleteByDriver($driverId);
            
            // Δημιουργία των νέων αδειών
            foreach ($data['licenses'] as $license) {
                $license['driver_id'] = $driverId;
                $this->driverLicenseRepository->create($license);
            }
        }
        
        // Ενημέρωση των δεξιοτήτων
        if (isset($data['skills']) && is_array($data['skills'])) {
            // Διαγραφή των υπαρχουσών δεξιοτήτων
            $this->driverSkillsRepository->deleteByDriver($driverId);
            
            // Δημιουργία των νέων δεξιοτήτων
            foreach ($data['skills'] as $skill) {
                $skill['driver_id'] = $driverId;
                $this->driverSkillsRepository->create($skill);
            }
        }
        
        return $result;
    }
    
    /**
     * Διαγράφει έναν οδηγό
     *
     * @param int $driverId Το ID του οδηγού
     * @return bool Αν η διαγραφή ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function deleteDriver(int $driverId)
    {
        // Διαγραφή των αδειών οδήγησης
        $this->driverLicenseRepository->deleteByDriver($driverId);
        
        // Διαγραφή των δεξιοτήτων
        $this->driverSkillsRepository->deleteByDriver($driverId);
        
        // Διαγραφή του οδηγού
        return $this->driversRepository->delete($driverId);
    }
    
    /**
     * Βρίσκει έναν οδηγό με βάση το ID του
     *
     * @param int $driverId Το ID του οδηγού
     * @return array|null Τα δεδομένα του οδηγού ή null αν δεν βρεθεί
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function findDriver(int $driverId)
    {
        // Εύρεση του οδηγού
        $driver = $this->driversRepository->find($driverId);
        
        if (!$driver) {
            return null;
        }
        
        // Εύρεση των αδειών οδήγησης
        $driver['licenses'] = $this->driverLicenseRepository->findByDriver($driverId);
        
        // Εύρεση των δεξιοτήτων
        $driver['skills'] = $this->driverSkillsRepository->findByDriver($driverId);
        
        return $driver;
    }
    
    /**
     * Επικυρώνει τα δεδομένα ενός οδηγού
     *
     * @param array $data Τα δεδομένα του οδηγού
     * @throws ValidationException Αν τα δεδομένα δεν είναι έγκυρα
     */
    private function validateDriverData(array $data)
    {
        $errors = [];
        
        // Έλεγχος υποχρεωτικών πεδίων
        $requiredFields = ['first_name', 'last_name', 'email', 'phone'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[$field] = "Το πεδίο $field είναι υποχρεωτικό.";
            }
        }
        
        // Έλεγχος email
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Το email δεν είναι έγκυρο.";
        }
        
        // Έλεγχος τηλεφώνου
        if (isset($data['phone']) && !preg_match('/^[0-9]{10}$/', $data['phone'])) {
            $errors['phone'] = "Το τηλέφωνο πρέπει να αποτελείται από 10 ψηφία.";
        }
        
        // Αν υπάρχουν σφάλματα, ρίχνουμε exception
        if (!empty($errors)) {
            throw new ValidationException("Τα δεδομένα του οδηγού δεν είναι έγκυρα.", $errors);
        }
    }
}
```

### 2. Ενημέρωση των Controllers

Οι controllers θα πρέπει να ενημερωθούν για να χρησιμοποιούν τις νέες υπηρεσίες αντί να περιέχουν την επιχειρηματική λογική. Αυτό θα απλοποιήσει τους controllers και θα τους κάνει πιο εύκολους στη συντήρηση.

#### 2.1 Παράδειγμα Controller

```php
namespace Drivejob\Controllers\Driver;

use Drivejob\Core\Session;
use Drivejob\Core\AuthMiddleware;
use Drivejob\Core\Exceptions\ValidationException;
use Drivejob\Core\Exceptions\DatabaseException;
use Drivejob\Services\User\DriverService;

class DriversController extends \Drivejob\Controllers\BaseController
{
    private $driverService;
    
    public function __construct()
    {
        parent::__construct();
        
        // Αρχικοποίηση του DriverService
        $this->driverService = new DriverService(
            $this->driversRepository,
            $this->driverLicenseRepository,
            $this->driverSkillsRepository
        );
    }
    
    /**
     * Εμφανίζει τη φόρμα εγγραφής οδηγού
     */
    public function showRegistrationForm()
    {
        include ROOT_DIR . '/src/Views/drivers/registration.php';
    }
    
    /**
     * Εγγράφει έναν νέο οδηγό
     */
    public function register()
    {
        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'drivers/register');
            exit();
        }
        
        try {
            // Συλλογή των δεδομένων από τη φόρμα
            $data = $this->collectFormData();
            
            // Δημιουργία του οδηγού μέσω του DriverService
            $driverId = $this->driverService->createDriver($data);
            
            // Αποθήκευση του ID του οδηγού στη συνεδρία
            Session::set('user_id', $driverId);
            Session::set('user_role', 'driver');
            
            // Ανακατεύθυνση στη σελίδα καλωσορίσματος
            header('Location: ' . BASE_URL . 'drivers/welcome');
            exit();
        } catch (ValidationException $e) {
            // Αποθήκευση των σφαλμάτων και των δεδομένων της φόρμας
            Session::set('errors', $e->getErrors());
            Session::set('old_input', $_POST);
            
            // Ανακατεύθυνση πίσω στη φόρμα εγγραφής
            header('Location: ' . BASE_URL . 'drivers/register');
            exit();
        } catch (DatabaseException $e) {
            // Αποθήκευση του μηνύματος σφάλματος
            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            
            // Ανακατεύθυνση πίσω στη φόρμα εγγραφής
            header('Location: ' . BASE_URL . 'drivers/register');
            exit();
        } catch (\Exception $e) {
            // Αποθήκευση του μηνύματος σφάλματος
            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            
            // Ανακατεύθυνση πίσω στη φόρμα εγγραφής
            header('Location: ' . BASE_URL . 'drivers/register');
            exit();
        }
    }
    
    /**
     * Εμφανίζει το προφίλ του οδηγού
     */
    public function profile()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως οδηγός
        AuthMiddleware::hasRole('driver');
        
        // Λήψη του ID του οδηγού
        $driverId = Session::get('user_id');
        
        try {
            // Εύρεση του οδηγού μέσω του DriverService
            $driver = $this->driverService->findDriver($driverId);
            
            if (!$driver) {
                Session::set('error_message', 'Ο οδηγός δεν βρέθηκε.');
                header('Location: ' . BASE_URL . 'home');
                exit();
            }
            
            // Φόρτωση του view
            include ROOT_DIR . '/src/Views/drivers/profile.php';
        } catch (DatabaseException $e) {
            // Αποθήκευση του μηνύματος σφάλματος
            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            
            // Ανακατεύθυνση στην αρχική σελίδα
            header('Location: ' . BASE_URL . 'home');
            exit();
        } catch (\Exception $e) {
            // Αποθήκευση του μηνύματος σφάλματος
            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            
            // Ανακατεύθυνση στην αρχική σελίδα
            header('Location: ' . BASE_URL . 'home');
            exit();
        }
    }
    
    // Άλλες μέθοδοι του controller...
}
```

### 3. Δημιουργία Interfaces για τις Υπηρεσίες

Για να διευκολυνθεί η δοκιμή και η συντήρηση του κώδικα, προτείνεται η δημιουργία interfaces για τις υπηρεσίες. Αυτό θα επιτρέψει την εύκολη αντικατάσταση των υπηρεσιών με mock objects κατά τη διάρκεια των δοκιμών.

#### 3.1 Παράδειγμα Interface

```php
namespace Drivejob\Services\User;

interface DriverServiceInterface
{
    /**
     * Δημιουργεί έναν νέο οδηγό
     *
     * @param array $data Τα δεδομένα του οδηγού
     * @return int Το ID του νέου οδηγού
     * @throws ValidationException Αν τα δεδομένα δεν είναι έγκυρα
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function createDriver(array $data);
    
    /**
     * Ενημερώνει τα στοιχεία ενός οδηγού
     *
     * @param int $driverId Το ID του οδηγού
     * @param array $data Τα νέα δεδομένα του οδηγού
     * @return bool Αν η ενημέρωση ήταν επιτυχής
     * @throws ValidationException Αν τα δεδομένα δεν είναι έγκυρα
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function updateDriver(int $driverId, array $data);
    
    /**
     * Διαγράφει έναν οδηγό
     *
     * @param int $driverId Το ID του οδηγού
     * @return bool Αν η διαγραφή ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function deleteDriver(int $driverId);
    
    /**
     * Βρίσκει έναν οδηγό με βάση το ID του
     *
     * @param int $driverId Το ID του οδηγού
     * @return array|null Τα δεδομένα του οδηγού ή null αν δεν βρεθεί
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function findDriver(int $driverId);
}
```

### 4. Χρήση Dependency Injection

Για να διευκολυνθεί η δοκιμή και η συντήρηση του κώδικα, προτείνεται η χρήση Dependency Injection για την παροχή των υπηρεσιών στους controllers. Αυτό μπορεί να γίνει με τη χρήση ενός Container.

#### 4.1 Ενημέρωση του Container

```php
// src/bootstrap.php

// Αρχικοποίηση του Container
$container = \Drivejob\Core\Container::getInstance();

// Καταχώρηση των repositories
$container->set('DriversRepository', function () use ($container) {
    return new \Drivejob\Repositories\DriversRepository($container->get('pdo'));
});

$container->set('DriverLicenseRepository', function () use ($container) {
    return new \Drivejob\Repositories\DriverLicenseRepository($container->get('pdo'));
});

$container->set('DriverSkillsRepository', function () use ($container) {
    return new \Drivejob\Repositories\DriverSkillsRepository($container->get('pdo'));
});

// Καταχώρηση των υπηρεσιών
$container->set('DriverService', function () use ($container) {
    return new \Drivejob\Services\User\DriverService(
        $container->get('DriversRepository'),
        $container->get('DriverLicenseRepository'),
        $container->get('DriverSkillsRepository')
    );
});

// Επιστροφή του Container
return $container;
```

#### 4.2 Ενημέρωση του Controller

```php
namespace Drivejob\Controllers\Driver;

use Drivejob\Core\Session;
use Drivejob\Core\AuthMiddleware;
use Drivejob\Core\Exceptions\ValidationException;
use Drivejob\Core\Exceptions\DatabaseException;
use Drivejob\Services\User\DriverServiceInterface;

class DriversController extends \Drivejob\Controllers\BaseController
{
    private $driverService;
    
    public function __construct(DriverServiceInterface $driverService = null)
    {
        parent::__construct();
        
        // Αν δεν έχει παραχθεί DriverService, χρησιμοποιούμε το Container
        if ($driverService === null) {
            $container = \Drivejob\Core\Container::getInstance();
            $this->driverService = $container->get('DriverService');
        } else {
            $this->driverService = $driverService;
        }
    }
    
    // Μέθοδοι του controller...
}
```

## Πλεονεκτήματα του Service Layer Pattern

- **Διαχωρισμός ευθυνών**: Η επιχειρηματική λογική διαχωρίζεται από τους controllers και τα models
- **Επαναχρησιμοποίηση κώδικα**: Οι υπηρεσίες μπορούν να χρησιμοποιηθούν από διάφορους controllers
- **Ευκολότερη συντήρηση**: Οι αλλαγές στην επιχειρηματική λογική γίνονται σε ένα μόνο σημείο
- **Ευκολότερη δοκιμή**: Οι υπηρεσίες μπορούν να δοκιμαστούν ανεξάρτητα από τους controllers
- **Καλύτερη οργάνωση**: Ο κώδικας είναι καλύτερα οργανωμένος και πιο εύκολος στην κατανόηση

## Επόμενα Βήματα

1. **Δημιουργία των interfaces για τις υπηρεσίες**: Δημιουργία των interfaces για όλες τις υπηρεσίες
2. **Υλοποίηση των υπηρεσιών**: Υλοποίηση των υπηρεσιών με βάση τα interfaces
3. **Ενημέρωση του Container**: Ενημέρωση του Container για την καταχώρηση των υπηρεσιών
4. **Ενημέρωση των controllers**: Ενημέρωση των controllers για τη χρήση των υπηρεσιών
5. **Δοκιμή**: Δοκιμή των υπηρεσιών και των controllers για να βεβαιωθούμε ότι λειτουργούν σωστά

## Συμπέρασμα

Η εφαρμογή του Service Layer Pattern στο DriveJob θα βελτιώσει σημαντικά την οργάνωση του κώδικα, θα διευκολύνει τη συντήρηση και θα επιτρέψει την ευκολότερη επέκταση της εφαρμογής. Αυτό θα οδηγήσει σε ένα πιο σταθερό και αξιόπιστο σύστημα που θα μπορεί να αναπτυχθεί και να συντηρηθεί πιο εύκολα.
