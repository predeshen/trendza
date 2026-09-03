<?php
namespace Trendza\Products;

final class ProductMeta {
    public const TREND_SCORE = '_trendza_trend_score';
    public const TREND_STATUS = '_trendza_trend_status';
    public const TREND_UPDATED = '_trendza_trend_updated_at';
    public const TREND_SIGNALS = '_trendza_trend_signals';
    public const QUALITY_SCORE = '_trendza_quality_score';
    public const VALUE_SCORE = '_trendza_value_score';
    public const BRAND = '_trendza_brand';
    public const MANUFACTURER = '_trendza_manufacturer';
    public const SUMMARY = '_trendza_summary';
    public const USE_CASES = '_trendza_use_cases';
    public const PROS = '_trendza_pros';
    public const CONS = '_trendza_cons';
    public const SPECS = '_trendza_specs';
    public const SHIPPING = '_trendza_shipping_info';
    public const AI_SUMMARY = '_trendza_ai_summary';
    public const EXTERNAL_ID = '_trendza_external_id';
    public const LAST_SYNC = '_trendza_last_synced_at';

    public static function get(int $productId, string $key, $default = '') {
        $value = get_post_meta($productId, $key, true);
        return $value === '' ? $default : $value;
    }
}
