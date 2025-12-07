<?php

declare(strict_types=1);

namespace App\Services;

use Throwable;

/**
 * Result object returned by service operations.
 *
 * Contains the outcome of a service execution including success status,
 * returned data, errors, and metadata.
 */
class ServiceResult
{
    public bool $success;
    public mixed $data;
    public ?Throwable $error;
    public array $metadata;
    public float $executionTime;

    /**
     * Create a new ServiceResult instance.
     */
    public function __construct(
        bool $success = false,
        mixed $data = null,
        ?Throwable $error = null,
        array $metadata = [],
        float $executionTime = 0.0
    ) {
        $this->success = $success;
        $this->data = $data;
        $this->error = $error;
        $this->metadata = $metadata;
        $this->executionTime = $executionTime;
    }

    /**
     * Create a successful result.
     */
    public static function success(
        mixed $data = null,
        array $metadata = [],
        float $executionTime = 0.0
    ): self {
        return new self(
            true,
            $data,
            null,
            $metadata,
            $executionTime
        );
    }

    /**
     * Create a failed result.
     */
    public static function failure(
        Throwable $error,
        mixed $data = null,
        array $metadata = [],
        float $executionTime = 0.0
    ): self {
        return new self(
            false,
            $data,
            $error,
            $metadata,
            $executionTime
        );
    }

    /**
     * Check if the result indicates success.
     */
    public function isSuccessful(): bool
    {
        return $this->success && $this->error === null;
    }

    /**
     * Check if the result indicates failure.
     */
    public function isFailure(): bool
    {
        return !$this->success || $this->error !== null;
    }

    /**
     * Get the error message if present.
     */
    public function getErrorMessage(): ?string
    {
        return $this->error?->getMessage();
    }

    /**
     * Get a summary of the result.
     */
    public function getSummary(): array
    {
        return [
            'success' => $this->success,
            'has_error' => $this->error !== null,
            'error_message' => $this->getErrorMessage(),
            'execution_time' => $this->executionTime,
            'has_data' => $this->data !== null,
            'metadata_count' => count($this->metadata)
        ];
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'error' => $this->getErrorMessage(),
            'metadata' => $this->metadata,
            'execution_time' => $this->executionTime,
            'summary' => $this->getSummary()
        ];
    }
}
