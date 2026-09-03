<?php
namespace Trendza\API;

use Trendza\Products\ProductData;
use Trendza\Products\ProductRepository;

final class RestController {
    public static function register(): void {
        add_action('rest_api_init', [self::class, 'routes']);
    }

    public static function routes(): void {
        register_rest_route('trendza/v1', '/products', ['methods'=>'GET','callback'=>[self::class,'products'],'permission_callback'=>'__return_true']);
        register_rest_route('trendza/v1', '/products/(?P<id>\d+)', ['methods'=>'GET','callback'=>[self::class,'product'],'permission_callback'=>'__return_true']);
        foreach (['trending','rising','best-value','quality','recent'] as $mode) register_rest_route('trendza/v1','/discover/'.$mode,['methods'=>'GET','callback'=>function($request) use ($mode){return self::discover($request,$mode);},'permission_callback'=>'__return_true']);
    }

    private static function limit($request): int { return max(1, min(50, (int) $request->get_param('limit') ?: 8)); }
    public static function products($request) { return rest_ensure_response((new ProductRepository())->discover(self::limit($request), 'trending')); }
    public static function discover($request, string $mode) { return rest_ensure_response((new ProductRepository())->discover(self::limit($request), $mode)); }
    public static function product($request) {
        $product = function_exists('wc_get_product') ? wc_get_product((int) $request['id']) : false;
        if (!$product || $product->get_status() !== 'publish') return new \WP_Error('trendza_product_not_found','Product not found',['status'=>404]);
        return rest_ensure_response(ProductData::fromProduct($product));
    }
}
