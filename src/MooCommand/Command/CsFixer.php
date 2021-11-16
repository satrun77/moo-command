<?php
/*
 * This file is part of the MooCommand package.
 *
 * (c) Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MooCommand\Command;

use MooCommand\Console\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * CsFixer.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class CsFixer extends Command
{
    protected const PHP_FIXER_VERSION = '3.';

    /**
     * @var bool
     */
    protected $runRoot = false;
    /**
     * @var string
     */
    protected $description = 'Execute php-cs-fixer on selected paths.';
    /**
     * @var string
     */
    protected $signature = 'csfixer';
    /**
     * @var array
     */
    protected $arguments = [
        'paths' => [
            'mode' => InputArgument::IS_ARRAY,
            'description' => 'List of relative paths.',
        ],
    ];
    /**
     * @var array
     */
    protected $options = [
        'dry' => [
            'mode' => InputOption::VALUE_OPTIONAL,
            'description' => 'Displays the files that need to be fixed but without modifying them.',
            'default' => false,
            'shortcut' => 'd',
        ],
        'risky' => [
            'mode' => InputOption::VALUE_OPTIONAL,
            'description' => 'Allows you to set whether risky rules may run.',
            'default' => false,
            'shortcut' => 'r',
        ],
        'update' => [
            'mode' => InputOption::VALUE_OPTIONAL,
            'description' => 'Update php-cs-fixer',
            'default' => false,
            'shortcut' => 'u',
        ],
    ];

    /**
     * Main method to execute the command script.
     *
     * @throws \Exception
     */
    protected function fire(): void
    {
        // List of directories/files to scan
        $paths = $this->argument('paths');

        // Update php-cs-fixer only
        if ($this->option('update') !== false) {
            $this->getOutputStyle()->info('Updating php-cs-fixer to latest version...');
            $this->getShellHelper()->execRealTime('php-cs-fixer self-update');

            return;
        }

        // Install command if it does not exists
        if (!$this->getShellHelper()->isCommandInstall('php-cs-fixer')) {
            $this->installCsFixer();
        }

        // Check the version of CS fixer - support 3.x
        $version = $this->getShellHelper()->exec('php-cs-fixer --version')->getOutput();
        if (false === mb_strpos(trim($version), static::PHP_FIXER_VERSION)) {
            $this->getOutputStyle()->error(sprintf('This command require CS Fixer version %sx', static::PHP_FIXER_VERSION));

            return;
        }

        // Fix php code
        foreach ($paths as $path) {
            $this->getOutputStyle()->section('Fixing: ' . $path);

            // Define php-cs-fixer options
            $verbose = $this->option('verbose') !== false ? '--verbose' : '';
            $risky = $this->option('risky') !== false ? '--allow-risky=yes' : '';
            $dryrun = $this->option('dry') !== false ? '--dry-run' : '';

            // Execute and display progress
            if (file_exists($path)) {
                $this->getShellHelper()->execRealTime(
                    "php-cs-fixer fix %s --rules='%s' %s %s %s",
                    $path, $this->getFixes(), $verbose, $dryrun, $risky
                );
            }
        }
    }

    /**
     * Returns a list fixes to apply.
     */
    protected function getFixes(): string
    {
        return json_encode($this->getConfigHelper()->getConfig('csfixes'));
    }

    /**
     * Attempt to install php-cs-fixer in user machine if it does not exists.
     *
     * @throws CommandNotFoundException
     */
    protected function installCsFixer(): void
    {
        $shellHelper = $this->getShellHelper();
        $this->getOutputStyle()->error('php-cs-fixer not installed in your machine. Start attempt to install it...');

        // Check for wget or curl to use for downloading the php-cs-fixer
        $wget = $shellHelper->isCommandInstall('wget');
        $curl = $shellHelper->isCommandInstall('curl');
        if (!$wget && !$curl) {
            throw new CommandNotFoundException("Unable to installed php-cs-fixer. You don't have wget or curl.");
        }

        // Download php-cs-fixer
        $command = 'curl -L https://cs.symfony.com/download/php-cs-fixer-v2.phar -o php-cs-fixer';
        if ($wget) {
            $command = 'wget https://cs.symfony.com/download/php-cs-fixer-v2.phar -O php-cs-fixer';
        }
        $download = $shellHelper->exec($command);
        if (!$download->isSuccessful()) {
            $this->getOutputStyle()->error('Unable to download php-cs-fixer');
        }

        // Make php-cs-fixer executable
        $chmod = $shellHelper->exec('sudo chmod a+x php-cs-fixer');
        if (!$chmod->isSuccessful()) {
            $this->getOutputStyle()->error('Unable to make php-cs-fixer executable');
        }

        // Install php-cs-fixer globally
        $globally = $shellHelper->exec('sudo mv php-cs-fixer /usr/local/bin/php-cs-fixer');
        if (!$globally->isSuccessful()) {
            $this->getOutputStyle()->error('Unable to install php-cs-fixer globally');
        }

        $this->getOutputStyle()->info('php-cs-fixer installed globally.');
    }
}
