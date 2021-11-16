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
            'mode' => InputArgument::IS_ARRAY,
            'description' => 'List of relative paths.',
        ],
    ];

    /**
     * @var array
     */
    protected $options = [
        'mess' => [
            'shortcut' => 'm',
            'mode' => InputOption::VALUE_NONE,
            'description' => 'Mess Detector Analyses',
            'default' => null,
        ],
        'copypaste' => [
            'shortcut' => 'c',
            'mode' => InputOption::VALUE_NONE,
            'description' => 'Copy/Paste Detector Analyses',
            'default' => null,
        ],
        'lint' => [
            'shortcut' => 'l',
            'mode' => InputOption::VALUE_NONE,
            'description' => 'PHP Parallel Lint Analyses',
            'default' => null,
        ],
        'security' => [
            'shortcut' => 's',
            'mode' => InputOption::VALUE_NONE,
            'description' => 'Security Advisories Checker Analyses',
            'default' => null,
        ],
        'phpstan' => [
            'shortcut' => 'p',
            'mode' => InputOption::VALUE_NONE,
            'description' => 'PHP Static Analysis Tool',
            'default' => null,
        ],
        'dephpend' => [
            'shortcut' => 'd',
            'mode' => InputOption::VALUE_NONE,
            'description' => 'dePHPend Tool',
            'default' => null,
        ],
        'phpinsights' => [
            'shortcut' => 'i',
            'mode' => InputOption::VALUE_NONE,
            'description' => 'PHP Insights Analysis Tool',
            'default' => null,
        ],
    ];

    /**
     * @var array
     */
    protected $analyses = [
        'mess' => [
            'callback' => 'analyseMessDetector',
            'title' => 'Mess Detector',
        ],
        'copypaste' => [
            'callback' => 'analyseCopyPasteDetector',
            'title' => 'Copy/Paste Detector',
        ],
        'lint' => [
            'callback' => 'analyseLintChecker',
            'title' => 'PHP Parallel Lint',
        ],
        'security' => [
            'callback' => 'analyseSecurityChecker',
            'title' => 'Security Advisories Checker',
        ],
        'phpstan' => [
            'callback' => 'analyseStaticCodeChecker',
            'title' => 'PHP Static Analysis Tool',
        ],
        'dephpend' => [
            'callback' => 'analyseDephpendChecker',
            'title' => 'dePHPend Tool',
        ],
        'phpinsights' => [
            'callback' => 'analysePhpInsightsChecker',
            'title' => 'PHP Insights Analysis Tool',
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

        // Install PHP Parallel Lint if not exists
        if (!$this->getShellHelper()->isCommandInstall('dephpend')) {
            $this->installDephpendDetector();
        }

        // Install PHP Parallel Lint if not exists
        if (!$this->getShellHelper()->isCommandInstall('phpinsights')) {
            $this->installPhpInsightsDetector();
        }

        // Scan paths and display report
        $this->scanFiles($paths);
    }

    /**
     * Analyse a path for code quality.
     */
    protected function scanFiles(array $paths): void
    {
        // Get current path and display section title
        $path = current($paths);
        $this->getOutputStyle()->section('Analysing: ' . $path);

        // Analyses options
        $executeAll = $this->hasOptionsAllOrNone(...array_keys($this->analyses));

        // Analyse files within the path
        foreach ($this->analyses as $option => $analyse) {
            // Skip unwanted analyse
            if (!$executeAll && !$this->option($option)) {
                continue;
            }

            $callback = $title = '';
            extract($analyse, EXTR_OVERWRITE);

            // Is it composer.lock file
            $isComposerLock = mb_strpos($path, 'composer.lock');
            $hasComposerLock = file_exists(rtrim($path, '/') . '/composer.lock');

            // Skip analyseSecurityChecker if path is not for composer.lock
            // else, Skip all other analysers if the path if for composer.lock
            if ('analyseSecurityChecker' === $callback && false === $isComposerLock && !$hasComposerLock) {
                continue;
            }
            if ('analyseSecurityChecker' !== $callback && false !== $isComposerLock) {
                continue;
            }

            // Title
            $this->getOutputStyle()->block('[ ' . $title . ' ]', null, 'fg=magenta');

            // Analyse output
            $this->{$callback}($path);
        }

        // Move to the next parameter
        if (next($paths)) {
            $this->scanFiles($paths);
        }
    }

    /**
     * Whether we have all options or none.
     *
     * @param mixed ...$options
     */
    protected function hasOptionsAllOrNone(...$options): bool
    {
        // Create an array containing the options defined by user
        $state = array_filter($options, function ($name) {
            return $this->option($name);
        });

        // Return true if we have no options or have them all
        return empty($state) || count($state) === count($this->analyses);
    }

    /**
     * Execute Mess detector on a path.
     */
    protected function analyseMessDetector(string $path): void
    {
        $phpmd = $this->getShellHelper()->exec(
            'phpmd %s ansi cleancode, codesize, controversial, design, naming, unusedcode',
            $path
        );

        try {
            $xml = new \SimpleXMLElement((string)$phpmd->getOutput());
        } catch (\Exception $e) {
            $this->getOutputStyle()->error($e->getMessage());

            return;
        }

        $rows = [];
        foreach ($xml->file as $file) {
            $filePath = (string)$file->attributes()['name'];
            $beginLine = (string)$file->violation->attributes()['beginline'];
            $endLine = (string)$file->violation->attributes()['endline'];
            $message = trim((string)$file->violation);

            $rows[] = [$beginLine, $endLine, $filePath, $message];
        }

        // Display table of data
        $headers = ['Begin line', 'End line', 'File', 'Note'];
        $this->getOutputStyle()->table($headers, $rows);
    }

    /**
     * Execute Copy/Paste detector on a path.
     */
    protected function analyseCopyPasteDetector(string $path): void
    {
        $phpcpd = $this->getShellHelper()->exec('phpcpd %s', $path);
        $output = $phpcpd->getOutput();

        $this->getOutputStyle()->info($output);
    }

    /**
     * Execute security checker on composer.lock file.
     */
    protected function analyseSecurityChecker(string $path): void
    {
        $curl = $this->getShellHelper()->isCommandInstall('curl');
        if (!$curl) {
            $this->getOutputStyle()->error('curl command is required for this analyser.');

            return;
        }

        // If composer.lock is in the root level of the directory $path
        if (false === mb_strpos($path, 'composer.lock')) {
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
     */
    protected function analyseLintChecker(string $path): void
    {
        $this->getShellHelper()->execRealTime(
            'parallel-lint %s',
            $path
        );
    }

    /**
     * Execute PHP Static Analysis Tool.
     */
    protected function analyseStaticCodeChecker(string $path): void
    {
        // Get path to workspace
        $workspace = $this->getConfigHelper()->getWorkspace();
        // Get name of current site
        $siteName = $this->getConfigHelper()->getCurrentSiteName();
        // Get path to site root directory - default to current path
        $siteRootPath = rtrim(getcwd(), '/') . '/';

        // If site name can be found, then display INFO message, else set site root directory to current site (docker setup)
        if (empty($siteName)) {
            $this->getOutputStyle()->info(sprintf('Unable to find a workspace site. The anaylse is from the current directory: %s', getcwd()));
        } else {
            $siteRootPath = $workspace . $siteName . '/site/';
        }

        // Default code base, or if we are in laravel code base, or if we are in SilverStripe 4+ code base
        $codeBase = 'default';
        if (file_exists($siteRootPath . 'artisan')) {
            $codeBase = 'laravel';
        } elseif (is_dir($siteRootPath . 'vendor/silverstripe')) {
            $codeBase = 'silverstripe';
        }

        // Collection of commands based on each code base - default collection and override defined in .moo.yml
        $commands = array_merge([
            'default' => '{site_root}vendor/bin/phpstan analyse {path} --level 1 --memory-limit=5000M --ansi',
            'laravel' => 'php artisan code:analyse --paths="{path}"',
            'silverstripe' => '{site_root}vendor/bin/phpstan analyse {path} -c {site_root}phpstan.neon -a {site_root}vendor/symbiote/silverstripe-phpstan/bootstrap.php --level 1 --memory-limit=5000M --ansi',
        ], (array)$this->getConfigHelper()->getConfig('qcode.phpstan'));

        // Check if we have a command to execute based on code base
        if (empty($commands[$codeBase])) {
            $this->getOutputStyle()->error(sprintf('There is no command to execute for base code: %s', $codeBase));

            return;
        }

        // Execute analyse command
        $this->getShellHelper()->execRealTime(strtr($commands[$codeBase], [
            '{path}' => $path,
            '{site_root}' => $siteRootPath,
        ]));
    }

    /**
     * Execute dePHPend on a path.
     */
    protected function analyseDephpendChecker(string $path): void
    {
        $this->getShellHelper()->execRealTime(
            'dephpend uml %s --keep-uml --output=%s.png --depth=3',
            $path,
            $this->getName()
        );
    }

    /**
     * Execute PHP Insights on a path.
     */
    protected function analysePhpInsightsChecker(string $path): void
    {
        $this->getShellHelper()->execRealTime(
            '~/.composer/vendor/bin/phpinsights analyse %s',
            $path
        );
    }

    /**
     * Attempt to install phpmd in user machine if it does not exists.
     *
     * @throws CommandNotFoundException
     */
    protected function installMessDetector(): void
    {
        $this->installCommandLine('phpmd', 'https://phpmd.org/static/latest/phpmd.phar');
    }

    /**
     * Attempt to install phpcpd in user machine if it does not exists.
     *
     * @throws CommandNotFoundException
     */
    protected function installCopyPasteDetector(): void
    {
        $this->installCommandLine('phpcpd', 'https://phar.phpunit.de/phpcpd.phar');
    }

    /**
     * Attempt to install composer in user machine if it does not exists.
     *
     * @throws CommandNotFoundException
     */
    protected function installComposer(): void
    {
        $this->installCommandLine('composer', 'https://getcomposer.org/composer.phar');
    }

    /**
     * Attempt to install php-parallel-lint in user machine if it does not exists.
     *
     * @throws CommandNotFoundException
     */
    protected function installPhpLintDetector(): void
    {
        // Install composer if not exists
        if (!$this->getShellHelper()->isCommandInstall('composer')) {
            $this->installComposer();
        }

        // Download php-parallel-lint
        $command = $this->getShellHelper()->exec('composer global require --dev jakub-onderka/php-parallel-lint');
        if (!$command->isSuccessful()) {
            $this->getOutputStyle()->error('Unable to make install php-parallel-lint globally.');

            return;
        }

        // Download colored output plugin. This is just nice to have
        $command = $this->getShellHelper()->exec('composer global require --dev jakub-onderka/php-console-highlighter');
        if (!$command->isSuccessful()) {
            $this->getOutputStyle()->error('Unable to make install php-console-highlighter globally.');
        }

        $this->getOutputStyle()->info('php-parallel-lint installed globally.');
    }

    /**
     * Install dePHPend from phar file
     */
    protected function installDephpendDetector(): void
    {
        $this->installCommandLine('dephpend', 'https://github.com/mihaeu/dephpend/releases/download/0.8.0/dephpend-0.8.0.phar');
        $this->getOutputStyle()->info('dePHPend installed globally.');
    }

    /**
     * Install PHP Insights using composer global
     */
    protected function installPhpInsightsDetector(): void
    {
        // Install composer if not exists
        if (!$this->getShellHelper()->isCommandInstall('composer')) {
            $this->installComposer();
        }

        // Download PHP Insights
        $command = $this->getShellHelper()->exec('composer global require --dev nunomaduro/phpinsights');
        if (!$command->isSuccessful()) {
            $this->getOutputStyle()->error('Unable to make install nunomaduro/phpinsights globally.');

            return;
        }

        $this->getOutputStyle()->info('PHP insights installed globally.');
    }

    /**
     * Attempt to install a command in user machine if it does not exists.
     *
     * @throws CommandNotFoundException
     */
    protected function installCommandLine(string $name, string $url): void
    {
        $this->getOutputStyle()->info($name . ' not installed in your machine. Start attempt to install it...');

        // Check for wget or curl to use for downloading the command
        $wget = $this->getShellHelper()->isCommandInstall('wget');
        $curl = $this->getShellHelper()->isCommandInstall('curl');
        if (!$wget && !$curl) {
            throw new CommandNotFoundException('Unable to installed ' . $name . ". You don't have wget or curl.");
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
