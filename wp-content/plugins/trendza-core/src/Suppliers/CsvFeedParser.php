<?php
namespace Trendza\Suppliers;

final class CsvFeedParser implements FeedParserInterface {
    public function parse(string $contents): iterable {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents); rewind($stream);
        $headers = fgetcsv($stream);
        if (!$headers) return;
        $headers = array_map(static fn($h) => strtolower(trim((string) $h)), $headers);
        while (($row = fgetcsv($stream)) !== false) {
            if (count(array_filter($row, static fn($v) => trim((string)$v) !== '')) === 0) continue;
            $row = array_pad($row, count($headers), '');
            if (count($row) > count($headers)) $row = array_slice($row, 0, count($headers));
            $data = array_combine($headers, $row);
            if (!$data) continue;
            yield new SupplierProduct(
                (string) ($data['external_id'] ?? $data['id'] ?? ''),
                (string) ($data['name'] ?? $data['title'] ?? ''),
                (float) ($data['cost'] ?? 0),
                (float) ($data['rrp'] ?? 0),
                filter_var($data['in_stock'] ?? true, FILTER_VALIDATE_BOOLEAN),
                (string) ($data['sku'] ?? ''),
                (string) ($data['brand'] ?? ''),
                (string) ($data['description'] ?? ''),
                (string) ($data['image'] ?? ''),
                array_filter(array_map('trim', explode('|', (string) ($data['categories'] ?? '')))),
                self::attributes($data),
                (float) ($data['sale_price'] ?? $data['saleprice'] ?? 0),
                self::variants($data['variants'] ?? ''),
            );
        }
        fclose($stream);
    }

    private static function variants(string $value): array {
        if (trim($value) === '') return [];
        $decoded = json_decode($value, true);
        if (!is_array($decoded)) return [];
        return array_values(array_filter($decoded, static fn ($variant): bool => is_array($variant)));
    }

    private static function attributes(array $data): array {
        $attributes = [];
        foreach ($data as $key => $value) {
            $key = trim((string) $key);
            if (!str_starts_with($key, 'attribute_')) continue;
            $name = trim(substr($key, 10));
            $value = trim((string) $value);
            if ($name !== '' && $value !== '') $attributes[$name] = $value;
        }
        return $attributes;
    }
}
