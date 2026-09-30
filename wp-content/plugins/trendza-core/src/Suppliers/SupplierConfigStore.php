<?php
namespace Trendza\Suppliers;

final class SupplierConfigStore {
    public const OPTION = 'trendza_supplier_configs';

    public static function all(): array {
        if (!function_exists('get_option')) return [];
        $stored = get_option(self::OPTION, []);
        return is_array($stored) ? $stored : [];
    }

    public static function get(string $code): ?SupplierConfig {
        $normalized = self::normalize($code);
        $stored = self::all();
        if (!isset($stored[$normalized]) || !is_array($stored[$normalized])) return null;
        $data = $stored[$normalized];
        try {
            return new SupplierConfig(
                $normalized,
                (float) ($data['margin_percent'] ?? 25),
                (float) ($data['minimum_feed_retention'] ?? 0.50),
                (int) ($data['max_products'] ?? 300),
                !empty($data['update_price']),
                !empty($data['update_stock']),
                !empty($data['allow_empty_feed'])
            );
        } catch (\Throwable) { return null; }
    }

    public static function save(SupplierConfig $config): bool {
        if (!function_exists('update_option')) return false;
        $all = self::all();
        $all[$config->code] = [
            'margin_percent' => $config->marginPercent,
            'minimum_feed_retention' => $config->minimumFeedRetention,
            'max_products' => $config->maxProducts,
            'update_price' => $config->updatePrice,
            'update_stock' => $config->updateStock,
            'allow_empty_feed' => $config->allowEmptyFeed,
        ];
        return (bool) update_option(self::OPTION, $all, false);
    }

    private static function normalize(string $code): string {
        if (function_exists('sanitize_key')) return sanitize_key($code);
        return strtolower(trim(preg_replace('/[^a-z0-9_-]+/i', '-', $code), '-'));
    }
}
