<?php declare(strict_types=1);

namespace danielpieper\FintsOfx\Command;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Command\Command;

class BaseCommand extends Command
{
    protected function getConfigurationFile()
    {
        $configDirectories = [
            implode(DIRECTORY_SEPARATOR, [$this->getUserHomeFolder(), '.config', 'fints-ofx']) . DIRECTORY_SEPARATOR,
        ];

        $locator = new FileLocator($configDirectories);
        $configurationFile = $locator->locate('config.yaml', realpath(__DIR__ . '/../../'), true);

        return $configurationFile;
    }

    /**
     * @return null|string
     */
    protected function getUserHomeFolder()
    {
        $home = getenv('HOME');
        if (!empty($home)) {
            $home = rtrim($home, '/');
        } elseif (!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOMEPATH'])) {
            // home on windows
            $home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
            $home = rtrim($home, '\\/');
        }
        return empty($home) ? null : $home;
    }
}
