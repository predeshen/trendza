<?php
namespace Trendza\Suppliers;

final class RemoteFeedSupplier implements SupplierInterface {
    public function __construct(private string $code, private string $url, private FeedParserInterface $parser, private int $timeout = 30) {}
    public function getCode(): string { return sanitize_key($this->code); }
    public function fetch(): iterable {
        if ($this->url === '' || !wp_http_validate_url($this->url)) throw new \InvalidArgumentException('Invalid supplier feed URL.');
        $response = wp_safe_remote_get($this->url, ['timeout'=>$this->timeout,'redirection'=>3,'limit_response_size'=>20 * 1024 * 1024]);
        if (is_wp_error($response)) throw new \RuntimeException($response->get_error_message());
        $status = (int) wp_remote_retrieve_response_code($response);
        if ($status < 200 || $status >= 300) throw new \RuntimeException('Supplier feed returned HTTP ' . $status . '.');
        yield from $this->parser->parse((string) wp_remote_retrieve_body($response));
    }
}
