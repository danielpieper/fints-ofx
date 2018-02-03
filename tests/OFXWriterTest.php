<?php declare(strict_types=1);

namespace FintsOfx;

use danielpieper\FintsOfx\OFXWriter;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;
use PHPUnit\Framework\TestCase;

class OFXWriterTest extends TestCase
{
    public function test_it_can_create_documents(): void
    {
        $currencies = new ISOCurrencies();
        $moneyFormatter = new DecimalMoneyFormatter($currencies);
        $filename = tempnam(sys_get_temp_dir(), 'fintsofx');

        $ofxWriter = new OFXWriter($filename, $moneyFormatter);
        $ofxWriter->startDocument();
        $ofxWriter->endDocument();

        $actual = file_get_contents($filename);
        $expected = file_get_contents(__DIR__ . '/../fixtures/empty_document.ofx');
        $this->assertSame($expected, $actual);
    }
}
