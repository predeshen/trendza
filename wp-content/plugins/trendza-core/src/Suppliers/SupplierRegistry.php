<?php
namespace Trendza\Suppliers;

final class SupplierRegistry {
    /** @var SupplierInterface[] */
    private array $suppliers = [];

    public function register(SupplierInterface $supplier): void { $this->suppliers[$supplier->getCode()] = $supplier; }
    public function all(): array { return array_values($this->suppliers); }
    public function get(string $code): ?SupplierInterface { return $this->suppliers[$code] ?? null; }
}
