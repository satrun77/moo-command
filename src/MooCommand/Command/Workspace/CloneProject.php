<?php
/*
 * This file is part of the MooCommand package.
 *
 * (c) Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MooCommand\Command\Workspace;

use MooCommand\Command\Workspace as WorkspaceAbstract;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Parser;

/**
 * Workspace.
 *
 * moo clone [repository] [branch]
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class CloneProject extends WorkspaceAbstract
{
    /**
     * @var string
     */
    protected $description = 'Setup local development from a single YAML file within a repository (.env.yml)';
    /**
     * @var string
     */
    protected $childSignature = 'clone';
    /**
     * @var array
     */
    protected $arguments = [
        'repository' => [
            'mode'        => InputArgument::REQUIRED,
            'description' => 'Repository to clone',
        ],
        'branch'     => [
            'mode'        => InputArgument::OPTIONAL,
            'description' => 'The branch name to fetch. Default to "develop"',
            'default'     => 'develop',
        ],
    ];

    protected function cloneRepository($repository, $branch)
    {
        $this->createDirectory('./site/public');

        $status = $this->getShellHelper()->execRealTime('git clone -b %s --single-branch %s ./site/public', $branch, $repository);
        if (!$status) {
            throw new \Exception('Unable to clone repository.');
        }
    }

    protected function getEnvironmentSetup()
    {
        return (new Parser())->parse(
            file_get_contents('./site/public/.env.yml'),
            true,
            true
        );
    }

    protected function createRootFiles(array $setup)
    {
        if (!array_key_exists('files', $setup)) {
            return;
        }

        foreach ($setup['files'] as $file => $content) {
            $this->createFile($file, $content);
        }
    }

    protected function createContainers(array $setup)
    {
        if (!array_key_exists('containers', $setup)) {
            return;
        }

        foreach ($setup['containers'] as $folder => $structure) {
            $this->createDirectory($folder);
            foreach ($structure as $file => $content) {
                if ('~folder~' === $content) {
                    $this->createDirectory($folder . '/' . $file);
                } else {
                    $content = $this->populateDefaultValues($content);
                    $this->createFile($folder . '/' . $file, $content);
                }
            }
        }
    }

    protected function populateDefaultValues($content)
    {
        $data = [];
        // Update repository details
        if (false !== strpos($content, '{repository}') || false !== strpos($content, '{branch}')) {
            $data = [
                '{repository}' => $this->argument('repository'),
                '{branch}'     => $this->argument('branch'),
            ];
        }

        // Set site details
        if (false !== strpos($content, '{virtual_host}')) {
            while (!($host = $this->getQuestionHelper()->ask('Please enter your host name?'))) {
                $this->getOutputStyle()->error('Please enter a value for your host name.');
            }
            $data['{virtual_host}'] = $host;
        }

        // Set site & solr ports
        if (false !== strpos($content, '{virtual_port}')) {
            $data['{virtual_port}'] = $this->chooseSitePort();
        }
        if (false !== strpos($content, '{solr_port}')) {
            $data['{solr_port}'] = $this->chooseSolrPort();
        }

        return strtr($content, $data);
    }

    protected function createDirectory($dir)
    {
        $this->debug('Create directory: (0777) ' . $dir);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    protected function createFile($path, $content)
    {
        $this->debug('Create File: ' . $path);

        $file = new \SplFileObject($path, 'w+');

        return $file->fwrite($content);
    }

    protected function choosePort($placeholder, $newPort, $command)
    {
        $command = $this->getShellHelper()->exec($command);
        if ($command->isSuccessful()) {
            $output   = $command->getOutput();
            $ports    = explode("\n", $output);
            $maxPort1 = (int) max($ports);
            $maxPort2 = (int) max($this->getUsedPorts(strtoupper($placeholder)));
            $maxPort  = $maxPort1 > $maxPort2 ? $maxPort1 : $maxPort2;

            if ($maxPort > 0) {
                $newPort = $maxPort + 1;
            }

            return $newPort;
        }

        return $newPort;
    }

    protected function chooseSitePort()
    {
        return $this->choosePort(
            'virtual_port',
            1000,
            "docker ps | awk '/->80/' | sed 's/.*0.0.0.0:\([0-9]*\)->.*/\\1/'"
        );
    }

    protected function chooseSolrPort()
    {
        return $this->choosePort(
            'solr_port',
            8985,
            "docker ps | awk '/_solr_1/' | sed 's/.*0.0.0.0:\([0-9]*\)->.*/\\1/'"
        );
    }

    protected function fixFilesPermissions(array $setup)
    {
        if (!array_key_exists('permissions', $setup)) {
            return;
        }

        foreach ($setup['permissions'] as $file => $permission) {
            $this->getOutputStyle()->info('Fix permission for: ' . $file);
            $status = $this->getShellHelper()->exec('sudo chmod %s %s', $permission, $file);
            if (!$status->isSuccessful()) {
                $this->getOutputStyle()->error('Unable to set the permission to file: ' . $file);
            }
        }
    }

    /**
     * Main method to execute the command script.
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function fire()
    {
        $repository = $this->argument('repository');
        $branch     = $this->argument('branch');

        $this->cloneRepository($repository, $branch);

        // Environment
        $setup = $this->getEnvironmentSetup();

        // Create root files
        $this->createRootFiles($setup);

        // Create docker containers
        $this->createContainers($setup);

        // Fix files permission (ie. make executable)
        $this->fixFilesPermissions($setup);

        // Display desktop notification
        $this->notify('Clone environment', 'Repository cloned, you like to start the new environment?');

        // Ask to start container
        if ($this->getQuestionHelper()->confirmAsk('Would you like to start the new environment?', false)) {
            $this->getShellHelper()->execRealTime('./start');
        }

        $this->getOutputStyle()->success('Well Done!');
    }
}
