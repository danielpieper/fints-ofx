#!/usr/bin/env php
<?php declare(strict_types=1);
require_once('vendor/autoload.php');

use League\CLImate\CLImate;
use Assert\Assert;
use Assert\AssertionFailedException;
use Fhp\FinTs;
use Fhp\Model\StatementOfAccount\Statement;
use Fhp\Model\StatementOfAccount\Transaction;

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
        'prefix' => 'c',
        'longPrefix' => 'code',
        'description' => 'Bank routing code',
        'castTo' => 'int',
    ],
    'account' => [
        'prefix' => 'a',
        'longPrefix' => 'account',
        'description' => 'Bank account number',
        'castTo' => 'int',
    ],
    'user' => [
        'prefix' => 'u',
        'longPrefix' => 'user',
        'description' => 'username or account number',
    ],
    'pin' => [
        'prefix' => 'p',
        'longPrefix' => 'pin',
        'description' => 'PIN',
    ],
    'from' => [
        'prefix' => 'f',
        'longPrefix' => 'from',
        'description' => 'From date',
        'defaultValue' => '2 weeks ago',
    ],
    'to' => [
        'prefix' => 't',
        'longPrefix' => 'to',
        'description' => 'To date',
        'defaultValue' => 'today',
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

$sepaAccount = getSepaAccount($fintsClient);
if (!$sepaAccount) {
    $climate->error('Could not find bank account.');
    exit(1);
}
if ($configuration['verbose']) {
    $padding = $climate->padding(10);
    $padding->label('Account')->result($sepaAccount->getAccountNumber());
    $padding->label('Owner')->result($sepaAccount->getAccountOwnerName());
    $padding->label('Description')->result($sepaAccount->getAccountDescription());
    $padding->label('IBAN')->result($sepaAccount->getIban());
    $padding->label('Balance')->result($fintsClient->getSaldo($sepaAccount)->getAmount());
}

$soa = $fintsClient->getStatementOfAccount(
    $sepaAccount,
    (new \DateTime($configuration['from'])),
    (new \DateTime($configuration['to']))
);
foreach ($soa->getStatements() as $statement) {
    foreach ($statement->getTransactions() as $transaction) {
        // TODO
    }
}

function getConfiguration(CLImate &$climate)
{
    $configuration = [
        'url' => null,
        'port' => null,
        'code' => null,
        'account' => null,
        'user' => null,
        'pin' => null,
        'from' => $climate->arguments->get('from'),
        'to' => $climate->arguments->get('to'),
        'verbose' => $climate->arguments->get('verbose'),
    ];
    // get url:
    if ($climate->arguments->defined('url')) {
        $configuration['url'] = $climate->arguments->get('url');
    } else {
        $input = $climate->input('FinTS bank url:');
        $configuration['url'] = $input->prompt();
    }
    // get port:
    if ($climate->arguments->defined('port')) {
        $configuration['port'] = $climate->arguments->get('port');
    } else {
        $input = $climate->input('FinTS bank port:');
        $configuration['port'] = $input->prompt();
    }
    // get code:
    if ($climate->arguments->defined('code')) {
        $configuration['code'] = $climate->arguments->get('code');
    } else {
        $input = $climate->input('Bank routing code:');
        $configuration['code'] = $input->prompt();
    }
    // get account:
    if ($climate->arguments->defined('account')) {
        $configuration['account'] = $climate->arguments->get('account');
    } else {
        $input = $climate->input('Bank account number:');
        $configuration['account'] = $input->prompt();
    }
    // get user:
    if ($climate->arguments->defined('user')) {
        $configuration['user'] = $climate->arguments->get('user');
    } else {
        $input = $climate->input('Username or account number:');
        $configuration['user'] = $input->prompt();
    }
    // get pin:
    if ($climate->arguments->defined('pin')) {
        $configuration['pin'] = $climate->arguments->get('pin');
    } else {
        $input = $climate->password('PIN:');
        $configuration['pin'] = $input->prompt();
    }

    try {
        Assert::lazy()
            ->that($configuration['url'], 'url')->notEmpty()->url()
            ->that($configuration['port'], 'port')->notEmpty()->integer()
            ->that($configuration['code'], 'code')->notEmpty()->integer()
            ->that($configuration['account'], 'account')->notEmpty()->integer()
            ->that($configuration['user'], 'user')->notEmpty()
            ->that($configuration['pin'], 'pin')->notEmpty()
            ->that($configuration['from'], 'from')->notEmpty()
            ->that($configuration['to'], 'to')->notEmpty()
            ->verifyNow();
    } catch (AssertionFailedException $e) {
        $climate->error($e->getMessage());
        exit(1);
    }
    return $configuration;
}

function getSepaAccount(FinTs &$fintsClient)
{
    $sepaAccounts = $fintsClient->getSEPAAccounts();
    foreach ($sepaAccounts as $sepaAccount) {
        if ($sepaAccount->getAccountNumber() == $configuration['account']) {
            return $sepaAccount;
        }
    }
    return false;
}
