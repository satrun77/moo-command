<?php

/*
 * This file is part of the MooCommand package.
 *
 * (c) Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MooCommand\Console\Helper;

use MooCommand\Console\Command;
use SplFileInfo;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Yaml\Parser;

/**
 * Class ConfigHelper.
 */
class ConfigHelper extends Helper
{
    /**
     * @var array
     */
    protected $config;

    /**
     * Get the path to workspace. Defined in .moo.yml file.
     *
     * @return string
     */
    public function getWorkspace(): string
    {
        return rtrim($this->getConfig('workspace'), '/') . '/';
    }

    /**
     * Get site directory name from the current path.
     *
     * @return string
     */
    public function getCurrentSiteName(): string
    {
        // Current path & convert into an array
        $path     = realpath(trim($this->getShellHelper()->exec('pwd')->getOutput()));
        $segments = explode('/', $path);
        // Workspace path without trailing "/"
        $workspace = trim($this->getWorkspace(), '/');

        // Loop over the path segments until we found a path that matched the workspace,
        // then the previous item is the site name
        $siteName = end($segments);
        do {
            $path         = trim(dirname($path), '/');
            $pathNotFound = $workspace === $path;
        } while (!$pathNotFound && $siteName = prev($segments));

        return $siteName;
    }

    /**
     * Get the docker site root path.
     *
     * @param string $name
     *
     * @return string
     */
    public function getSiteRoot(string $name = 'name'): ?string
    {
        $fullPath = $this->getWorkspace() . $this->getCommand()->argument($name);

        if (is_dir($fullPath)) {
            return $fullPath;
        }

        return null;
    }

    /**
     * Copy files or directory from the resource directory in this application.
     *
     * @param string $source
     * @param string $destination
     * @param array  $excludeData
     */
    public function copyResource(string $source, string $destination, array $excludeData = []): void
    {
        $directory = __APP_DIR__ . '/resources/' . $source;
        // Collection of dot files that should be converted to correct name
        $dotFiles = [
            'gitkeep'      => '.gitkeep',
            'site/env'     => 'site/.env',
            'dockerignore' => '.dockerignore',
        ];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            $relativePath = str_replace($directory, '', $file->getPath());
            $fileName     = strtr($iterator->getSubPathName(), $dotFiles);
            $filePath     = $destination . DIRECTORY_SEPARATOR . $fileName;

            // Ignore MAC .DS_Store
            if ($file->getFilename() === '.DS_Store') {
                continue;
            }

            if ($file->isDir()) {
                if (!is_dir($filePath)) {
                    mkdir($filePath, 0755, true);
                }
                $this->getCommand()->debug('Make dir: ' . $filePath);
            } elseif (!in_array($relativePath, $excludeData, true)) {
                $this->getCommand()->debug('Copy:     ' . $file->getPathname() . ' to ' . $filePath);

                // Copy file
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                if (!copy($file, $filePath)) {
                    $this->getCommand()->getOutputStyle()->error(sprintf('Failed to copy: %s', $file->getPathname()));
                }

                // Temporary hack for now
                if ('start' === $file->getFilename() && !chmod($filePath, 0750)) {
                    $this->getCommand()->getOutputStyle()->error(sprintf('Failed to set permission: %s', $file->getPathname()));
                }
            }
        }
    }

    /**
     * Load YML resource. Stored in the resources directory in this application.
     *
     * @param string $filename
     *
     * @return mixed
     */
    public function getResource(string $filename)
    {
        $yml = new Parser();

        return $yml->parse(file_get_contents(__APP_DIR__ . '/resources/' . $filename));
    }

    /**
     * Get path to user config file.
     *
     * @return string
     */
    protected function getUserConfigFilePath(): string
    {
        $username = $this->getShellHelper()->exec('whoami');
        $path     = '/Users/' . trim($username->getOutput()) . '/.moo.yml';
        $this->getCommand()->debug('User config: ' . $path);

        return $path;
    }

    /**
     * Get path to core config file.
     *
     * @return string
     */
    protected function getCoreConfigFilePath(): string
    {
        return __APP_DIR__ . '/resources/core_config.yml';
    }

    /**
     * Load and merge configuration files.
     *
     * @return void
     */
    protected function loadConfig(): void
    {
        if (!is_null($this->config)) {
            return;
        }

        // Load user configurations
        $this->config = (new Parser())->parse(file_get_contents($this->getUserConfigFilePath()));

        // Override user configurations with core ones, if exists
        $this->loadCoreConfigIfNeeded();
    }

    /**
     * Load core config to override user configs from .moo.yml.
     *
     * @return void
     */
    protected function loadCoreConfigIfNeeded(): void
    {
        // Only load core config if file exists
        $configFile = $this->getCoreConfigFilePath();
        if (!file_exists($configFile)) {
            return;
        }

        // Get core configs
        $coreConfig = (new Parser())->parse(file_get_contents($this->getCoreConfigFilePath()));
        if (!$coreConfig) {
            return;
        }

        // Core config should not override workspace path, or faqs
        unset($coreConfig['workspace'], $coreConfig['faqs']);

        // Merge core config to override user config
        $this->config = array_merge($this->config, $coreConfig);
    }

    /**
     * Get a value from the .moo.yml file.
     *
     * @param string $name
     *
     * @return array|mixed|null
     */
    public function getConfig(string $name)
    {
        $this->loadConfig();

        if (array_key_exists($name, $this->config)) {
            return $this->config[$name];
        }

        $array = $this->config;
        foreach (explode('.', $name) as $segment) {
            if (array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return null;
            }
        }

        return $array;
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName(): string
    {
        return 'config';
    }

    /**
     * Get instance of the shell helper.
     *
     * @return ShellHelper
     */
    protected function getShellHelper(): HelperInterface
    {
        return $this->getHelperSet()->get('shell');
    }

    /**
     * Get instance of the current command line class.
     *
     * @return Command
     */
    protected function getCommand(): Command
    {
        return $this->getHelperSet()->getCommand();
    }
}
