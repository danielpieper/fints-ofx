<?php declare(strict_types=1);

namespace FintsOfx\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigureCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('configure')
            ->setDescription('Setup institutions and bank accounts');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = __DIR__ . DIRECTORY_SEPARATOR . 'config.yaml';
        if (!file_exists('config.yaml')) {
            $config = <<<EOF
institutions:
  - name: 
    url: fints url, see https://www.hbci-zka.de/institute/institut_auswahl.htm
    port: 3000
    code: bank routing code
    bic: bank identifier code
    username: 
    password:
    currency: EUR
    accounts:
      - name: Checking
        number:
        iban:
      - name: Credit Card
        number: 
        iban: 
EOF
;
            file_put_contents($filename, $config);
        }
        $output->writeln('Configuration template saved as config.yaml');
    }
}
