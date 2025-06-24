<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Drivejob\Services\AI\MatchingService;
use Drivejob\Services\AI\FeatureExtractor;
use Drivejob\Services\AI\ScoreCalculator;

class MatchingServiceTest extends TestCase
{
    private $matchingService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock dependencies
        $featureExtractor = $this->createMock(FeatureExtractor::class);
        $scoreCalculator = $this->createMock(ScoreCalculator::class);
        
        $this->matchingService = new MatchingService($featureExtractor, $scoreCalculator);
    }
    
    public function testCalculateMatchReturnsValidScore()
    {
        // Arrange
        $driverId = 1;
        $jobId = 1;
        
        // Act
        $result = $this->matchingService->calculateMatch($driverId, $jobId);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_score', $result);
        $this->assertGreaterThanOrEqual(0, $result['overall_score']);
        $this->assertLessThanOrEqual(100, $result['overall_score']);
    }
    
    public function testGetTopMatchesReturnsArray()
    {
        // Arrange
        $entityId = 1;
        $entityType = 'driver';
        $limit = 5;
        
        // Act
        $result = $this->matchingService->getTopMatches($entityId, $entityType, $limit);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertLessThanOrEqual($limit, count($result));
    }
    
    public function testInvalidEntityTypeThrowsException()
    {
        // Arrange
        $entityId = 1;
        $entityType = 'invalid';
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        
        // Act
        $this->matchingService->getTopMatches($entityId, $entityType);
    }
}