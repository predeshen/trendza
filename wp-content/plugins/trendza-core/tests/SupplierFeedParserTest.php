<?php
use PHPUnit\Framework\TestCase;
use Trendza\Suppliers\CsvFeedParser;
use Trendza\Suppliers\XmlFeedParser;

final class SupplierFeedParserTest extends TestCase {
    public function testCsvParsesAttributeColumns(): void {
        $json = '[{"external_id":"v1","sku":"L-W","in_stock":true,"attributes":{"Colour":"White"}}]';
        $csv = "external_id,name,cost,rrp,in_stock,attribute_colour,attribute_size,variants\n";
        $csv .= '1,Lamp,100,150,1,White,Large,"' . str_replace('"', '""', $json) . '"' . "\n";
        $items = iterator_to_array((new CsvFeedParser())->parse($csv));

        self::assertCount(1, $items);
        self::assertSame(['colour' => 'White', 'size' => 'Large'], $items[0]->attributes);
        self::assertSame('v1', $items[0]->variants[0]['external_id']);
        self::assertSame('L-W', $items[0]->variants[0]['sku']);
    }

    public function testXmlParsesNamedAttributes(): void {
        $xml = '<products><product><id>1</id><name>Lamp</name><cost>100</cost><rrp>150</rrp><in_stock>1</in_stock><attributes><attribute name="Colour" value="White"/><attribute name="Size">Large</attribute></attributes><variants><variant><id>v1</id><sku>L-W</sku><in_stock>1</in_stock><attributes><attribute name="Colour" value="White"/></attributes></variant></variants></product></products>';
        $items = iterator_to_array((new XmlFeedParser())->parse($xml));

        self::assertCount(1, $items);
        self::assertSame(['Colour' => 'White', 'Size' => 'Large'], $items[0]->attributes);
        self::assertSame('v1', $items[0]->variants[0]['external_id']);
        self::assertSame('L-W', $items[0]->variants[0]['sku']);
    }
}
