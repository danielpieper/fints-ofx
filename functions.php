<?php
use League\CLImate\CLImate;
use Fhp\FinTs;
use Assert\Assert;
use Assert\AssertionFailedException;

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
        'file' => $climate->arguments->get('file'),
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
            ->that($configuration['code'], 'code')->notEmpty()
            ->that($configuration['account'], 'account')->notEmpty()
            ->that($configuration['user'], 'user')->notEmpty()
            ->that($configuration['pin'], 'pin')->notEmpty()
            ->that($configuration['from'], 'from')->notEmpty()
            ->that($configuration['to'], 'to')->notEmpty()
            ->that($configuration['file'], 'file')->notEmpty()
            ->verifyNow();
    } catch (AssertionFailedException $e) {
        $climate->error($e->getMessage());
        exit(1);
    }
    return $configuration;
}

function getSepaAccount(FinTs &$fintsClient, $accountNumber)
{
    $sepaAccounts = $fintsClient->getSEPAAccounts();
    foreach ($sepaAccounts as $sepaAccount) {
        if ($sepaAccount->getAccountNumber() == $accountNumber) {
            return $sepaAccount;
        }
    }
    return false;
}

function getAccount(FinTs &$fintsClient, $accountNumber)
{
    $accounts = $fintsClient->getAccounts();
    foreach ($accounts as $account) {
        if ($account->getAccountNumber() == $accountNumber) {
            return $account;
        }
    }
    return false;
}
