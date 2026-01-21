<?php

declare(strict_types=1);

namespace App\Services\DNS;

use App\Models\DnsAccount;
use App\Models\Domain;

interface DnsServiceInterface
{
    /**
     * Initialize service with account credentials
     */
    public function __construct(DnsAccount $account);

    /**
     * Add domain to DNS provider
     * 
     * @return array{zone_id: string, nameservers: array}
     */
    public function addDomain(string $domain): array;

    /**
     * Remove domain from DNS provider
     */
    public function removeDomain(string $zoneId): bool;

    /**
     * Create DNS record
     * 
     * @return string Record ID
     */
    public function createRecord(string $zoneId, string $type, string $name, string $content, int $ttl = 300, bool $proxied = true): string;

    /**
     * Update DNS record
     */
    public function updateRecord(string $zoneId, string $recordId, string $type, string $name, string $content, int $ttl = 300, bool $proxied = true): bool;

    /**
     * Delete DNS record
     */
    public function deleteRecord(string $zoneId, string $recordId): bool;

    /**
     * Get all records for a zone
     */
    public function getRecords(string $zoneId): array;

    /**
     * Set up SSL for domain
     */
    public function setupSsl(string $zoneId): bool;

    /**
     * Check SSL status
     */
    public function getSslStatus(string $zoneId): string;

    /**
     * Verify API connection
     */
    public function verifyConnection(): bool;

    /**
     * Get account info
     */
    public function getAccountInfo(): array;
}
