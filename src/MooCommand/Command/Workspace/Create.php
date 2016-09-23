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

/**
 * Create.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Create extends WorkspaceAbstract
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
            'mode'        => InputArgument::OPTIONAL,
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
    protected function fire()
    {
        // Validations
        $this->argumentMustNotBeEmpty('name');
        $this->siteNameMustNotEqualToProxy('name');
        $this->siteDirectoryMustNotExists('name');

        $template = $this->getHelper('dialog')->select(
            $this->getOutput(),
            $this->getOutputStyle()->formatLine('Please select environment template', 'question', 'QUESTION'),
            $this->templates,
            1
        );

        // Site root directory
        $sitePath = $this->getConfigHelper()->getWorkspace() . $this->argument('name');

        // Copy container files
        $this->getConfigHelper()->copyResource('docker/' . $template, $sitePath);

        // Update web.env file
        $this->updateWebEnvFile($sitePath);

        $shell = $this->getShellHelper();
        $shell->exec('sudo chmod +x %s/start', $sitePath);
        $shell->exec('sudo chmod +x %s/frontend/build', $sitePath);
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
     * Update host and port in web.env file.
     *
     * @param string $sitePath
     *
     * @return bool
     */
    protected function updateWebEnvFile($sitePath)
    {
        // Highest port plus 1 && site name
        $port = max($this->getUsedPorts()) + 1;
        $name = $this->argument('name');

        // Open file for reading & replace host & port
        $envFile  = new \SplFileObject($sitePath . '/env/web.env', 'r');
        $contents = $envFile->fread($envFile->getSize());
        $contents = strtr($contents, [
            'site1' => $name,
            '1000'  => $port,
        ]);
        $envFile = null;

        // Write new content of file replacing existing data
        $envFile = new \SplFileObject($sitePath . '/env/web.env', 'w+');
        $envFile->fwrite($contents);
        $envFile = null;

        return true;
    }
}
