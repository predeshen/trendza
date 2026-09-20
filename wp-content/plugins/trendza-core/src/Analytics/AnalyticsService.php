<?php
namespace Trendza\Analytics;

use Trendza\Products\ProductMeta;
use Trendza\Trend\TrendScoreCalculator;
use Trendza\Trend\TrendSignal;
use Trendza\Trend\TrendStatusResolver;

final class AnalyticsService {
    public static function register(): void {
        add_action('woocommerce_add_to_cart', [self::class, 'onAddToCart'], 10, 6);
        add_action('woocommerce_checkout_create_order', [self::class, 'onCheckoutCreateOrder'], 10, 2);
        add_action('woocommerce_order_status_processing', [self::class, 'onOrder'], 10, 1);
        add_action('woocommerce_order_status_completed', [self::class, 'onOrder'], 10, 1);
        add_action('trendza_recalculate_trends', [self::class, 'recalculate'], 20);
        add_action('trendza_prune_events', [self::class, 'prune']);
    }

    public static function onAddToCart($cartItemKey, $productId, $quantity): void {
        EventStore::record((int) $productId, 'add_to_cart', self::sessionKey(), ['quantity' => max(1, (int) $quantity)]);
    }

    public static function onCheckoutCreateOrder($order, $data): void {
        if (!$order || !is_object($order) || !method_exists($order, 'update_meta_data')) {
            return;
        }

        $order->update_meta_data('_trendza_session_hash', hash('sha256', self::sessionKey()));
    }

    public static function onOrder(int $orderId): void {
        $order = function_exists('wc_get_order') ? wc_get_order($orderId) : false;
        if (!$order || $order->get_meta('_trendza_purchase_recorded')) return;

        $sessionHash = (string) $order->get_meta('_trendza_session_hash');
        foreach ($order->get_items() as $item) {
            $productId = (int) $item->get_product_id();
            if ($productId <= 0) continue;
            EventStore::record($productId, 'purchase', $sessionHash ?: self::sessionKey(), [
                'quantity' => max(1, (int) $item->get_quantity()),
                'order_hash' => hash('sha256', (string) $orderId),
            ]);
        }
        $order->update_meta_data('_trendza_purchase_recorded', 'yes');
        $order->save_meta_data();
    }

    public static function recalculate(): void {
        $ids = get_posts(['post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids']);
        $calculator = new TrendScoreCalculator();
        $resolver = new TrendStatusResolver();
        foreach ($ids as $id) {
            $id = (int) $id;
            $signals = self::signals($id);
            $score = $calculator->calculate($signals);
            $previous = (float) ProductMeta::get($id, ProductMeta::TREND_SCORE, 0);
            $momentum = $score - $previous;
            update_post_meta($id, ProductMeta::TREND_SCORE, $score);
            update_post_meta($id, ProductMeta::TREND_STATUS, $resolver->resolve($score, $momentum));
            update_post_meta($id, ProductMeta::TREND_SIGNALS, array_map(static fn (TrendSignal $s) => [
                'name' => $s->name,
                'value' => $s->value,
                'weight' => $s->weight,
            ], $signals));
            update_post_meta($id, ProductMeta::TREND_UPDATED_AT, current_time('mysql', true));
        }
    }

    private static function signals(int $productId): array {
        $views24 = EventStore::count($productId, 'view', 24);
        $views7 = EventStore::count($productId, 'view', 168);
        $cart24 = EventStore::count($productId, 'add_to_cart', 24);
        $cart7 = EventStore::count($productId, 'add_to_cart', 168);
        $sales24 = EventStore::count($productId, 'purchase', 24);
        $sales7 = EventStore::count($productId, 'purchase', 168);
        $search24 = EventStore::count($productId, 'search', 24);
        $search7 = EventStore::count($productId, 'search', 168);

        return [
            new TrendSignal('sales_velocity', self::velocity($sales24, $sales7), 30),
            new TrendSignal('view_velocity', self::velocity($views24, $views7), 10),
            new TrendSignal('add_to_cart_velocity', self::velocity($cart24, $cart7), 15),
            new TrendSignal('search_growth', self::velocity($search24, $search7), 15),
            new TrendSignal('review_quality', self::reviewScore($productId), 5),
            new TrendSignal('availability', self::availabilityScore($productId), 5),
        ];
    }

    /**
     * Convert a 24-hour event count into a bounded momentum/activity signal.
     *
     * A raw ratio makes a single new event look like a 7x growth spike when
     * the previous six days are empty. This dampens sparse catalogue data by
     * blending growth with an activity-confidence factor.
     */
    private static function velocity(int $short, int $long): float {
        $short = max(0, $short);
        $long = max($short, $long);

        if ($long <= 0) return 0.0;

        $previousSixDays = max(0, $long - $short);
        $baselineDaily = $previousSixDays / 6.0;

        if ($baselineDaily <= 0) {
            $growth = $short > 0 ? 100.0 : 0.0;
        } else {
            $ratio = $short / $baselineDaily;
            $growth = 50.0 + (($ratio - 1.0) * 25.0);
            $growth = min(100.0, max(0.0, $growth));
        }

        $confidence = min(1.0, log1p($long) / log1p(50));
        $activity = min(100.0, log1p($long) * 18.0);

        return round(($growth * $confidence) + (50.0 * (1.0 - $confidence)) * 0.45 + ($activity * 0.55 * $confidence), 2);
    }

    private static function reviewScore(int $id): float {
        $product = function_exists('wc_get_product') ? wc_get_product($id) : false;
        if (!$product) return 0;
        return min(100, max(0, ((float) $product->get_average_rating() / 5) * 70 + min(30, (int) $product->get_review_count())));
    }

    private static function availabilityScore(int $id): float {
        $product = function_exists('wc_get_product') ? wc_get_product($id) : false;
        return $product && $product->is_in_stock() ? 100 : 0;
    }

    private static function sessionKey(): string {
        if (isset($_COOKIE['trendza_session']) && is_string($_COOKIE['trendza_session'])) {
            return sanitize_text_field(wp_unslash($_COOKIE['trendza_session']));
        }

        $session = wp_generate_uuid4();
        if (function_exists('wc_setcookie')) {
            wc_setcookie('trendza_session', $session, time() + (30 * DAY_IN_SECONDS), false);
        } elseif (!headers_sent()) {
            setcookie('trendza_session', $session, [
                'expires' => time() + (30 * DAY_IN_SECONDS),
                'path' => COOKIEPATH ?: '/',
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        return $session;
    }

    public static function prune(): void { EventStore::prune(90); }
}
