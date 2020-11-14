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

        if ($dockerTemplates = $this->getDockerTemplates()) {
            $this->buildFromTemplateDocker($dockerTemplates);
        } else {
            $this->buildFromDefaultDocker();
        }

        $this->finaliseBuild();
    }

    /**
     * Get site name used by docker
     *
     * @return string
     */
    protected function getSiteName(): string
    {
        return str_replace('.', '', $this->argument('name'));
    }

    /**
     * Get absolute path to new site
     *
     * @return string
     */
    protected function getSitePath(): string
    {
        return $this->getConfigHelper()->getWorkspace() . $this->argument('name');
    }

    /**
     * Get collection of docker templates from a remote repository
     *
     * @return array
     */
    protected function getDockerTemplates(): array
    {
        $dockerTemplates = $this->getConfigHelper()->getConfig('docker.templates');
        if (!empty($dockerTemplates)) {
            return [];
        }

        $process = $this->getShellHelper()->exec('git ls-remote %s | grep "refs/heads/" | awk \'{print $NF}\'', $dockerTemplates);
        if (!$process->isSuccessful()) {
            $this->getOutputStyle()->warning('Unable to read docker.templates');

            return [];
        }

        return array_filter(array_map('trim', explode('refs/heads/', $process->getOutput())));
    }

    /**
     * Start building site based on docker templates from a remote repository
     *
     * @param array $branches
     */
    protected function buildFromTemplateDocker(array $branches): void
    {
        $sitePath = $this->getSitePath();

        array_unshift($branches, '--skip--');
        $dockerTemplate = $this->getQuestionHelper()->choices(
            'Please select a docker template',
            $branches,
            0
        );

        if ($dockerTemplate) {
            $dockerTemplates = $this->getConfigHelper()->getConfig('docker.templates');
            $this->getShellHelper()->execRealTime('git clone %s %s --branch %s', $dockerTemplates, $sitePath, $branches[$dockerTemplate]);
        } else {
            $this->buildFromDefaultDocker();
        }
    }

    /**
     * Start building site based on default build
     */
    protected function buildFromDefaultDocker(): void
    {
        $sitePath = $this->getSitePath();
        $siteName = $this->getSiteName();

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
            $themeDirectory = $this->getQuestionHelper()->ask('Enter path to theme directory from /var/www/html/');
        }

        // PHP image name based on the selected php version - latest version not included in the image name
        $phpImage = 'mo_php' . ($phpVersion !== key($this->phpVersions) ? str_replace('.', '', $phpVersion) : '');
        // Collection of used hosts ports by other environments
        $usedPorts = $this->getWebEnvData();
        $usedPorts = !empty($usedPorts)? [1000] : $usedPorts;
        // Collection of used solr ports by other environments
        $usedSolrPorts = $this->getWebEnvData('SOLR_PORT');
        $usedSolrPorts = !empty($usedSolrPorts)? [9000] : $usedSolrPorts;
        // Collection of used solr ports by other environments
        $usedMysqlPorts = $this->getWebEnvData('MYSQL_PORT');
        $usedMysqlPorts = !empty($usedMysqlPorts)? [3000] : $usedMysqlPorts;

        // Copy container files
        $this->getConfigHelper()->copyResource('docker/' . $template, $sitePath);

        // Update other placeholders such as, PHP version to use, values in web.env file, or docker-sync settings
        $this->updatePlaceholders($sitePath, [
            '{{php}}'         => $phpVersion,
            '{{php_image}}'   => $phpImage,
            '{{host}}'        => $this->argument('name'),
            '{{host_port}}'   => max($usedPorts) + 1,
            '{{solr_port}}'   => max($usedSolrPorts) + 1,
            '{{mysql_port}}'  => max($usedMysqlPorts) + 1,
            '{{volume-name}}' => $siteName . '_dockersync_1',
            '{{root_path}}'   => $sitePath,
            '{{name}}'        => $siteName,
            '{{work_dir}}'    => !empty($workDirectory) ? $this->workDirectories[$workDirectory] : current($this->workDirectories),
            '{{theme_dir}}'   => !empty($themeDirectory) ? $themeDirectory : '',
        ]);

        // Custom setup for PHP 7.3
        if ($phpVersion === static::PHP_73) {
            $shell = $this->getShellHelper();
            // Delete default php docker setup and use php7.3 specific setup
            $shell->exec('rm -rf %s/php/Dockerfile', $sitePath);
            $shell->exec('mv %s/php/Dockerfile7.3 %s/php/Dockerfile', $sitePath, $sitePath);
        }
    }

    /**
     * Final steps.
     * Display confirmation message and option to start the new site
     *
     * @throws \Exception
     */
    protected function finaliseBuild(): void
    {
        // Site root directory
        $sitePath = $this->getSitePath();
        // Site name
        $siteName = $this->getSiteName();

        $shell = $this->getShellHelper();
        $shell->exec('sudo chmod +x %s/start', $sitePath);
        $shell->exec('sudo chmod +x %s/php/templates/sendmail', $sitePath);
        $shell->exec('sudo chmod +x %s/php/templates/permission', $sitePath);
        $shell->exec('sudo chmod +x %s/permission', $sitePath);

        // Display success message
        $successMessage = 'The new site files created successfully.';
        $this->getOutputStyle()->success($successMessage);

        // Show current sites
        $shell->execApplicationCommand('ws:sites', ['container' => $siteName]);

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
        $envFile = new \SplFileObject($filePath, 'r');
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
