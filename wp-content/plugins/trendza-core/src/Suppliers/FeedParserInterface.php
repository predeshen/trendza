<?php
namespace Trendza\Suppliers;

interface FeedParserInterface {
    /** @return iterable<SupplierProduct> */
    public function parse(string $contents): iterable;
}
