<?php
namespace Trendza\Suppliers;

final class SupplierSyncAudit {
    public const OPTION = 'trendza_supplier_sync_audit';
    private const LIMIT = 20;

    public static function record(string $supplierCode, string $status, array $data = []): void {
        if (!function_exists('get_option') || !function_exists('update_option')) return;
        $audit = get_option(self::OPTION, []);
        if (!is_array($audit)) $audit = [];
        array_unshift($audit, [
            'supplier' => $supplierCode,
            'status' => $status,
            'time' => function_exists('current_time') ? current_time('mysql', true) : gmdate('Y-m-d H:i:s'),
            'data' => $data,
        ]);
        update_option(self::OPTION, array_slice($audit, 0, self::LIMIT), false);
    }

    public static function all(): array {
        if (!function_exists('get_option')) return [];
        $audit = get_option(self::OPTION, []);
        return is_array($audit) ? $audit : [];
    }

    public static function latest(string $supplierCode): ?array {
        foreach (self::all() as $entry) {
            if (($entry['supplier'] ?? '') === $supplierCode) return $entry;
        }
        return null;
    }
}
