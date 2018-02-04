<?php declare(strict_types=1);

namespace danielpieper\FintsOfx;

use Fhp\Model\Account;
use Fhp\Model\StatementOfAccount\StatementOfAccount;
use Fhp\Model\StatementOfAccount\Transaction;
use Money\Currency;
use Money\Money;
use Money\MoneyFormatter;

class OFXWriter extends \XMLWriter
{
    private $moneyFormatter;

    /**
     * {@inheritdoc}
     */
    public function __construct($file, MoneyFormatter $moneyFormatter)
    {
        $this->openURI($file);
        $this->setIndent(true);
        $this->setIndentString('  ');
        $this->moneyFormatter = $moneyFormatter;
    }

    public function startDocument($version = '1.0', $encoding = 'UTF-8', $standalone = null) : void
    {
        parent::startDocument($version, $encoding, $standalone);
        $this->writePi('OFX', 'OFXHEADER="200" VERSION="220" SECURITY="NONE" OLDFILEUID="NONE" NEWFILEUID="NONE"');
        $this->startElement('OFX');
    }

    public function endDocument() : void
    {
        $this->endElement(); // OFX
        parent::endDocument();
    }

    public function writeDateTimeElement($name, \DateTime $date) : void
    {
        $this->writeElement($name, $date->format('YmdHis'));
    }

    public function writeSignOnMessageSet(\DateTime $dtServerDateTime = null) : void
    {
        if (!$dtServerDateTime) {
            $dtServerDateTime = new \DateTime('now');
        }
        $this->startElement('SIGNONMSGSRSV1');
        $this->startElement('SONRS');

        $this->startElement('STATUS');
        $this->writeElement('CODE', '0');
        $this->writeElement('SEVERITY', 'INFO');
        $this->endElement(); // STATUS
        $this->writeDateTimeElement('DTSERVER', $dtServerDateTime);
        $this->writeElement('LANGUAGE', 'GER');

        $this->endElement(); // SONRS
        $this->endElement(); // SIGNONMSGSRSV1
    }

    public function startBankingMessageSet() : void
    {
        $this->startElement('BANKMSGSRSV1');
    }

    public function endBankingMessageSet() : void
    {
        $this->endElement(); // BANKMSGSRSV1
    }

    public function startStatementTransactionWrapper() : void
    {
        $this->startElement('STMTTRNRS');

        $this->writeElement('TRNUID', '0');
        $this->startElement('STATUS');
        $this->writeElement('CODE', '0');
        $this->writeElement('SEVERITY', 'INFO');
        $this->endElement(); // STATUS
    }

    public function endStatementTransactionWrapper() : void
    {
        $this->endElement(); // STMTTRNRS
    }

    public function startStatementResponse(Account $account) : void
    {
        $this->startElement('STMTRS');
        $this->writeElement('CURDEF', $account->getCurrency());

        $this->startElement('BANKACCTFROM');
        $this->writeElement('BANKID', (string)$account->getBankCode());
        $this->writeElement('ACCTID', (string)$account->getAccountNumber());
        $this->writeElement('ACCTTYPE', 'CHECKING');
        $this->endElement(); // BANKACCTFROM
    }

    public function endStatementResponse() : void
    {
        $this->endElement(); // STMTRS
    }

    public function startStatementTransactionAggregate(StatementOfAccount $statementOfAccount) : void
    {
        $this->startElement('BANKTRANLIST');
        $statements = $statementOfAccount->getStatements();
        $statementCount = count($statements);

        if ($statementCount > 0) {
            $this->writeDateTimeElement('DTSTART', $statements[0]->getDate());
            $this->writeDateTimeElement('DTEND', $statements[$statementCount - 1]->getDate());
        }
    }

    public function endStatementTransactionAggregate() : void
    {
        $this->endElement(); // BANKTRANLIST
    }

    public function writeStatementTransaction(Account $account, Transaction $transaction) : void
    {
        $this->startElement('STMTTRN');

        $this->writeElement('TRNTYPE', strtoupper($transaction->getCreditDebit()));
        $this->writeDateTimeElement('DTPOSTED', $transaction->getBookingDate());
        $amount = ($transaction->getCreditDebit() == Transaction::CD_DEBIT ? -100 : 100) * $transaction->getAmount();
        $money = new Money($amount, new Currency($account->getCurrency()));
        $this->writeElement(
            'TRNAMT',
            $this->moneyFormatter->format($money)
        );
        $this->writeElement('FITID', md5($transaction->getDescription1()));
        $this->writeElement('NAME', $transaction->getName());

        $this->endElement(); // STMTTRN
    }
}
