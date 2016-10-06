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

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Yaml\Parser;

/**
 * Class ConfigHelper.
 *
 * @package MooCommand\Console\Helper
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
    public function getWorkspace()
    {
        return rtrim($this->getConfig('workspace'), '/') . '/';
    }

    /**
     * Get site directory name from the current path.
     *
     * @return string
     */
    public function getCurrentSiteName()
    {
        $path  = realpath(trim($this->getShellHelper()->exec('pwd')->getOutput()));
        $value = basename($path);

        if ($value === 'public') {
            $path  = realpath($path . '/../../');
            $value = basename($path);
        }

        if ($value === 'site') {
            $path  = realpath($path . '/../');
            $value = basename($path);
        }

        return $value;
    }

    /**
     * Get the docker site root path.
     *
     * @param $name
     *
     * @return string
     */
    public function getSiteRoot($name = 'name')
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
     * @param       $source
     * @param       $destination
     * @param array $excludeData
     */
    public function copyResource($source, $destination, $excludeData = [])
    {
        $directory = __APP_DIR__ . '/resources/' . $source;

        $iterator = new \RecursiveDirectoryIterator($directory);
        foreach (new \RecursiveIteratorIterator($iterator) as $file) {
            /* @var \SplFileInfo $file */
            $filePath     = $destination . str_replace($directory, '', $file->getPathname());
            $relativePath = str_replace($directory, '', $file->getPath());
            $path         = $destination . $relativePath;

            // Create directory structure
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }

            if (!in_array($relativePath, $excludeData)) {
                $this->getCommand()->debug('Copy: ' . $file->getPathname() . ' to ' . $filePath);

                // Copy file
                if (!copy($file->getPathname(), $filePath)) {
                    $this->getCommand()->getOutputStyle()->error(sprintf('Failed to copy: %s', $file->getPathname()));
                }

                // Temporary hack for now
                if ($file->getFilename() == 'start' && !chmod($filePath, 0750)) {
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
    public function getResource($filename)
    {
        $yml = new Parser();

        return $yml->parse(
            file_get_contents(__APP_DIR__ . '/resources/' . $filename),
            true,
            true
        );
    }

    /**
     * @return string
     */
    protected function getConfigFilePath()
    {
        $username = $this->getShellHelper()->exec('whoami');
        $path     = '/Users/' . trim($username->getOutput()) . '/.moo.yml';
        $this->getCommand()->debug('Config path: ' . $path);

        return $path;
    }

    protected function loadConfig()
    {
        if (!is_null($this->config)) {
            return;
        }

        $yml          = new Parser();
        $this->config = $yml->parse(
            file_get_contents($this->getConfigFilePath()),
            true,
            true
        );
    }

    /**
     * Get a value from the .moo.yml file.
     *
     * @param $name
     *
     * @return array|mixed|null
     */
    public function getConfig($name)
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
    public function getName()
    {
        return 'config';
    }

    /**
     * Get instance of the shell helper.
     *
     * @return ShellHelper
     */
    protected function getShellHelper()
    {
        return $this->getHelperSet()->get('shell');
    }

    /**
     * Get instance of the current command line class.
     *
     * @return \Symfony\Component\Console\Command\Command
     */
    protected function getCommand()
    {
        return $this->getHelperSet()->getCommand();
    }
}
