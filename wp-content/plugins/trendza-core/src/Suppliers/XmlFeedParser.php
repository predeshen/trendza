<?php
namespace Trendza\Suppliers;

final class XmlFeedParser implements FeedParserInterface {
    public function parse(string $contents): iterable {
        if (!function_exists('simplexml_load_string')) throw new \RuntimeException('SimpleXML is required for XML supplier feeds.');
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($contents, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        if (!$xml) throw new \InvalidArgumentException('Invalid supplier XML feed.');
        foreach ($xml->product as $node) {
            $categories = isset($node->categories) ? array_filter(array_map('trim', explode('|', (string) $node->categories))) : [];
            yield new SupplierProduct(
                (string) ($node->external_id ?: $node->id),
                (string) ($node->name ?: $node->title),
                (float) $node->cost,
                (float) $node->rrp,
                filter_var((string) ($node->in_stock ?? '1'), FILTER_VALIDATE_BOOLEAN),
                (string) $node->sku,
                (string) $node->brand,
                (string) $node->description,
                (string) $node->image,
                $categories,
                self::attributes($node),
                (float) ($node->sale_price ?: $node->saleprice ?: 0),
            );
        }
    }

    private static function attributes(\SimpleXMLElement $node): array {
        $attributes = [];
        if (!isset($node->attributes)) return $attributes;
        foreach ($node->attributes->children() as $attribute) {
            $name = trim((string) ($attribute['name'] ?? $attribute->name ?? ''));
            $value = trim((string) ($attribute['value'] ?? $attribute));
            if ($name !== '' && $value !== '') $attributes[$name] = $value;
        }
        return $attributes;
    }
}
