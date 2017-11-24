#!/usr/bin/env php
<?php declare(strict_types=1);
require_once('vendor/autoload.php');
require_once('functions.php');

use League\CLImate\CLImate;
use Fhp\FinTs;
use Fhp\Model\StatementOfAccount\Statement;
use Fhp\Model\StatementOfAccount\Transaction;
use Lib\OFXWriter;

$climate = new CLImate();
$climate->description('Export bank account statements via fints to open finance exchange (ofx) files');
$climate->arguments->add([
    'url' => [
        'longPrefix' => 'url',
        'description' => 'Bank FinTS url',
    ],
    'port' => [
        'longPrefix' => 'port',
        'description' => 'Bank FinTS port',
        'castTo' => 'int',
    ],
    'code' => [
        'longPrefix' => 'code',
        'description' => 'Bank routing code',
    ],
    'account' => [
        'longPrefix' => 'account',
        'description' => 'Bank account number',
    ],
    'user' => [
        'longPrefix' => 'user',
        'description' => 'username or account number',
    ],
    'pin' => [
        'longPrefix' => 'pin',
        'description' => 'PIN',
    ],
    'from' => [
        'longPrefix' => 'from',
        'description' => 'From date',
        'defaultValue' => '2 weeks ago',
    ],
    'to' => [
        'longPrefix' => 'to',
        'description' => 'To date',
        'defaultValue' => 'today',
    ],
    'file' => [
        'longPrefix' => 'file',
        'description' => 'Output ofx filename',
        'defaultValue' => 'php://stdout',
    ],
    'verbose' => [
        'prefix' => 'v',
        'longPrefix' => 'verbose',
        'description' => 'Verbose output',
        'noValue' => true,
    ],
    'help' => [
        'prefix' => 'h',
        'longPrefix' => 'help',
        'description' => 'Prints a usage statement',
        'noValue' => true,
    ],
]);
$climate->arguments->parse();

// print usage
if ($climate->arguments->get('help')) {
    $climate->usage();
    exit(0);
}
$configuration = getConfiguration($climate);

$fintsClient = new FinTs(
    $configuration['url'],
    $configuration['port'],
    $configuration['code'],
    $configuration['user'],
    $configuration['pin']
);

$sepaAccount = getSepaAccount($fintsClient, $configuration['account']);
if (!$sepaAccount) {
    $climate->error('Could not find bank sepa account.');
    exit(1);
}
$account = getAccount($fintsClient, $configuration['account']);
if (!$account) {
    $climate->error('Could not find bank account.');
    exit(1);
}
if ($configuration['verbose']) {
    $padding = $climate->padding(15);
    $padding->label('Account')->result($account->getAccountNumber());
    $padding->label('Owner')->result($account->getAccountOwnerName());
    $padding->label('Description')->result($account->getAccountDescription());
    $padding->label('IBAN')->result($account->getIban());
    $padding->label('Balance')->result($fintsClient->getSaldo($sepaAccount)->getAmount());
}
$soa = $fintsClient->getStatementOfAccount(
    $sepaAccount,
    (new \DateTime($configuration['from'])),
    (new \DateTime($configuration['to']))
);
$ofxWriter = new OFXWriter($configuration['file']);
$ofxWriter->startDocument();
$ofxWriter->writeSignOnMessageSet();
$ofxWriter->startBankingMessageSet();
$ofxWriter->startStatementTransactionWrapper();
$ofxWriter->startStatementResponse($account);
$ofxWriter->startStatementTransactionAggregate($soa);
$transactionTable = [];
$numberFormatter = new \NumberFormatter('de_DE', \NumberFormatter::CURRENCY);
foreach ($soa->getStatements() as $statement) {
    foreach ($statement->getTransactions() as $transaction) {
        $ofxWriter->writeStatementTransaction($transaction);
        if ($configuration['verbose']) {
            $amount = $transaction->getAmount();
            if ($transaction->getCreditDebit() == Transaction::CD_DEBIT) {
                $amount *= -1;
            }
            $transactionTable[] = [
               'date' => $transaction->getBookingDate()->format('d.m.Y'),
               'name' => $transaction->getName(),
               'amount' => $numberFormatter->formatCurrency($amount, $account->getCurrency()),
            ];
        }
    }
}
$ofxWriter->endStatementTransactionAggregate();
$ofxWriter->endStatementResponse();
$ofxWriter->endStatementTransactionWrapper();
$ofxWriter->endBankingMessageSet();
$ofxWriter->endDocument();
if ($configuration['verbose']) {
    $climate->table($transactionTable);
}
