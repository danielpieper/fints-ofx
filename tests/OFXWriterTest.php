<?php declare(strict_types=1);

namespace FintsOfx;

use danielpieper\FintsOfx\OFXWriter;
use Faker\Factory;
use Faker\Generator;
use Fhp\Model\Account;
use Fhp\Model\StatementOfAccount\Statement;
use Fhp\Model\StatementOfAccount\StatementOfAccount;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;
use Money\MoneyFormatter;
use PHPUnit\Framework\TestCase;

class OFXWriterTest extends TestCase
{
    /** @var string */
    private $filename;

    /** @var MoneyFormatter */
    private $moneyFormatter;

    /** @var OFXWriter */
    private $ofxWriter;

    /** @var Generator */
    private $faker;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {

        $currencies = new ISOCurrencies();
        $this->moneyFormatter = new DecimalMoneyFormatter($currencies);
        $this->filename = tempnam(sys_get_temp_dir(), 'fintsofx');

        $this->ofxWriter = new OFXWriter($this->filename, $this->moneyFormatter);

        $this->faker = Factory::create('de_DE');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset($this->ofxWriter);
        unlink($this->filename);

        parent::tearDown();
    }

    public function testCreateDocument(): void
    {
        $this->ofxWriter->startDocument();
        $this->ofxWriter->endDocument();

        $actual = file_get_contents($this->filename);
        $expected = file_get_contents(__DIR__ . '/fixtures/ofx/empty_document.txt');
        $this->assertSame($expected, $actual);
    }

    public function testWriteDateTimeElement(): void
    {
        $date = new \DateTime('2018-01-01 13:37:00', new \DateTimeZone('UTC'));
        $this->ofxWriter->startDocument();
        $this->ofxWriter->writeDateTimeElement('test', $date);
        $this->ofxWriter->endDocument();

        $actual = file_get_contents($this->filename);
        $expected = file_get_contents(__DIR__ . '/fixtures/ofx/date_time_element.txt');
        $this->assertSame($expected, $actual);
    }

    public function testWriteSignOnMessageSet(): void
    {
        $date = new \DateTime('2018-01-01 13:37:00', new \DateTimeZone('UTC'));
        $this->ofxWriter->startDocument();
        $this->ofxWriter->writeSignOnMessageSet($date);
        $this->ofxWriter->endDocument();

        $actual = file_get_contents($this->filename);
        $expected = file_get_contents(__DIR__ . '/fixtures/ofx/sign_on_message_set.txt');
        $this->assertSame($expected, $actual);
    }

    public function testWriteBankingMessageSet(): void
    {
        $this->ofxWriter->startDocument();
        $this->ofxWriter->startBankingMessageSet();
        $this->ofxWriter->endBankingMessageSet();
        $this->ofxWriter->endDocument();

        $actual = file_get_contents($this->filename);
        $expected = file_get_contents(__DIR__ . '/fixtures/ofx/banking_message_set.txt');
        $this->assertSame($expected, $actual);
    }

    public function testWriteStatementTransactionWrapper(): void
    {
        $this->ofxWriter->startDocument();
        $this->ofxWriter->startStatementTransactionWrapper();
        $this->ofxWriter->endStatementTransactionWrapper();
        $this->ofxWriter->endDocument();

        $actual = file_get_contents($this->filename);
        $expected = file_get_contents(__DIR__ . '/fixtures/ofx/statement_transaction_wrapper.txt');
        $this->assertSame($expected, $actual);
    }

    public function testWriteStatementResponse(): void
    {
        $accountModel = new Account();
        $accountModel->setCurrency($this->faker->currencyCode);
        $accountModel->setAccountNumber($this->faker->bankAccountNumber);
        $accountModel->setBankCode($this->faker->randomNumber(5));

        $this->ofxWriter->startDocument();
        $this->ofxWriter->startStatemetResponse($accountModel);
        $this->ofxWriter->endStatementResponse();
        $this->ofxWriter->endDocument();

        $actual = file_get_contents($this->filename);
        $expected = file_get_contents(__DIR__ . '/fixtures/ofx/statement_response.txt');
        $expected = strtr($expected, [
            '{currency}' => $accountModel->getCurrency(),
            '{bankcode}' => $accountModel->getBankCode(),
            '{account_number}' => $accountModel->getAccountNumber(),
        ]);
        $this->assertSame($expected, $actual);
    }

    public function testStatementTransactionAggregate(): void
    {
        /** @var Statement $statement */
        $currentStatement = null;
        $statements = [];
        $iterations = $this->faker->numberBetween(2, 9);
        for ($i = 0; $i < $iterations; $i++) {
            $maxDate = ($currentStatement ? $currentStatement->getDate() : 'now');
            $currentStatement = new Statement();
            $date = $this->faker->dateTimeThisDecade($maxDate);
            $currentStatement->setDate($date);
            $statements[] = $currentStatement;
        }

        $template = file_get_contents(__DIR__ . '/fixtures/ofx/statement_transaction_aggregate.txt');
        $expected = strtr($template, [
            '{dtstart}' => $statements[0]->getDate()->format('YmdHis'),
            '{dtend}' => end($statements)->getDate()->format('YmdHis'),
        ]);

        $statementOfAccountModel = new StatementOfAccount();
        $statementOfAccountModel->setStatements($statements);

        $this->ofxWriter->startDocument();
        $this->ofxWriter->startStatementTransactionAggregate($statementOfAccountModel);
        $this->ofxWriter->endStatementTransactionAggregate();
        $this->ofxWriter->endDocument();

        $actual = file_get_contents($this->filename);
        $this->assertSame($expected, $actual);
    }
}
