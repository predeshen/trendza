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
            $data = array_combine($headers, array_pad($row, count($headers), ''));
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
                [],
            );
        }
        fclose($stream);
    }
}
