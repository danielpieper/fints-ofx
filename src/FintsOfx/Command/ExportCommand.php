<?php declare(strict_types=1);

namespace FintsOfx\Command;

use Fhp\FinTs;
use Fhp\Model\Account;
use Fhp\Model\SEPAAccount;
use FintsOfx\OFXWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCommand extends Command
{
    private $configuration;

    public function __construct(string $name = null, array $configuration)
    {
        parent::__construct($name);
        $this->configuration = $configuration;
    }

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
        foreach ($this->configuration['institutions'] as $institution) {
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

                $from = new \DateTime($this->configuration['from']);
                $to = new \DateTime($this->configuration['to']);

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

                $ofxWriter = new OFXWriter($filename);
                $ofxWriter->startDocument();
                $ofxWriter->writeSignOnMessageSet();
                $ofxWriter->startBankingMessageSet();
                $ofxWriter->startStatementTransactionWrapper();
                $ofxWriter->startStatementResponse($accountModel);
                $ofxWriter->startStatementTransactionAggregate($soa);
                foreach ($soa->getStatements() as $statement) {
                    foreach ($statement->getTransactions() as $transaction) {
                        $ofxWriter->writeStatementTransaction($transaction);
                    }
                }
                $ofxWriter->endStatementTransactionAggregate();
                $ofxWriter->endStatementResponse();
                $ofxWriter->endStatementTransactionWrapper();
                $ofxWriter->endBankingMessageSet();
                $ofxWriter->endDocument();
            }
        }
    }
}
