<?php
use PHPUnit\Framework\TestCase;
use Trendza\Suppliers\SupplierSyncAudit;

final class SupplierSyncAuditTest extends TestCase {
    public function testAuditIsSafeWithoutWordPressStorage(): void {
        self::assertSame([], SupplierSyncAudit::all());
        self::assertNull(SupplierSyncAudit::latest('supplier-a'));
        SupplierSyncAudit::record('supplier-a', 'started');
        self::assertSame([], SupplierSyncAudit::all());
    }
}
