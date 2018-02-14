<?php declare(strict_types=1);

namespace danielpieper\FintsOfx\Command;

use Fhp\FinTs;
use Fhp\Model\Account;
use Fhp\Model\SEPAAccount;
use Fhp\Model\StatementOfAccount\Transaction;
use danielpieper\FintsOfx\OFXWriter;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use danielpieper\FintsOfx\Config\AppConfiguration;
use Symfony\Component\Config\Definition\Processor;

class ExportCommand extends BaseCommand
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
        $filename = null;
        try {
            $filename = $this->getConfigurationFile();
        } catch (FileLocatorFileNotFoundException $e) {
            $output->writeln('config.yaml not found. Please run fints-ofx configure.');
            throw $e;
        }

        // Load configuration
        $config = Yaml::parseFile($filename);
        $processor = new Processor();
        $configuration = new AppConfiguration();
        $processedConfiguration = $processor->processConfiguration($configuration, [$config]);

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

                $startDate = new \DateTime($processedConfiguration['start_date']);
                $endDate = new \DateTime($processedConfiguration['end_date']);

                $soa = $fintsClient->getStatementOfAccount(
                    $sepaAccountModel,
                    $startDate,
                    $endDate
                );

                $filename = sprintf(
                    "%s_%s_%s.ofx",
                    strtolower(strtr($account['name'], [' ' => '_'])),
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d')
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
                            $amount =
                                ($transaction->getCreditDebit() == Transaction::CD_DEBIT ? -100 : 100)
                                * $transaction->getAmount();
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
