<?php
use League\CLImate\CLImate;
use Fhp\FinTs;
use Lib\AppConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

/**
 * @param CLImate $climate
 * @return array
 */
function getConfiguration(CLImate &$climate)
{
    $config = array_merge(
        Yaml::parseFile($climate->arguments->get('config')),
        [
            'from' => $climate->arguments->get('from'),
            'to' => $climate->arguments->get('to'),
            'file' => $climate->arguments->get('file'),
            'verbose' => $climate->arguments->get('verbose'),
        ]
    );
    $processor = new Processor();
    $configuration = new AppConfiguration();

    return $processor->processConfiguration($configuration, $config);
}

/**
 * @param FinTs $fintsClient
 * @param $accountNumber
 * @return bool|\Fhp\Model\SEPAAccount
 * @throws \Fhp\Adapter\Exception\AdapterException
 * @throws \Fhp\Adapter\Exception\CurlException
 */
function getSepaAccount(FinTs &$fintsClient, $accountNumber)
{
    $sepaAccounts = $fintsClient->getSEPAAccounts();
    foreach ($sepaAccounts as $sepaAccount) {
        var_dump($sepaAccount);
        if ($sepaAccount->getAccountNumber() == $accountNumber) {
            return $sepaAccount;
        }
    }
    return false;
}

/**
 * @param FinTs $fintsClient
 * @param $accountNumber
 * @return bool|\Fhp\Model\Account
 */
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
