<?php declare(strict_types=1);
namespace FintsOfx;

use Fhp\Model\Account;
use Fhp\Model\StatementOfAccount\StatementOfAccount;
use Fhp\Model\StatementOfAccount\Transaction;

class OFXWriter extends \XMLWriter
{
    public function __construct($file)
    {
        $this->openURI($file);
        $this->setIndent(true);
        $this->setIndentString('  ');
    }

    public function startDocument($version = '1.0', $encoding = 'UTF-8', $standalone = null)
    {
        parent::startDocument($version, $encoding, $standalone);
        $this->writePi('OFX', 'OFXHEADER="200" VERSION="220" SECURITY="NONE" OLDFILEUID="NONE" NEWFILEUID="NONE"');
        $this->startElement('OFX');
    }

    public function endDocument()
    {
        $this->endElement(); // OFX
        parent::endDocument();
    }

    public function writeDateTimeElement($name, \DateTime $date)
    {
        $this->writeElement($name, $date->format('YmdHis'));
    }

    public function writeSignOnMessageSet()
    {
        $this->startElement('SIGNONMSGSRSV1');
        $this->startElement('SONRS');

        $this->startElement('STATUS');
        $this->writeElement('CODE', '0');
        $this->writeElement('SEVERITY', 'INFO');
        $this->endElement(); // STATUS
        $this->writeDateTimeElement('DTSERVER', new \DateTime('now'));
        $this->writeElement('LANGUAGE', 'GER');

        $this->endElement(); // SONRS
        $this->endElement(); // SIGNONMSGSRSV1
    }

    public function startBankingMessageSet()
    {
        $this->startElement('BANKMSGSRSV1');
    }

    public function endBankingMessageSet()
    {
        $this->endElement(); // BANKMSGSRSV1
    }

    public function startStatementTransactionWrapper()
    {
        $this->startElement('STMTTRNRS');

        $this->writeElement('TRNUID', '0');
        $this->startElement('STATUS');
        $this->writeElement('CODE', '0');
        $this->writeElement('SEVERITY', 'INFO');
        $this->endElement(); // STATUS
    }

    public function endStatementTransactionWrapper()
    {
        $this->endElement(); // STMTTRNRS
    }

    public function startStatementResponse(Account $account)
    {
        $this->startElement('STMTRS');
        $this->writeElement('CURDEF', $account->getCurrency());

        $this->startElement('BANKACCTFROM');
        $this->writeElement('BANKID', (string)$account->getBankCode());
        $this->writeElement('ACCTID', (string)$account->getAccountNumber());
        $this->writeElement('ACCTTYPE', 'CHECKING');
        $this->endElement(); // BANKACCTFROM
    }

    public function endStatementResponse()
    {
        $this->endElement(); // STMTRS
    }

    public function startStatementTransactionAggregate(StatementOfAccount $statementOfAccount)
    {
        $this->startElement('BANKTRANLIST');
        $statements = $statementOfAccount->getStatements();
        $statementCount = count($statements);

        if ($statementCount > 0) {
            $this->writeDateTimeElement('DTSTART', $statements[0]->getDate());
            $this->writeDateTimeElement('DTEND', $statements[$statementCount - 1]->getDate());
        }
    }

    public function endStatementTransactionAggregate()
    {
        $this->endElement(); // BANKTRANLIST
    }

    public function writeStatementTransaction(Transaction $transaction)
    {
        $this->startElement('STMTTRN');

        $this->writeElement('TRNTYPE', strtoupper($transaction->getCreditDebit()));
        $this->writeDateTimeElement('DTPOSTED', $transaction->getBookingDate());
        $this->writeElement(
            'TRNAMT',
            ($transaction->getCreditDebit() == Transaction::CD_DEBIT ? '-' : '') . $transaction->getAmount()
        );
        $this->writeElement('FITID', md5($transaction->getDescription1()));
        $this->writeElement('NAME', $transaction->getName());

        $this->endElement(); // STMTTRN
    }
}
