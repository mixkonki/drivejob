<?php

namespace Drivejob\Controllers;

/**
 * Controller για την αυθεντικοποίηση χρηστών
 * 
 * Επεκτείνει τον BaseUserController και χρησιμοποιεί τις κοινές λειτουργίες
 * για τη διαχείριση χρηστών (login, logout, κλπ.)
 */
class AuthController extends BaseUserController
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Κλήση του constructor της γονικής κλάσης
        parent::__construct();
    }
}
