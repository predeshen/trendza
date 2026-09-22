<?php
use PHPUnit\Framework\TestCase;
use Trendza\Suppliers\CsvFeedParser;
use Trendza\Suppliers\XmlFeedParser;

final class SupplierFeedParserTest extends TestCase {
    public function testCsvParsesAttributeColumns(): void {
        $csv = "external_id,name,cost,rrp,in_stock,attribute_colour,attribute_size\n1,Lamp,100,150,1,White,Large\n";
        $items = iterator_to_array((new CsvFeedParser())->parse($csv));

        self::assertCount(1, $items);
        self::assertSame(['colour' => 'White', 'size' => 'Large'], $items[0]->attributes);
    }

    public function testXmlParsesNamedAttributes(): void {
        $xml = '<products><product><id>1</id><name>Lamp</name><cost>100</cost><rrp>150</rrp><in_stock>1</in_stock><attributes><attribute name="Colour" value="White"/><attribute name="Size">Large</attribute></attributes></product></products>';
        $items = iterator_to_array((new XmlFeedParser())->parse($xml));

        self::assertCount(1, $items);
        self::assertSame(['Colour' => 'White', 'Size' => 'Large'], $items[0]->attributes);
    }
}
