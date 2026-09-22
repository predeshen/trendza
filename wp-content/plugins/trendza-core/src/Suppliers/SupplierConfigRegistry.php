<?php
namespace Trendza\Suppliers;

final class SupplierConfigRegistry {
    public static function resolve(string $code): SupplierConfig {
        $config = new SupplierConfig(sanitize_key($code));

        if (function_exists('apply_filters')) {
            $filtered = apply_filters('trendza_supplier_config', $config, $code);
            if ($filtered instanceof SupplierConfig) return $filtered;
        }

        return $config;
    }
}
