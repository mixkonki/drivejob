<?php

namespace Drivejob\Core;

/**
 * Container για Dependency Injection
 * 
 * Αυτή η κλάση υλοποιεί ένα απλό container για dependency injection,
 * επιτρέποντας την καταχώρηση και ανάκτηση υπηρεσιών.
 */
class Container
{
    /**
     * @var Container Η μοναδική περίσταση του Container (Singleton pattern)
     */
    private static $instance = null;

    /**
     * @var array Οι καταχωρημένες υπηρεσίες
     */
    private $services = [];

    /**
     * Ιδιωτικός constructor για αποτροπή δημιουργίας πολλαπλών περιστάσεων
     */
    private function __construct() {}

    /**
     * Επιστρέφει τη μοναδική περίσταση του Container
     *
     * @return Container
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Καταχωρεί μια υπηρεσία στο container
     *
     * @param string $id Το αναγνωριστικό της υπηρεσίας
     * @param callable $factory Η συνάρτηση που δημιουργεί την υπηρεσία
     * @param bool $shared Αν η υπηρεσία είναι κοινόχρηστη (singleton)
     * @return $this
     */
    public function set($id, callable $factory, $shared = true)
    {
        $this->services[$id] = [
            'factory' => $factory,
            'shared' => $shared,
            'instance' => null
        ];

        return $this;
    }

    /**
     * Επιστρέφει μια υπηρεσία από το container
     *
     * @param string $id Το αναγνωριστικό της υπηρεσίας
     * @return mixed Η υπηρεσία
     * @throws \Exception Αν η υπηρεσία δεν βρεθεί
     */
    public function get($id)
    {
        if (!isset($this->services[$id])) {
            throw new \Exception("Η υπηρεσία '{$id}' δεν βρέθηκε στο container.");
        }

        $service = $this->services[$id];

        // Αν η υπηρεσία είναι κοινόχρηστη και έχει ήδη δημιουργηθεί, επιστροφή της υπάρχουσας περίστασης
        if ($service['shared'] && $service['instance'] !== null) {
            return $service['instance'];
        }

        // Δημιουργία της υπηρεσίας
        $instance = call_user_func($service['factory'], $this);

        // Αν η υπηρεσία είναι κοινόχρηστη, αποθήκευση της περίστασης
        if ($service['shared']) {
            $this->services[$id]['instance'] = $instance;
        }

        return $instance;
    }

    /**
     * Ελέγχει αν μια υπηρεσία υπάρχει στο container
     *
     * @param string $id Το αναγνωριστικό της υπηρεσίας
     * @return bool Αν υπάρχει η υπηρεσία
     */
    public function has($id)
    {
        return isset($this->services[$id]);
    }

    /**
     * Αφαιρεί μια υπηρεσία από το container
     *
     * @param string $id Το αναγνωριστικό της υπηρεσίας
     * @return $this
     */
    public function remove($id)
    {
        unset($this->services[$id]);
        return $this;
    }

    /**
     * Καταχωρεί μια υπάρχουσα περίσταση ως υπηρεσία
     *
     * @param string $id Το αναγνωριστικό της υπηρεσίας
     * @param mixed $instance Η περίσταση
     * @return $this
     */
    public function setInstance($id, $instance)
    {
        $this->services[$id] = [
            'factory' => function () use ($instance) {
                return $instance;
            },
            'shared' => true,
            'instance' => $instance
        ];

        return $this;
    }

    /**
     * Επιστρέφει όλες τις καταχωρημένες υπηρεσίες
     *
     * @return array Οι καταχωρημένες υπηρεσίες
     */
    public function getServices()
    {
        return array_keys($this->services);
    }
}
