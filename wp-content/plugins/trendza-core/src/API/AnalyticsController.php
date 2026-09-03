<?php
namespace Trendza\API;

use Trendza\Analytics\EventStore;

final class AnalyticsController {
    public static function register(): void { add_action('rest_api_init', [self::class, 'routes']); }

    public static function routes(): void {
        register_rest_route('trendza/v1', '/events', [
            'methods' => 'POST', 'callback' => [self::class, 'record'],
            'permission_callback' => '__return_true',
            'args' => [
                'event' => ['required'=>true, 'sanitize_callback'=>'sanitize_key'],
                'product_id' => ['default'=>0, 'sanitize_callback'=>'absint'],
                'query' => ['default'=>'', 'sanitize_callback'=>'sanitize_text_field'],
            ],
        ]);
    }

    public static function record(\WP_REST_Request $request) {
        $event = sanitize_key((string) $request->get_param('event'));
        $productId = absint($request->get_param('product_id'));
        if (!in_array($event, ['view','search','add_to_cart','begin_checkout'], true)) return new \WP_Error('trendza_invalid_event','Unsupported event',['status'=>400]);
        if ($productId && (!get_post($productId) || get_post_type($productId) !== 'product')) return new \WP_Error('trendza_invalid_product','Invalid product',['status'=>400]);
        $metadata = [];
        if ($event === 'search' && $request->get_param('query')) $metadata['query'] = sanitize_text_field((string) $request->get_param('query'));
        EventStore::record($productId, $event, self::clientKey($request), $metadata);
        return rest_ensure_response(['recorded'=>true]);
    }

    private static function clientKey(\WP_REST_Request $request): string {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        $agent = $request->get_header('user-agent');
        return hash('sha256', $ip . '|' . (string) $agent . '|' . wp_salt('auth'));
    }
}
