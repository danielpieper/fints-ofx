<?php

namespace FintsOfx\Command;

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('export');
    }
}
