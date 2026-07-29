<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Supervisor;

use App\Services\HealthStatus;
use App\Services\Interfaces\ServiceInterface;
use App\Services\Supervisor\MainSupervisor;
use App\Services\Supervisor\SupervisorResult;
use App\Services\SupervisorStatus;
use App\Services\SupervisorResult as GlobalSupervisorResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Test suite for MainSupervisor functionality.
 *
 * This test class covers all aspects of the MainSupervisor including
 * service supervision, failure handling, system status, and supervisor management.
 */
class MainSupervisorTest extends TestCase
{
    private MainSupervisor $mainSupervisor;
    private LoggerInterface|MockObject $logger;
    private array $configuration;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->configuration = [
            'max_retry_attempts' => 2,
            'health_check_interval' => 30,
            'timeout_seconds' => 5,
        ];

        $this->mainSupervisor = new MainSupervisor($this->configuration, $this->logger);
    }

    /**
     * Test MainSupervisor initialization.
     */
    public function testInitialization(): void
    {
        $this->assertEquals('MainSupervisor', $this->mainSupervisor->getName());
        $this->assertEquals(SupervisorStatus::HEALTHY, $this->mainSupervisor->getStatus());
        $this->assertEmpty($this->mainSupervisor->getManagedServices());
    }

    /**
     * Test supervising a healthy service.
     */
    public function testSuperviseHealthyService(): void
    {
        $service = $this->createMock(ServiceInterface::class);
        $service->expects($this->once())
            ->method('getName')
            ->willReturn('TestService');

        $service->expects($this->once())
            ->method('isOperational')
            ->willReturn(true);

        $service->expects($this->once())
            ->method('getHealth')
            ->willReturn(HealthStatus::HEALTHY);

        $service->expects($this->once())
            ->method('execute')
            ->willReturn($this->createMock(\App\Services\ServiceResult::class));

        $this->mainSupervisor->addService($service);

        $result = $this->mainSupervisor->supervise($service);

        $this->assertInstanceOf(GlobalSupervisorResult::class, $result);
        $this->assertTrue($result->isSuccessful());
    }

    /**
     * Test supervising an unhealthy service.
     */
    public function testSuperviseUnhealthyService(): void
    {
        $service = $this->createMock(ServiceInterface::class);
        $service->expects($this->any())
            ->method('getName')
            ->willReturn('UnhealthyService');

        $service->expects($this->once())
            ->method('isOperational')
            ->willReturn(false);

        $this->mainSupervisor->addService($service);

        $result = $this->mainSupervisor->supervise($service);

        $this->assertInstanceOf(GlobalSupervisorResult::class, $result);
        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(SupervisorStatus::CRITICAL, $result->status);
    }

    /**
     * Test supervising all services.
     */
    public function testSuperviseAll(): void
    {
        // Create multiple mock services
        $services = [];
        for ($i = 0; $i < 3; $i++) {
            $service = $this->createMock(ServiceInterface::class);
            $service->expects($this->any())
                ->method('getName')
                ->willReturn("Service{$i}");

            $service->expects($this->any())
                ->method('isOperational')
                ->willReturn(true);

            $service->expects($this->any())
                ->method('getHealth')
                ->willReturn(HealthStatus::HEALTHY);

            $service->expects($this->any())
                ->method('execute')
                ->willReturn($this->createMock(\App\Services\ServiceResult::class));

            $services[] = $service;
            $this->mainSupervisor->addService($service);
        }

        $results = $this->mainSupervisor->superviseAll();

        $this->assertIsArray($results);
        $this->assertCount(3, $results);

        foreach ($results as $serviceName => $result) {
            $this->assertInstanceOf(GlobalSupervisorResult::class, $result);
            $this->assertTrue($result->isSuccessful());
        }
    }

    /**
     * Test handling service failures.
     */
    public function testHandleFailure(): void
    {
        $service = $this->createMock(ServiceInterface::class);
        $service->expects($this->any())
            ->method('getName')
            ->willReturn('FailingService');

        // Mock service operations for recovery
        $service->expects($this->any())
            ->method('shutdown')
            ->willReturn(true);

        $service->expects($this->any())
            ->method('initialize')
            ->willReturn(true);

        $this->mainSupervisor->addService($service);

        $result = $this->mainSupervisor->handleFailure($service);

        $this->assertTrue($result);
    }

    /**
     * Test system health check.
     */
    public function testPerformSystemHealthCheck(): void
    {
        // Add some healthy services
        $service1 = $this->createMock(ServiceInterface::class);
        $service1->method('getName')->willReturn('HealthyService1');
        $service1->method('isOperational')->willReturn(true);
        $service1->method('getHealth')->willReturn(HealthStatus::HEALTHY);

        $service2 = $this->createMock(ServiceInterface::class);
        $service2->method('getName')->willReturn('HealthyService2');
        $service2->method('isOperational')->willReturn(true);
        $service2->method('getHealth')->willReturn(HealthStatus::HEALTHY);

        $this->mainSupervisor->addService($service1);
        $this->mainSupervisor->addService($service2);

        $status = $this->mainSupervisor->performSystemHealthCheck();

        $this->assertEquals(SupervisorStatus::HEALTHY, $status);
    }

    /**
     * Test system status reporting.
     */
    public function testGetSystemStatus(): void
    {
        $service = $this->createMock(ServiceInterface::class);
        $service->method('getName')->willReturn('TestService');
        $service->method('isOperational')->willReturn(true);
        $service->method('getHealth')->willReturn(HealthStatus::HEALTHY);

        $this->mainSupervisor->addService($service);

        $status = $this->mainSupervisor->getSystemStatus();

        $this->assertIsArray($status);
        $this->assertArrayHasKey('main_supervisor', $status);
        $this->assertArrayHasKey('supervisors', $status);
        $this->assertArrayHasKey('system_metrics', $status);

        $this->assertEquals('MainSupervisor', $status['main_supervisor']['name']);
        $this->assertEquals(1, $status['main_supervisor']['total_services']);
    }

    /**
     * Test adding and removing supervisors.
     */
    public function testSupervisorManagement(): void
    {
        // Create a mock supervisor
        $mockSupervisor = $this->createMock(\App\Services\Interfaces\SupervisorInterface::class);
        $mockSupervisor->method('getName')->willReturn('MockSupervisor');
        $mockSupervisor->method('getManagedServices')->willReturn([]);

        // Test adding supervisor
        $added = $this->mainSupervisor->addSupervisor($mockSupervisor);
        $this->assertTrue($added);

        // Test getting supervisors
        $supervisors = $this->mainSupervisor->getSupervisors();
        $this->assertCount(1, $supervisors);
        $this->assertArrayHasKey('MockSupervisor', $supervisors);

        // Test removing supervisor
        $removed = $this->mainSupervisor->removeSupervisor($mockSupervisor);
        $this->assertTrue($removed);

        // Verify supervisor was removed
        $supervisors = $this->mainSupervisor->getSupervisors();
        $this->assertEmpty($supervisors);
    }

    /**
     * Test configuration updates.
     */
    public function testConfigurationUpdates(): void
    {
        $newConfig = [
            'max_retry_attempts' => 5,
            'timeout_seconds' => 10,
        ];

        $this->mainSupervisor->updateConfiguration($newConfig);

        // The configuration should be merged with existing config
        $currentConfig = $this->mainSupervisor->getConfiguration();
        $this->assertEquals(5, $currentConfig['max_retry_attempts']);
        $this->assertEquals(10, $currentConfig['timeout_seconds']);
    }

    /**
     * Test metrics collection.
     */
    public function testMetricsCollection(): void
    {
        $service = $this->createMock(ServiceInterface::class);
        $service->method('getName')->willReturn('MetricsTestService');
        $service->method('isOperational')->willReturn(true);
        $service->method('getHealth')->willReturn(HealthStatus::HEALTHY);
        $service->method('execute')->willReturn($this->createMock(\App\Services\ServiceResult::class));

        $this->mainSupervisor->addService($service);

        // Supervise the service to generate metrics
        $this->mainSupervisor->supervise($service);

        $metrics = $this->mainSupervisor->getMetrics();

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('total_supervisions', $metrics);
        $this->assertArrayHasKey('successful_supervisions', $metrics);
        $this->assertEquals(1, $metrics['total_supervisions']);
    }

    /**
     * Test error handling during supervision.
     */
    public function testErrorHandling(): void
    {
        $service = $this->createMock(ServiceInterface::class);
        $service->method('getName')->willReturn('ErrorService');
        $service->method('isOperational')->willThrowException(new \RuntimeException('Service error'));

        $this->mainSupervisor->addService($service);

        $result = $this->mainSupervisor->supervise($service);

        $this->assertInstanceOf(GlobalSupervisorResult::class, $result);
        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(SupervisorStatus::CRITICAL, $result->status);
        $this->assertNotNull($result->error);
    }

    /**
     * Test recovery functionality.
     */
    public function testRecovery(): void
    {
        $service = $this->createMock(ServiceInterface::class);
        $service->method('getName')->willReturn('RecoveryTestService');
        $service->method('shutdown')->willReturn(true);
        $service->method('initialize')->willReturn(true);

        $this->mainSupervisor->addService($service);

        $recovered = $this->mainSupervisor->recover($service);

        $this->assertTrue($recovered);
    }

    /**
     * Test service management operations.
     */
    public function testServiceManagement(): void
    {
        $service1 = $this->createMock(ServiceInterface::class);
        $service1->method('getName')->willReturn('Service1');

        $service2 = $this->createMock(ServiceInterface::class);
        $service2->method('getName')->willReturn('Service2');

        // Test adding services
        $this->assertTrue($this->mainSupervisor->addService($service1));
        $this->assertTrue($this->mainSupervisor->addService($service2));

        // Verify services are managed
        $managedServices = $this->mainSupervisor->getManagedServices();
        $this->assertCount(2, $managedServices);

        // Test removing service
        $this->assertTrue($this->mainSupervisor->removeService($service1));

        // Verify service was removed
        $managedServices = $this->mainSupervisor->getManagedServices();
        $this->assertCount(1, $managedServices);
        $this->assertEquals('Service2', $managedServices[0]->getName());
    }

    /**
     * Test status updates based on supervision results.
     */
    public function testStatusUpdates(): void
    {
        // Initially healthy
        $this->assertEquals(SupervisorStatus::HEALTHY, $this->mainSupervisor->getStatus());

        // Add a failing service
        $failingService = $this->createMock(ServiceInterface::class);
        $failingService->method('getName')->willReturn('FailingService');
        $failingService->method('isOperational')->willReturn(false);

        $this->mainSupervisor->addService($failingService);

        // Supervise the failing service multiple times to affect status
        for ($i = 0; $i < 10; $i++) {
            $this->mainSupervisor->supervise($failingService);
        }

        // Status should be degraded or critical due to failures
        $status = $this->mainSupervisor->getStatus();
        $this->assertTrue(
            $status === SupervisorStatus::DEGRADED || $status === SupervisorStatus::CRITICAL
        );
    }
}
