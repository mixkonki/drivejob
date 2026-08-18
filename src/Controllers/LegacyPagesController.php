<?php

namespace Drivejob\Controllers;

/**
 * Σερβίρει τις legacy σελίδες μηνυμάτων/συνομιλιών μέσω routes.
 *
 * Οι σελίδες στο src/Legacy/ είναι αυτόνομες (κάνουν δικό τους έλεγχο
 * σύνδεσης/ρόλου και δικά τους queries). Μεταφέρθηκαν εκτός public/
 * ώστε κάθε αίτημα να περνά από τον router — η πλήρης μετάπτωσή τους
 * σε κανονικά Controller/Views προγραμματίζεται στο Πακέτο 5.
 */
class LegacyPagesController extends BaseController
{
    public function driverMessages()
    {
        $this->renderLegacy('drivers-messages.php');
    }

    public function driverConversation()
    {
        $this->renderLegacy('drivers-conversation.php');
    }

    public function companyMessages()
    {
        $this->renderLegacy('companies-messages.php');
    }

    public function companyConversation()
    {
        $this->renderLegacy('companies-conversation.php');
    }

    private function renderLegacy(string $file): void
    {
        $path = ROOT_DIR . '/src/Legacy/' . $file;
        if (!is_file($path)) {
            http_response_code(404);
            echo 'Η σελίδα δεν βρέθηκε.';
            exit;
        }
        require $path;
        exit;
    }
}
