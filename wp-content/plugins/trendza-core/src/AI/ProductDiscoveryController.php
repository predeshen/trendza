<?php
namespace Trendza\AI;

use Trendza\Products\ProductData;

final class ProductDiscoveryController {
    public static function register(): void { add_action('rest_api_init', [self::class, 'routes']); }

    public static function routes(): void {
        register_rest_route('trendza/v1', '/ai/products', [
            'methods'=>'GET', 'callback'=>[self::class,'query'], 'permission_callback'=>'__return_true',
            'args'=>[
                'q'=>['sanitize_callback'=>'sanitize_text_field'],
                'category'=>['sanitize_callback'=>'sanitize_title'],
                'max_price'=>['sanitize_callback'=>'floatval'],
                'min_price'=>['sanitize_callback'=>'floatval'],
                'in_stock'=>['sanitize_callback'=>'rest_sanitize_boolean'],
                'trend_status'=>['sanitize_callback'=>'sanitize_key'],
                'min_quality'=>['sanitize_callback'=>'floatval'],
                'limit'=>['sanitize_callback'=>'absint'],
            ],
        ]);

        register_rest_route('trendza/v1', '/ai/compare', [
            'methods'=>'GET', 'callback'=>[self::class,'compare'], 'permission_callback'=>'__return_true',
            'args'=>[
                'ids'=>['required'=>true,'sanitize_callback'=>'sanitize_text_field'],
            ],
        ]);

        register_rest_route('trendza/v1', '/ai/recommendations/(?P<id>\d+)', [
            'methods'=>'GET', 'callback'=>[self::class,'recommendations'], 'permission_callback'=>'__return_true',
            'args'=>[
                'id'=>['required'=>true,'sanitize_callback'=>'absint'],
                'limit'=>['sanitize_callback'=>'absint'],
            ],
        ]);
    }

    public static function query(\WP_REST_Request $request) {
        if (!function_exists('wc_get_products')) return new \WP_Error('trendza_woocommerce_required','WooCommerce is required',['status'=>503]);
        $limit = max(1, min(30, (int) ($request->get_param('limit') ?: 10)));
        $args = ['status'=>'publish','limit'=>$limit,'return'=>'objects','orderby'=>'meta_value_num','meta_key'=>'_trendza_trend_score','order'=>'DESC'];
        if ($request->get_param('q')) $args['s'] = sanitize_text_field((string) $request->get_param('q'));
        if ($request->get_param('category')) $args['category'] = [sanitize_title((string) $request->get_param('category'))];
        if ($request->get_param('max_price') !== null && $request->get_param('max_price') !== '') $args['max_price'] = max(0, (float) $request->get_param('max_price'));
        if ($request->get_param('min_price') !== null && $request->get_param('min_price') !== '') $args['min_price'] = max(0, (float) $request->get_param('min_price'));
        if ($request->get_param('in_stock') !== null) $args['stock_status'] = rest_sanitize_boolean($request->get_param('in_stock')) ? 'instock' : 'outofstock';
        $metaQuery = [];
        if ($request->get_param('trend_status')) $metaQuery[] = ['key'=>'_trendza_trend_status','value'=>sanitize_key((string)$request->get_param('trend_status'))];
        if ($request->get_param('min_quality') !== null && $request->get_param('min_quality') !== '') $metaQuery[] = ['key'=>'_trendza_quality_score','value'=>max(0,(float)$request->get_param('min_quality')),'type'=>'NUMERIC','compare'=>'>='];
        if ($metaQuery) $args['meta_query'] = $metaQuery;
        $products = wc_get_products($args);
        return rest_ensure_response(['query'=>['q'=>(string)$request->get_param('q'),'category'=>(string)$request->get_param('category'),'max_price'=>$request->get_param('max_price'),'min_price'=>$request->get_param('min_price'),'in_stock'=>$request->get_param('in_stock'),'trend_status'=>$request->get_param('trend_status')],'results'=>array_map(static function($product){$data=ProductData::fromProduct($product);$data['why_recommended']=self::reason($data);return $data;},$products)]);
    }

    public static function compare(\WP_REST_Request $request) {
        if (!function_exists('wc_get_product')) return new \WP_Error('trendza_woocommerce_required','WooCommerce is required',['status'=>503]);
        $ids = array_values(array_unique(array_filter(array_map('absint', preg_split('/\s*,\s*/', (string) $request->get_param('ids'))))));
        if (count($ids) < 2 || count($ids) > 4) return new \WP_Error('trendza_invalid_comparison','Provide between 2 and 4 product IDs.',['status'=>400]);
        $products = [];
        foreach ($ids as $id) {
            $product = wc_get_product($id);
            if (!$product || $product->get_status() !== 'publish') continue;
            $products[] = ProductData::fromProduct($product);
        }
        if (count($products) < 2) return new \WP_Error('trendza_products_not_found','At least two published products are required.',['status'=>404]);
        return rest_ensure_response(['count'=>count($products),'products'=>$products,'comparison'=>self::comparison($products)]);
    }

    public static function recommendations(\WP_REST_Request $request) {
        if (!function_exists('wc_get_product') || !function_exists('wc_get_products')) return new \WP_Error('trendza_woocommerce_required','WooCommerce is required',['status'=>503]);
        $reference = wc_get_product((int) $request['id']);
        if (!$reference || $reference->get_status() !== 'publish') return new \WP_Error('trendza_product_not_found','Product not found.',['status'=>404]);
        $referenceData = ProductData::fromProduct($reference);
        $limit = max(1, min(12, (int) ($request->get_param('limit') ?: 6)));
        $candidates = wc_get_products(['status'=>'publish','limit'=>100,'return'=>'objects','orderby'=>'meta_value_num','meta_key'=>'_trendza_trend_score','order'=>'DESC']);
        $candidateData = array_map(static fn($product): array => ProductData::fromProduct($product), $candidates);
        return rest_ensure_response(['product'=>$referenceData,'recommendations'=>ProductRecommender::rank($referenceData,$candidateData,$limit)]);
    }

    private static function comparison(array $products): array {
        $dimensions = ['price','trend_score','trend_status','quality_score','value_score','stock_status','brand','categories'];
        $result = [];
        foreach ($dimensions as $dimension) {
            $result[$dimension] = array_map(static function(array $product) use ($dimension) { return ['id'=>$product['id'],'value'=>$product[$dimension] ?? null]; }, $products);
        }
        return $result;
    }

    private static function reason(array $data): string {
        $parts=[];
        if (($data['trend_score']??0)>=75) $parts[]='currently trending'; elseif (($data['trend_status']??'')==='rising') $parts[]='showing rising momentum';
        if (($data['quality_score']??0)>=80) $parts[]='strong product quality signals';
        if (!empty($data['in_stock'])) $parts[]='currently in stock';
        return $parts ? ucfirst(implode(', ',$parts)).'.' : 'Matches the requested product criteria.';
    }
}
