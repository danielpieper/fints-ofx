<?php declare(strict_types=1);

namespace danielpieper\FintsOfx\Command;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Command\Command;

class BaseCommand extends Command
{
    /**
     * @param string|null $currentDir
     * @return string
     */
    protected function getConfigurationFile($currentDir = null)
    {
        if ($currentDir === null) {
            $currentDir = realpath(__DIR__ . '/../../');
        }
        $configDirectories = [
            implode(DIRECTORY_SEPARATOR, [$this->getUserHomeFolder(), '.config', 'fints-ofx']),
        ];

        $locator = new FileLocator($configDirectories);
        $configurationFile = $locator->locate('config.yaml', $currentDir, true);

        return (string)$configurationFile;
    }

    /**
     * @return null|string
     */
    protected function getUserHomeFolder()
    {
        $home = (string)getenv('HOME');
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
