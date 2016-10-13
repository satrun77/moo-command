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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * CodeQuality.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class CodeQuality extends Command
{
    /**
     * @var bool
     */
    protected $runRoot = false;
    /**
     * @var string
     */
    protected $description = 'Check source code using tool such as, Mess Detector, Copy/Paste Detector, PHP Parallel Lint, & Security Advisories.';
    /**
     * @var string
     */
    protected $signature = 'qcode';
    /**
     * @var array
     */
    protected $arguments = [
        'paths' => [
            'mode'        => InputArgument::IS_ARRAY,
            'description' => 'List of relative paths.',
        ],
    ];

    /**
     * @var array
     */
    protected $options = [
        'mess'      => [
            'shortcut'    => 'm',
            'mode'        => InputOption::VALUE_NONE,
            'description' => 'Mess Detector Analyses',
            'default'     => null,
        ],
        'copypaste' => [
            'shortcut'    => 'c',
            'mode'        => InputOption::VALUE_NONE,
            'description' => 'Copy/Paste Detector Analyses',
            'default'     => null,
        ],
        'lint'      => [
            'shortcut'    => 'l',
            'mode'        => InputOption::VALUE_NONE,
            'description' => 'PHP Parallel Lint Analyses',
            'default'     => null,
        ],
        'security'  => [
            'shortcut'    => 's',
            'mode'        => InputOption::VALUE_NONE,
            'description' => 'Security Advisories Checker Analyses',
            'default'     => null,
        ],
    ];

    /**
     * @var array
     */
    protected $analyses = [
        'mess'      => [
            'callback' => 'analyseMessDetector',
            'title'    => 'Mess Detector',
        ],
        'copypaste' => [
            'callback' => 'analyseCopyPasteDetector',
            'title'    => 'Copy/Paste Detector',
        ],
        'lint'      => [
            'callback' => 'analyseLintChecker',
            'title'    => 'PHP Parallel Lint',
        ],
        'security'  => [
            'callback' => 'analyseSecurityChecker',
            'title'    => 'Security Advisories Checker',
        ],
    ];

    /**
     * Main method to execute the command script.
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function fire()
    {
        // List of directories/files to scan
        $paths = $this->argument('paths');

        // Install Mess Detector if not exists
        if (!$this->getShellHelper()->isCommandInstall('phpmd')) {
            $this->installMessDetector();
        }

        // Install Copy/Paste Detector if not exists
        if (!$this->getShellHelper()->isCommandInstall('phpcpd')) {
            $this->installCopyPasteDetector();
        }

        // Install PHP Parallel Lint if not exists
        if (!$this->getShellHelper()->isCommandInstall('parallel-lint')) {
            $this->installPhpLintDetector();
        }

        // Scan paths and display report
        $this->scanFiles($paths);
    }

    /**
     * Analyse a path for code quality.
     *
     * @param string $paths
     *
     * @return void
     */
    protected function scanFiles($paths)
    {
        // Get current path and display section title
        $path = current($paths);
        $this->getOutputStyle()->section('Analysing: ' . $path);

        // Analyses options
        $messDetector      = $this->option('mess');
        $copyPasteDetector = $this->option('copypaste');
        $securityDetector  = $this->option('security');
        $lintDetector      = $this->option('lint');
        $executeAll        = (!$messDetector && !$copyPasteDetector && !$securityDetector && !$lintDetector) ||
                      ($messDetector && $copyPasteDetector && $securityDetector && $lintDetector);

        // Analyse files within the path
        foreach ($this->analyses as $option => $analyse) {
            // Skip unwanted analyse
            if (!$executeAll && !$this->option($option)) {
                continue;
            }

            $callback = $title = '';
            extract($analyse);

            // Is it composer.lock file
            $isComposerLock  = strpos($path, 'composer.lock');
            $hasComposerLock = file_exists(rtrim($path, '/') . '/composer.lock');

            // Skip analyseSecurityChecker if path is not for composer.lock
            // else, Skip all other analysers if the path if for composer.lock
            if ($callback === 'analyseSecurityChecker' && $isComposerLock === false && $hasComposerLock === false) {
                continue;
            } elseif ($callback !== 'analyseSecurityChecker' && $isComposerLock !== false) {
                continue;
            }

            // Title
            $this->getOutputStyle()->block('[ ' . $title . ' ]', null, 'fg=magenta');

            // Analyse output
            $this->{$callback}($path);
        }

        // Move to the next parameter
        if (next($paths)) {
            return $this->scanFiles($paths);
        }
    }

    /**
     * Execute Mess detector on a path.
     *
     * @param string $path
     */
    protected function analyseMessDetector($path)
    {
        $phpmd = $this->getShellHelper()->exec(
            'phpmd %s xml cleancode, codesize, controversial, design, naming, unusedcode',
            $path
        );

        try {
            $xml = new \SimpleXMLElement((string) $phpmd->getOutput());
        } catch (\Exception $e) {
            return $this->getOutputStyle()->error($e->getMessage());
        }

        $rows = [];
        foreach ($xml->file as $file) {
            $filePath  = (string) $file->attributes()['name'];
            $beginLine = (string) $file->violation->attributes()['beginline'];
            $endLine   = (string) $file->violation->attributes()['endline'];
            $message   = trim((string) $file->violation);

            $rows[] = [$beginLine, $endLine, $filePath, $message];
        }

        // Display table of data
        $headers = ['Begin line', 'End line', 'File', 'Note'];
        $this->getOutputStyle()->table($headers, $rows);
    }

    /**
     * Execute Copy/Paste detector on a path.
     *
     * @param string $path
     */
    protected function analyseCopyPasteDetector($path)
    {
        $phpcpd = $this->getShellHelper()->exec('phpcpd %s', $path);
        $output = $phpcpd->getOutput();

        $this->getOutputStyle()->info($output);
    }

    /**
     * Execute security checker on composer.lock file.
     *
     * @param string $path
     */
    protected function analyseSecurityChecker($path)
    {
        $curl = $this->getShellHelper()->isCommandInstall('curl');
        if (!$curl) {
            return $this->getOutputStyle()->error('curl command is required for this analyser.');
        }

        // If composer.lock is in the root level of the directory $path
        if (strpos($path, 'composer.lock') === false) {
            $path = rtrim($path, '/') . '/composer.lock';
        }

        // curl -H "Accept: application/json" https://security.sensiolabs.org/check_lock -F lock=@./composer.lock
        $security = $this->getShellHelper()->exec(
            'curl -H "Accept: text/plain" https://security.sensiolabs.org/check_lock -F lock=@%s',
            $path
        );
        $this->getOutputStyle()->info($security->getOutput());
    }

    /**
     * Execute PHP Parallel Lint on a path.
     *
     * @param string $path
     */
    protected function analyseLintChecker($path)
    {
        $this->getShellHelper()->execRealTime(
            'parallel-lint %s',
            $path
        );
    }

    /**
     * Attempt to install phpmd in user machine if it does not exists.
     *
     * @throws \CommandNotFoundException
     */
    protected function installMessDetector()
    {
        return $this->installCommandLine('phpmd', 'http://static.phpmd.org/php/latest/phpmd.phar');
    }

    /**
     * Attempt to install phpcpd in user machine if it does not exists.
     *
     * @throws \CommandNotFoundException
     */
    protected function installCopyPasteDetector()
    {
        return $this->installCommandLine('phpcpd', 'https://phar.phpunit.de/phpcpd.phar');
    }

    /**
     * Attempt to install composer in user machine if it does not exists.
     *
     * @throws \CommandNotFoundException
     */
    protected function installComposer()
    {
        return $this->installCommandLine('composer', 'https://getcomposer.org/composer.phar');
    }

    /**
     * Attempt to install php-parallel-lint in user machine if it does not exists.
     *
     * @throws \CommandNotFoundException
     */
    protected function installPhpLintDetector()
    {
        // Install composer if not exists
        if (!$this->getShellHelper()->isCommandInstall('composer')) {
            $this->installComposer();
        }

        // Download php-parallel-lint
        $command = $this->getShellHelper()->exec('composer global require --dev jakub-onderka/php-parallel-lint');
        if (!$command->isSuccessful()) {
            return $this->getOutputStyle()->error('Unable to make install php-parallel-lint globally.');
        }

        // Download colored output plugin. This is just nice to have
        $command = $this->getShellHelper()->exec('composer global require --dev jakub-onderka/php-console-highlighter');
        if (!$command->isSuccessful()) {
            $this->getOutputStyle()->error('Unable to make install php-console-highlighter globally.');
        }

        $this->getOutputStyle()->info('php-parallel-lint installed globally.');
    }

    /**
     * Attempt to install a command in user machine if it does not exists.
     *
     * @param string $name
     * @param string $url
     *
     * @throws \CommandNotFoundException
     */
    protected function installCommandLine($name, $url)
    {
        $this->getOutputStyle()->info($name . ' not installed in your machine. Start attempt to install it...');

        // Check for wget or curl to use for downloading the command
        $wget = $this->getShellHelper()->isCommandInstall('wget');
        $curl = $this->getShellHelper()->isCommandInstall('curl');
        if (!$wget && !$curl) {
            throw new \CommandNotFoundException('Unable to installed ' . $name . '. You don\'t have wget or curl.');
        }

        // Download the command
        $command = 'curl ' . $url . ' -o ' . $name;
        if ($wget) {
            $command = 'wget ' . $url . ' -O ' . $name;
        }
        $download = $this->getShellHelper()->exec($command);
        if (!$download->isSuccessful()) {
            $this->getOutputStyle()->error('Unable to download ' . $name);
        }

        // Make command executable
        $chmod = $this->getShellHelper()->exec('sudo chmod a+x ' . $name);
        if (!$chmod->isSuccessful()) {
            $this->getOutputStyle()->error('Unable to make ' . $name . ' executable');
        }

        // Install command globally
        $globally = $this->getShellHelper()->exec('sudo mv %s /usr/local/bin/%s', $name, $name);
        if (!$globally->isSuccessful()) {
            $this->getOutputStyle()->error('Unable to install ' . $name . ' globally');
        }

        $this->getOutputStyle()->info($name . ' installed globally.');
    }
}
