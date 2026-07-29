<?php

namespace Drivejob\Controllers\Api;

use Drivejob\Core\Controller;
use Drivejob\Core\Database;
use Drivejob\Core\JsonResponse;
use Drivejob\Core\Auth;

class CompanyFeaturesController extends Controller
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance()->getConnection();

        // Ensure user is authenticated and is a company
        if (!Auth::check() || Auth::user()['role'] !== 'company') {
            JsonResponse::error('Unauthorized', 401);
            exit;
        }
    }

    /**
     * Get fleet vehicles
     */
    public function getFleetVehicles()
    {
        try {
            $companyId = Auth::user()['company_id'];

            $stmt = $this->db->prepare("
                SELECT * FROM company_fleet_vehicles 
                WHERE company_id = ? AND is_active = 1
                ORDER BY vehicle_type, license_plate
            ");
            $stmt->execute([$companyId]);
            $vehicles = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            JsonResponse::success($vehicles);
        } catch (\Exception $e) {
            JsonResponse::error('Failed to load vehicles', 500);
        }
    }

    /**
     * Get driver statistics
     */
    public function getDriverStats()
    {
        try {
            $companyId = Auth::user()['company_id'];

            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN is_active = 1 AND assigned_vehicle_id IS NOT NULL THEN 1 ELSE 0 END) as on_duty,
                    SUM(CASE WHEN is_active = 1 AND assigned_vehicle_id IS NULL THEN 1 ELSE 0 END) as available
                FROM company_driver_management
                WHERE company_id = ?
            ");
            $stmt->execute([$companyId]);
            $stats = $stmt->fetch(\PDO::FETCH_ASSOC);

            JsonResponse::success($stats);
        } catch (\Exception $e) {
            JsonResponse::error('Failed to load driver statistics', 500);
        }
    }

    /**
     * Upgrade subscription plan
     */
    public function upgradeSubscription()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $plan = $data['plan'] ?? null;

            if (!in_array($plan, ['basic', 'professional', 'enterprise', 'custom'])) {
                JsonResponse::error('Invalid plan selected', 400);
                return;
            }

            $companyId = Auth::user()['company_id'];

            // Calculate expiry date (1 year from now)
            $expiryDate = date('Y-m-d H:i:s', strtotime('+1 year'));

            // Update subscription
            $stmt = $this->db->prepare("
                UPDATE companies 
                SET subscription_plan = ?, 
                    subscription_expires_at = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$plan, $expiryDate, $companyId]);

            // Enable modules based on plan
            $modules = $this->getModulesForPlan($plan);
            $stmt = $this->db->prepare("
                UPDATE companies 
                SET enabled_modules = ?
                WHERE id = ?
            ");
            $stmt->execute([json_encode($modules), $companyId]);

            JsonResponse::success([
                'message' => 'Subscription upgraded successfully',
                'plan' => $plan,
                'expires_at' => $expiryDate
            ]);
        } catch (\Exception $e) {
            JsonResponse::error('Failed to upgrade subscription', 500);
        }
    }

    /**
     * Get fleet analytics data
     */
    public function getFleetAnalytics()
    {
        try {
            $companyId = Auth::user()['company_id'];

            // Get vehicle utilization
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_vehicles,
                    SUM(CASE WHEN cdm.driver_id IS NOT NULL THEN 1 ELSE 0 END) as in_use,
                    SUM(CASE WHEN cdm.driver_id IS NULL AND cfv.next_service_date > NOW() THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN cfv.next_service_date <= NOW() THEN 1 ELSE 0 END) as maintenance
                FROM company_fleet_vehicles cfv
                LEFT JOIN company_driver_management cdm ON cfv.id = cdm.assigned_vehicle_id AND cdm.is_active = 1
                WHERE cfv.company_id = ? AND cfv.is_active = 1
            ");
            $stmt->execute([$companyId]);
            $utilization = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Get monthly performance
            $stmt = $this->db->prepare("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    AVG(performance_rating) as avg_performance
                FROM company_driver_management
                WHERE company_id = ? AND performance_rating IS NOT NULL
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month DESC
                LIMIT 6
            ");
            $stmt->execute([$companyId]);
            $performance = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            JsonResponse::success([
                'utilization' => $utilization,
                'performance' => array_reverse($performance)
            ]);
        } catch (\Exception $e) {
            JsonResponse::error('Failed to load analytics', 500);
        }
    }

    /**
     * Add new vehicle to fleet
     */
    public function addVehicle()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $companyId = Auth::user()['company_id'];

            // Validate required fields
            $required = ['vehicle_type', 'license_plate', 'brand', 'model', 'year'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    JsonResponse::error("Field '$field' is required", 400);
                    return;
                }
            }

            // Check if license plate already exists
            $stmt = $this->db->prepare("SELECT id FROM company_fleet_vehicles WHERE license_plate = ?");
            $stmt->execute([$data['license_plate']]);
            if ($stmt->fetch()) {
                JsonResponse::error('License plate already exists', 400);
                return;
            }

            // Insert vehicle
            $stmt = $this->db->prepare("
                INSERT INTO company_fleet_vehicles 
                (company_id, vehicle_type, license_plate, brand, model, year, 
                 capacity_tons, fuel_type, euro_class, next_service_date, 
                 next_kteo_date, insurance_expires, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $companyId,
                $data['vehicle_type'],
                $data['license_plate'],
                $data['brand'],
                $data['model'],
                $data['year'],
                $data['capacity_tons'] ?? null,
                $data['fuel_type'] ?? 'diesel',
                $data['euro_class'] ?? null,
                $data['next_service_date'] ?? null,
                $data['next_kteo_date'] ?? null,
                $data['insurance_expires'] ?? null,
                $data['notes'] ?? null
            ]);

            // Update fleet size
            $stmt = $this->db->prepare("
                UPDATE companies 
                SET fleet_size = (SELECT COUNT(*) FROM company_fleet_vehicles WHERE company_id = ? AND is_active = 1)
                WHERE id = ?
            ");
            $stmt->execute([$companyId, $companyId]);

            JsonResponse::success([
                'message' => 'Vehicle added successfully',
                'vehicle_id' => $this->db->lastInsertId()
            ]);
        } catch (\Exception $e) {
            JsonResponse::error('Failed to add vehicle', 500);
        }
    }

    /**
     * Get compliance documents
     */
    public function getComplianceDocuments()
    {
        try {
            $companyId = Auth::user()['company_id'];

            $stmt = $this->db->prepare("
                SELECT * FROM company_compliance_tracking
                WHERE company_id = ?
                ORDER BY expires_date ASC
            ");
            $stmt->execute([$companyId]);
            $documents = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Check for expiring documents
            $expiringCount = 0;
            foreach ($documents as &$doc) {
                $expiryDate = new \DateTime($doc['expires_date']);
                $today = new \DateTime();
                $diff = $today->diff($expiryDate);

                if ($diff->days <= 30 && !$diff->invert) {
                    $doc['expiring_soon'] = true;
                    $expiringCount++;
                }
            }

            JsonResponse::success([
                'documents' => $documents,
                'expiring_count' => $expiringCount
            ]);
        } catch (\Exception $e) {
            JsonResponse::error('Failed to load compliance documents', 500);
        }
    }

    /**
     * Get modules for subscription plan
     */
    private function getModulesForPlan($plan)
    {
        $modules = [
            'basic' => ['job_posting', 'driver_search'],
            'professional' => ['job_posting', 'driver_search', 'ats', 'driver_management', 'analytics'],
            'enterprise' => ['job_posting', 'driver_search', 'ats', 'driver_management', 'fleet_management', 'compliance', 'analytics', 'api_access'],
            'custom' => ['job_posting', 'driver_search', 'ats', 'driver_management', 'fleet_management', 'compliance', 'analytics', 'api_access']
        ];

        return $modules[$plan] ?? $modules['basic'];
    }
}
