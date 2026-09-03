<?php
use PHPUnit\Framework\TestCase;
use Trendza\AI\ProductRecommender;

final class ProductRecommenderTest extends TestCase {
    private function product(int $id, array $overrides = []): array {
        return array_merge([
            'id'=>$id,'price'=>500.0,'in_stock'=>true,'categories'=>['Tech Gadgets'],
            'quality_score'=>70.0,'trend_score'=>50.0,'value_score'=>70.0,
        ], $overrides);
    }

    public function testSameCategoryAndPriceRanksHigher(): void {
        $reference=$this->product(1);
        $similar=$this->product(2,['quality_score'=>90,'trend_score'=>80]);
        $different=$this->product(3,['price'=>2500,'categories'=>['Beauty']]);
        $result=ProductRecommender::rank($reference,[$different,$similar],2);
        $this->assertSame(2,$result[0]['id']);
        $this->assertStringContainsString('similar category',$result[0]['why_recommended']);
    }

    public function testOutOfStockCandidatesAreExcluded(): void {
        $reference=$this->product(1);
        $result=ProductRecommender::rank($reference,[$this->product(2,['in_stock'=>false])],6);
        $this->assertCount(0,$result);
    }
}
