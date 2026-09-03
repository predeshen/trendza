<?php
namespace Trendza\Suppliers;

interface SupplierInterface {
    public function getCode(): string;
    public function fetch(): iterable;
}
