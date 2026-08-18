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

    // ---- Admin panel & εργαλεία (Πακέτο 4) ------------------------------

    public function adminPanel()
    {
        // Το παλιό admin/index.php φόρτωνε το ίδιο dashboard view — ενιαία είσοδος πλέον
        header('Location: ' . BASE_URL . 'admin/dashboard', true, 301);
        exit;
    }

    public function adminTool($tool)
    {
        \Drivejob\Core\AuthMiddleware::hasRole('admin');
        $map = [
            'ai-settings' => 'admin/ai-settings.php',
            'openai-settings' => 'admin/openai-settings.php',
            'settings' => 'admin/settings.php',
            'matching-dashboard' => 'admin/matching-dashboard.php',
            'identity-linker' => 'admin/identity-linker.php',
        ];
        if (!isset($map[$tool])) {
            http_response_code(404);
            echo 'Άγνωστο εργαλείο.';
            exit;
        }
        $this->renderLegacy($map[$tool]);
    }

    // ---- Legacy API endpoints (τα αρχεία κάνουν δικούς τους ελέγχους) ----

    public function api($endpoint)
    {
        $map = [
            'csrf-token' => 'api/csrf_token.php',
            'matches-simple' => 'api/matches-simple.php',
            'candidates-get' => 'api/candidates-get.php',
            'messaging-send' => 'api/messaging-send.php',
        ];
        if (!isset($map[$endpoint])) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'unknown_endpoint']);
            exit;
        }
        $this->renderLegacy($map[$endpoint]);
    }

    public function adminApi($endpoint)
    {
        // Τα admin APIs έχουν RBAC ελέγχους εσωτερικά· εδώ επιπλέον φραγή ρόλου
        \Drivejob\Core\AuthMiddleware::hasRole('admin');
        $map = [
            'metrics' => 'api/admin/matching_metrics.php',
            'metrics-prom' => 'api/admin/matching_metrics_prom.php',
            'enqueue-demo' => 'api/admin/matching_enqueue_demo.php',
            'users' => 'api/admin/users.php',
            'users-overview' => 'api/admin/users_overview.php',
            'link-identity' => 'api/admin/link_identity.php',
        ];
        if (!isset($map[$endpoint])) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'unknown_endpoint']);
            exit;
        }
        $this->renderLegacy($map[$endpoint]);
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
