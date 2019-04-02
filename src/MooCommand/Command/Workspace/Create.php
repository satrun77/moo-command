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

use MooCommand\Command\Workspace;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Create.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Create extends Workspace
{
    /**
     * @var string
     */
    protected $description = 'Create a new site. Create all of the files needed for the docker containers.';
    /**
     * @var string
     */
    protected $childSignature = 'new';
    /**
     * @var array
     */
    protected $arguments = [
        'name' => [
            'mode'        => InputArgument::REQUIRED,
            'description' => 'Name of the directory containing the docker/site files',
        ],
    ];

    /**
     * Main method to execute the command script.
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function fire(): void
    {
        // Validations
        $this->argumentMustNotBeEmpty('name');
        $this->siteNameMustNotEqualToProxy('name');
        $this->siteDirectoryMustNotExists('name');

        $template = $this->getQuestionHelper()->choices(
            'Please select environment template',
            $this->templates,
            1
        );

        // Select PHP version
        $phpVersion = $this->getQuestionHelper()->choices(
            'Which PHP version would you like to use',
            $this->phpVersions,
            key($this->phpVersions)
        );

        // Select SilverStripe file structure
        if ($template === 'ss') {
            $workDirectory = $this->getQuestionHelper()->choices(
                'Select work directory to use',
                $this->workDirectories,
                key($this->workDirectories)
            );
            $themeDirectory = $this->getQuestionHelper()->ask('Enter path to theme directory from site root directory');
        }

        // Site root directory
        $sitePath = $this->getConfigHelper()->getWorkspace() . $this->argument('name');

        // Collection of used hosts ports by other environments
        $usedPorts = $this->getUsedPorts();
        // Collection of used solr ports by other environments
        $usedSolrPorts = $this->getUsedPorts('SOLR_PORT');
        // Collection of used solr ports by other environments
        $usedMysqlPorts = $this->getUsedPorts('MYSQL_PORT');

        // Copy container files
        $this->getConfigHelper()->copyResource('docker/' . $template, $sitePath);

        // Update other placeholders such as, PHP version to use, values in web.env file, or docker-sync settings
        $this->updatePlaceholders($sitePath, [
            '{{php}}'          => $phpVersion,
            '{{host}}'         => $this->argument('name'),
            '{{host_port}}'    => max($usedPorts) + 1,
            '{{solr_port}}'    => max($usedSolrPorts) + 1,
            '{{mysql_port}}'   => max($usedMysqlPorts) + 1,
            '{{volume-name}}'  => str_replace('.', '', $this->argument('name')) . '_dockersync_1',
            '{{root_path}}'    => $sitePath,
            '{{name}}'         => str_replace('.', '', $this->argument('name')),
            '{{work_dir}}'     => !empty($workDirectory) ? $this->workDirectories[$workDirectory] : current($this->workDirectories),
            '{{theme_dir}}'    => !empty($themeDirectory) ? $themeDirectory : '',
        ]);

        $shell = $this->getShellHelper();
        $shell->exec('sudo chmod +x %s/start', $sitePath);
        $shell->exec('sudo chmod +x %s/php/templates/sendmail', $sitePath);

        // Display success message
        $successMessage = 'The new site files created successfully.';
        $this->getOutputStyle()->success($successMessage);

        // Show current sites
        $shell->execApplicationCommand('ws:sites');

        $this->getOutputStyle()->info('Make sure the environment configurations in the new container are correct.');

        // Ask to start container
        if ($this->getQuestionHelper()->confirmAsk('Would you like to start the new container?', false)) {
            $shell->execApplicationCommand('ws:start', [
                'name' => $this->argument('name'),
            ]);
        }

        // Update /etc/hosts
        $shell->execApplicationCommand('ws:hosts');
    }

    /**
     * Update any placeholders within the docker files.
     *
     * @param string $directory
     * @param array  $placeholders
     *
     * @return void
     */
    protected function updatePlaceholders(string $directory, array $placeholders = []): void
    {
        // Get iterator of all files within the site containers structure
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        // Update the placeholder within each file in the structure
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $this->updateFileContent($file->getPathname(), $placeholders);
            }
        }
    }

    /**
     * Update the content of a file by replacing a placeholders with their values.
     *
     * @param string $filePath
     * @param array  $changes
     */
    protected function updateFileContent(string $filePath, array $changes): void
    {
        // Get instance of SPL file
        $envFile  = new \SplFileObject($filePath, 'r');
        // Get the file size
        $size = $envFile->getSize();

        // Execute update only if the file size greater than zero
        if ($size > 0) {
            // Read the content of the file, replace placeholder
            $contents = $envFile->fread($envFile->getSize());
            $contents = strtr($contents, $changes);

            // Write new content of file replacing existing data
            $envFile = new \SplFileObject($filePath, 'w+');
            $envFile->fwrite($contents);
        }

        $envFile = null;
    }
}
