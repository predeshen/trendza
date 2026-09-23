<?php
namespace Trendza\Suppliers;

final class SupplierConfigRegistry {
    public static function resolve(string $code): SupplierConfig {
        $normalized = function_exists('sanitize_key')
            ? sanitize_key($code)
            : strtolower(trim(preg_replace('/[^a-z0-9_-]+/i', '-', $code), '-'));

        $config = new SupplierConfig($normalized);

        if (function_exists('apply_filters')) {
            $filtered = apply_filters('trendza_supplier_config', $config, $code);
            if ($filtered instanceof SupplierConfig) return $filtered;
        }

        return $config;
    }
}
