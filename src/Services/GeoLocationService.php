<?php

namespace Drivejob\Services;

use Drivejob\Core\Logger;

/**
 * Υπηρεσία για τη διαχείριση γεωγραφικών δεδομένων και αναζήτησης
 */
class GeoLocationService
{
    /**
     * @var string Το κλειδί API για την υπηρεσία γεωκωδικοποίησης
     */
    private $apiKey;

    /**
     * @var array Προσωρινή μνήμη για τις γεωγραφικές συντεταγμένες
     */
    private $coordinatesCache = [];

    /**
     * Constructor
     * 
     * @param string|null $apiKey Το κλειδί API για την υπηρεσία γεωκωδικοποίησης
     */
    public function __construct($apiKey = null)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Μετατρέπει μια διεύθυνση σε γεωγραφικές συντεταγμένες
     * 
     * @param string $address Η διεύθυνση προς γεωκωδικοποίηση
     * @return array|false Οι συντεταγμένες (lat, lng) ή false σε περίπτωση αποτυχίας
     */
    public function geocodeAddress($address)
    {
        // Έλεγχος αν η διεύθυνση υπάρχει ήδη στην προσωρινή μνήμη
        if (isset($this->coordinatesCache[$address])) {
            return $this->coordinatesCache[$address];
        }

        try {
            // Σε μια πραγματική εφαρμογή, εδώ θα γινόταν κλήση σε μια υπηρεσία γεωκωδικοποίησης
            // όπως το Google Maps Geocoding API ή το OpenStreetMap Nominatim

            // Για τους σκοπούς αυτής της υλοποίησης, θα χρησιμοποιήσουμε μια απλοποιημένη προσέγγιση
            // με προκαθορισμένες συντεταγμένες για κάποιες πόλεις της Ελλάδας

            $knownLocations = [
                'Αθήνα' => ['lat' => 37.9838, 'lng' => 23.7275],
                'Θεσσαλονίκη' => ['lat' => 40.6401, 'lng' => 22.9444],
                'Πάτρα' => ['lat' => 38.2466, 'lng' => 21.7345],
                'Ηράκλειο' => ['lat' => 35.3387, 'lng' => 25.1442],
                'Λάρισα' => ['lat' => 39.6390, 'lng' => 22.4174],
                'Βόλος' => ['lat' => 39.3621, 'lng' => 22.9460],
                'Ιωάννινα' => ['lat' => 39.6650, 'lng' => 20.8537],
                'Τρίκαλα' => ['lat' => 39.5557, 'lng' => 21.7679],
                'Χαλκίδα' => ['lat' => 38.4640, 'lng' => 23.5944],
                'Σέρρες' => ['lat' => 41.0914, 'lng' => 23.5470],
                'Αλεξανδρούπολη' => ['lat' => 40.8475, 'lng' => 25.8745],
                'Ξάνθη' => ['lat' => 41.1363, 'lng' => 24.8877],
                'Κατερίνη' => ['lat' => 40.2719, 'lng' => 22.5025],
                'Αγρίνιο' => ['lat' => 38.6210, 'lng' => 21.4078],
                'Καλαμάτα' => ['lat' => 37.0391, 'lng' => 22.1142],
                'Καβάλα' => ['lat' => 40.9374, 'lng' => 24.4122],
                'Ρόδος' => ['lat' => 36.4340, 'lng' => 28.2176],
                'Κέρκυρα' => ['lat' => 39.6243, 'lng' => 19.9217],
                'Κοζάνη' => ['lat' => 40.3006, 'lng' => 21.7888],
                'Δράμα' => ['lat' => 41.1531, 'lng' => 24.1462]
            ];

            // Αναζήτηση της τοποθεσίας στις γνωστές τοποθεσίες
            foreach ($knownLocations as $location => $coordinates) {
                if (stripos($address, $location) !== false) {
                    // Αποθήκευση στην προσωρινή μνήμη
                    $this->coordinatesCache[$address] = $coordinates;
                    return $coordinates;
                }
            }

            // Αν δεν βρέθηκε η τοποθεσία, επιστρέφουμε προεπιλεγμένες συντεταγμένες (κέντρο Αθήνας)
            $defaultCoordinates = ['lat' => 37.9838, 'lng' => 23.7275];
            $this->coordinatesCache[$address] = $defaultCoordinates;

            Logger::info('Geocoding fallback to default coordinates for address: ' . $address);

            return $defaultCoordinates;
        } catch (\Exception $e) {
            Logger::error('Error in geocodeAddress: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Υπολογίζει την απόσταση μεταξύ δύο σημείων σε χιλιόμετρα
     * 
     * @param float $lat1 Το γεωγραφικό πλάτος του πρώτου σημείου
     * @param float $lng1 Το γεωγραφικό μήκος του πρώτου σημείου
     * @param float $lat2 Το γεωγραφικό πλάτος του δεύτερου σημείου
     * @param float $lng2 Το γεωγραφικό μήκος του δεύτερου σημείου
     * @return float Η απόσταση σε χιλιόμετρα
     */
    public function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        // Υπολογισμός της απόστασης με τον τύπο Haversine
        $earthRadius = 6371; // Ακτίνα της Γης σε χιλιόμετρα

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }

    /**
     * Υπολογίζει την απόσταση μεταξύ δύο διευθύνσεων
     * 
     * @param string $address1 Η πρώτη διεύθυνση
     * @param string $address2 Η δεύτερη διεύθυνση
     * @return float|false Η απόσταση σε χιλιόμετρα ή false σε περίπτωση αποτυχίας
     */
    public function calculateDistanceBetweenAddresses($address1, $address2)
    {
        // Γεωκωδικοποίηση των διευθύνσεων
        $coordinates1 = $this->geocodeAddress($address1);
        $coordinates2 = $this->geocodeAddress($address2);

        if (!$coordinates1 || !$coordinates2) {
            return false;
        }

        // Υπολογισμός της απόστασης
        return $this->calculateDistance(
            $coordinates1['lat'],
            $coordinates1['lng'],
            $coordinates2['lat'],
            $coordinates2['lng']
        );
    }

    /**
     * Υπολογίζει το σκορ συμβατότητας τοποθεσίας με βάση την απόσταση
     * 
     * @param string $location1 Η πρώτη τοποθεσία
     * @param string $location2 Η δεύτερη τοποθεσία
     * @param float $maxDistance Η μέγιστη απόσταση σε χιλιόμετρα (προεπιλογή: 100)
     * @return float Το σκορ συμβατότητας (0-1)
     */
    public function calculateLocationScore($location1, $location2, $maxDistance = 100)
    {
        // Αν οι τοποθεσίες είναι ίδιες, επιστρέφουμε 1.0
        if (strtolower(trim($location1)) === strtolower(trim($location2))) {
            return 1.0;
        }

        // Υπολογισμός της απόστασης
        $distance = $this->calculateDistanceBetweenAddresses($location1, $location2);

        if ($distance === false) {
            // Αν δεν μπορούμε να υπολογίσουμε την απόσταση, επιστρέφουμε ένα προεπιλεγμένο σκορ
            return 0.5;
        }

        // Υπολογισμός του σκορ με βάση την απόσταση
        // Όσο μικρότερη η απόσταση, τόσο μεγαλύτερο το σκορ
        if ($distance <= 0) {
            return 1.0;
        } elseif ($distance >= $maxDistance) {
            return 0.0;
        } else {
            return 1.0 - ($distance / $maxDistance);
        }
    }

    /**
     * Αναζητά τοποθεσίες κοντά σε μια δεδομένη τοποθεσία
     * 
     * @param string $location Η τοποθεσία αναφοράς
     * @param float $maxDistance Η μέγιστη απόσταση σε χιλιόμετρα
     * @param array $locations Οι τοποθεσίες προς αναζήτηση
     * @return array Οι τοποθεσίες που βρίσκονται εντός της μέγιστης απόστασης
     */
    public function findNearbyLocations($location, $maxDistance, array $locations)
    {
        $nearbyLocations = [];
        $coordinates = $this->geocodeAddress($location);

        if (!$coordinates) {
            return $nearbyLocations;
        }

        foreach ($locations as $loc) {
            $locCoordinates = $this->geocodeAddress($loc);

            if (!$locCoordinates) {
                continue;
            }

            $distance = $this->calculateDistance(
                $coordinates['lat'],
                $coordinates['lng'],
                $locCoordinates['lat'],
                $locCoordinates['lng']
            );

            if ($distance <= $maxDistance) {
                $nearbyLocations[] = [
                    'location' => $loc,
                    'distance' => $distance
                ];
            }
        }

        // Ταξινόμηση των τοποθεσιών με βάση την απόσταση
        usort($nearbyLocations, function ($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        return $nearbyLocations;
    }

    /**
     * Καθαρίζει την προσωρινή μνήμη των συντεταγμένων
     */
    public function clearCache()
    {
        $this->coordinatesCache = [];
    }
}
