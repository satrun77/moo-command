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

/**
 * Workspace.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
abstract class Workspace extends Command
{
    /**
     * @var bool
     */
    protected $runRoot = false;
    /**
     * @var string
     */
    protected $description = 'Manage local workspace with docker containers.';
    /**
     * @var string
     */
    protected $signature = 'ws:';
    /**
     * @var string
     */
    protected $childSignature = '';

    /**
     * List of containers used for a site.
     *
     * @var array
     */
    protected $containers = [
        'mysql',
        'site',
        'php',
        'solr',
        'frontend',
    ];

    /**
     * List of supported environment structures.
     *
     * @var array
     */
    protected $templates = [
        'ss'      => 'SilverStripe',
        'laravel' => 'Laravel Framework',
    ];

    /**
     * List of supported php versions.
     *
     * @var array
     */
    protected $phpVersions = [
        '7.3' => 'PHP 7.3',
        '7.2' => 'PHP 7.2',
        '7.1' => 'PHP 7.1',
        '5.6' => 'PHP 5.6',
    ];

    /**'
     * List of supported work directories
     *
     * @var array
     */
    protected $workDirectories = [
        '/var/www/html',
        '/var/www/html/public',
    ];

    protected function configure()
    {
        $this->signature = $this->signature . $this->childSignature;

        parent::configure();
    }

    /**
     * Returns the ip address of docker machine.
     *
     * @return bool|string
     */
    protected function getMachineIp()
    {
        $ip = $this->getShellHelper()->exec('ifconfig | grep "inet " | grep -Fv 127.0.0.1 | awk \'{print $2}\'');

        if ($ip->isSuccessful()) {
            $potentialIds = explode("\n", trim($ip->getOutput()));
            if (!empty($potentialIds[0])) {
                return $potentialIds[0];
            }
        }

        return 'localhost';
    }

    /**
     * Returns template name by path or key.
     *
     * @param string $variable
     *
     * @return string
     */
    protected function getTemplate($variable)
    {
        // SilverStripe template
        if ('ss' === $variable || file_exists($variable . '/env/ss.env')) {
            return 'ss';
        }

        // Laravel template
        if ('laravel' === $variable || file_exists($variable . '/site/.env')) {
            return 'laravel';
        }

        // Proxy template
        if ('proxy' === $variable || is_dir($variable . '/proxy')) {
            return 'proxy';
        }

        // Any other template
        if (array_key_exists($variable, $this->templates)) {
            return $variable;
        }

        return false;
    }

    /**
     * Return an array of used ports.
     *
     * @param string $service
     *
     * @return array
     */
    protected function getUsedPorts($service = 'VIRTUAL_PORT')
    {
        $workspace = $this->getConfigHelper()->getWorkspace();
        $ports     = [];

        try {
            $iterator = new \DirectoryIterator($workspace);
            foreach ($iterator as $file) {
                $env = $file->getPathname() . '/env/web.env';
                if ($file->isDir() && file_exists($env)) {
                    $envFile = new \SplFileObject($env, 'r');

                    foreach ($envFile as $line) {
                        if (empty($line)) {
                            continue;
                        }

                        $line = explode('=', trim($line));
                        if ($line[0] !== $service) {
                            continue;
                        }

                        // Store ports to an array
                        $ports[$file->getFilename()] = $line[1];
                    }
                }
            }
        } catch (\Exception $e) {
            $this->debug($e->getMessage());
        }

        return $ports;
    }

    /**
     * Get value of an environment file (web.env) option
     *
     * @param $field
     * @param  string $default
     * @return string
     */
    protected function getEnvFileValue($field, $default = '')
    {
        $file = $this->getConfigHelper()->getSiteRoot('name') . '/env/web.env';

        try {
            if (file_exists($file)) {
                $envFile = new \SplFileObject($file, 'r');

                foreach ($envFile as $line) {
                    if (empty($line)) {
                        continue;
                    }

                    $line = explode('=', trim($line));
                    if ($line[0] === $field) {
                        return $line[1];
                    }
                }
            }
        } catch (\Exception $e) {
            $this->debug($e->getMessage());
        }

        return $default;
    }

    /**
     * Returns path to workspace.
     *
     * @return string
     */
    protected function getWorkspace()
    {
        return $this->getConfigHelper()->getWorkspace();
    }

    /**
     * Get argument value. Extend to hack the name value from "." to convert to current directory name.
     *
     * @param string $key
     *
     * @return array|string
     */
    public function argument($key = null)
    {
        $value = parent::argument($key);

        // Hack solution but works for now
        if ('name' === $key && '.' === $value) {
            $value = $this->getConfigHelper()->getCurrentSiteName();
        }

        return $value;
    }

    /**
     * Validate whether a parameter is empty.
     *
     * @param $name
     *
     * @return bool
     *
     * @throws \Exception
     */
    protected function argumentMustNotBeEmpty($name)
    {
        $param = $this->argument($name);
        if (empty($param)) {
            throw new \Exception('The site name cannot be empty.');
        }

        return true;
    }

    /**
     * Validate whether a parameter is equal to 'proxy'.
     *
     * @param $name
     *
     * @return bool
     *
     * @throws \Exception
     */
    protected function siteNameMustNotEqualToProxy($name)
    {
        $param = $this->argument($name);
        if ('proxy' === $param) {
            throw new \Exception('The site name cannot be empty or named \'proxy\'.');
        }

        return true;
    }

    /**
     * Validate whether a site does not exists.
     *
     * @param $name
     *
     * @return bool
     *
     * @throws \Exception
     */
    protected function siteDirectoryMustExists($name)
    {
        $siteRoot = $this->getConfigHelper()->getSiteRoot($name);
        if (is_null($siteRoot)) {
            throw new \Exception(sprintf("Unable to find the site with the name '%s'", $this->argument($name)));
        }

        return true;
    }

    /**
     * Validate whether a site exists.
     *
     * @param $name
     *
     * @return bool
     *
     * @throws \Exception siteDirectoryMustExists
     */
    protected function siteDirectoryMustNotExists($name)
    {
        $siteRoot = $this->getConfigHelper()->getSiteRoot($name);
        if (is_dir($siteRoot)) {
            throw new \Exception(sprintf("There is an existing site with the same name '%s'", $this->argument($name)));
        }

        return true;
    }

    protected function changeToSiteDirectory()
    {
        // Validations
        $this->argumentMustNotBeEmpty('name');
        $this->siteDirectoryMustExists('name');

        // Site root directory
        $siteRoot = $this->getConfigHelper()->getSiteRoot('name');

        // Change current directory to the container root directory
        chdir($siteRoot);

        return $siteRoot;
    }

    /**
     * @param string       $command
     * @param array|string $args
     * @param string       $container
     * @param string       $error
     * @param string       $success
     *
     * @return $this
     *
     * @throws \Exception
     */
    protected function execCommandInContainer($command, $args, $container = 'php', $error = '', $success = '')
    {
        // Validations
        $this->argumentMustNotBeEmpty('name');

        // Site root directory
        $siteRoot = $this->getConfigHelper()->getSiteRoot('name');
        $siteName = basename($siteRoot);
        // Docker prefix can't have "."
        $siteName = str_replace('.', '', $siteName);
        if (is_array($args)) {
            $args = implode(' ', $args);
        }

        $containerName = $container;
        if ('proxy' !== $siteName) {
            $containerName = sprintf('%s_%s_1', $siteName, $container);
        }

        chdir($siteRoot);

        // Execute command inside the docker site
        $status = $this->getShellHelper()->execRealTime('docker exec %s %s %s', $containerName, $command, $args);
        if (!$status && !empty($error)) {
            throw new \Exception($error);
        }

        // Success message
        if (!empty($success)) {
            $this->getOutputStyle()->success($success);
        }

        return $this;
    }

    /**
     * Display information about docker-sync commands.
     *
     * @param  string $volume
     * @param  string $start
     * @param  string $stop
     * @return void
     */
    protected function showDockerSyncInfo($volume, $start = '', $stop = '')
    {
        $this->getOutputStyle()->title('Docker Sync commands:');

        // Message about docker-sync
        if ($volume) {
            $volume = str_replace('.', '', $this->argument('name')) . '_dockersync_1';
            $this->getOutputStyle()->warning([
                'You need to create docker volume, if does not exists!',
                'Volumne name should be: ' . $volume,
            ]);
            $this->getOutputStyle()->info([
                'Command to create the volumne:',
                'docker volume create --name=' . $volume,
            ]);
        }

        if ($start) {
            $this->getOutputStyle()->info([
                'Command to start docker-sync:',
                'docker-sync start',
            ]);
        }

        if ($stop) {
            $this->getOutputStyle()->info([
                'Command to stop docker-sync:',
                'docker-sync stop',
            ]);
        }
    }
}
