<?php declare(strict_types=1);

namespace FintsOfx\Command;

use Fhp\FinTs;
use Fhp\Model\Account;
use Fhp\Model\SEPAAccount;
use Fhp\Model\StatementOfAccount\Transaction;
use FintsOfx\OFXWriter;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\GlobFileLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use FintsOfx\Config\AppConfiguration;
use Symfony\Component\Config\Definition\Processor;

class ExportCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('export')
            ->setDescription('Export transactions as ofx files');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $locator = new FileLocator([__DIR__]);
        try {
            $configFile = $locator->locate('config.yaml');
        } catch (FileLocatorFileNotFoundException $e) {
            $output->writeln($e->getMessage());
            $output->writeln('Please run fints-ofx configure.');
            exit(1);
        }

        // Load configuration
        $config = Yaml::parseFile($configFile);
        //            [
        //                'from' => $climate->arguments->get('from'),
        //                'to' => $climate->arguments->get('to'),
        //            ]
        $processor = new Processor();
        $configuration = new AppConfiguration();
        $processedConfiguration = $processor->processConfiguration($configuration, [$config]);
        $processedConfiguration['from'] = '1 month ago';
        $processedConfiguration['to'] = 'now';

        $currencies = new ISOCurrencies();
        $moneyFormatter = new IntlMoneyFormatter(
            new \NumberFormatter('en_US', \NumberFormatter::DECIMAL),
            $currencies
        );
        $decimalMoneyFormatter = new DecimalMoneyFormatter($currencies);
        foreach ($processedConfiguration['institutions'] as $institution) {
            $fintsClient = new FinTs(
                $institution['url'],
                $institution['port'],
                $institution['code'],
                $institution['username'],
                $institution['password']
            );

            foreach ($institution['accounts'] as $account) {
                $sepaAccountModel = new SEPAAccount();
                $sepaAccountModel->setIban($account['iban']);
                $sepaAccountModel->setAccountNumber($account['number']);
                $sepaAccountModel->setBic($institution['bic']);
                $sepaAccountModel->setBlz($institution['code']);

                $accountModel = new Account();
                $accountModel->setCurrency($institution['currency']);
                $accountModel->setAccountNumber($account['number']);
                $accountModel->setBankCode($institution['code']);

                $from = new \DateTime($processedConfiguration['from']);
                $to = new \DateTime($processedConfiguration['to']);

                $soa = $fintsClient->getStatementOfAccount(
                    $sepaAccountModel,
                    $from,
                    $to
                );

                $filename = sprintf(
                    "%s_%s_%s.ofx",
                    strtolower(strtr($account['name'], [' ' => '_'])),
                    $from->format('Y-m-d'),
                    $to->format('Y-m-d')
                );

                $table = null;
                if ($output->isVerbose()) {
                    $table = new Table($output);
                    $table->setHeaders(['Booking date', 'Name', 'Amount']);
                }

                $ofxWriter = new OFXWriter($filename, $decimalMoneyFormatter);
                $ofxWriter->startDocument();
                $ofxWriter->writeSignOnMessageSet();
                $ofxWriter->startBankingMessageSet();
                $ofxWriter->startStatementTransactionWrapper();
                $ofxWriter->startStatementResponse($accountModel);
                $ofxWriter->startStatementTransactionAggregate($soa);
                foreach ($soa->getStatements() as $statement) {
                    foreach ($statement->getTransactions() as $transaction) {
                        $ofxWriter->writeStatementTransaction($accountModel, $transaction);
                        if ($output->isVerbose() && $table) {
                            $amount = ($transaction->getCreditDebit() == Transaction::CD_DEBIT ? -100 : 100) * $transaction->getAmount();
                            $money = new Money($amount, new Currency($accountModel->getCurrency()));
                            $table->addRow([
                                $transaction->getBookingDate()->format('Y-m-d'),
                                $transaction->getName(),
                                $moneyFormatter->format($money)
                            ]);
                        }
                    }
                }
                $ofxWriter->endStatementTransactionAggregate();
                $ofxWriter->endStatementResponse();
                $ofxWriter->endStatementTransactionWrapper();
                $ofxWriter->endBankingMessageSet();
                $ofxWriter->endDocument();
                if ($output->isVerbose() && $table) {
                    $table->setStyle('borderless');
                    $table->render();
                }

                if (!$output->isQuiet()) {
                    $output->writeln(sprintf('<info>file %s saved.</info>', $filename));
                }
            }
        }
    }
}
