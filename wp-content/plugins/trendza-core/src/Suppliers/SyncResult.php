<?php
namespace Trendza\Suppliers;

final class SyncResult {
    public int $seen = 0;
    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;
    public array $errors = [];

    public function error(string $externalId, string $message): void {
        $this->errors[] = ['external_id' => $externalId, 'message' => $message];
    }
}
