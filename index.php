#!/usr/bin/env php
<?php declare(strict_types=1);
require_once('vendor/autoload.php');

use League\CLImate\CLImate;
use Assert\Assert;
use Assert\AssertionFailedException;

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
$configuration = [
    'url' => null,
    'port' => null,
    'code' => null,
    'user' => null,
    'pin' => null,
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
    $input = $climate->input('PIN:');
    $configuration['pin'] = $input->prompt();
}

try {
    Assert::lazy()
        ->that($configuration['url'], 'url')->notEmpty()->url()
        ->that($configuration['port'], 'port')->notEmpty()->integer()
        ->that($configuration['code'], 'code')->notEmpty()->integer()
        ->that($configuration['user'], 'user')->notEmpty()
        ->that($configuration['pin'], 'pin')->notEmpty()
        ->verifyNow();
} catch (AssertionFailedException $e) {
    $climate->error($e->getMessage());
    exit(1);
}
