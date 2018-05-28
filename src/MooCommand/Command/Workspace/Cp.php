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
 * Cp.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Cp extends WorkspaceAbstract
{
    /**
     * @var string
     */
    protected $description = 'Copy a file from host machine to a docker container or download from a docker container. A wrapper to docker cp command.';
    /**
     * @var string
     */
    protected $childSignature = 'cp';
    /**
     * @var array
     */
    protected $arguments = [
        'name'      => [
            'mode'        => InputArgument::REQUIRED,
            'description' => 'Name of the directory containing the docker/site files',
        ],
        'container' => [
            'mode'        => InputArgument::REQUIRED,
            'description' => 'Name of the container to upload file to or download file from',
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
        $this->siteDirectoryMustExists('name');
        $site = $this->argument('name');
        // Docker prefix can't have "."
        $site      = str_replace('.', '', $site);
        $container = $this->argument('container');

        // Ask to Upload or download?
        $isUpload = $this->getQuestionHelper()->choices(
            'What would you like to do?',
            ['Upload', 'Download'],
            0
        );
        $action = 0 == $isUpload ? 'upload' : 'download';

        // Ask what is the path of the file you want to copy.
        $copyFrom = $this->getQuestionHelper()->ask('Enter the path of the file you want to copy: ');

        // Ask where you want to copy the file to.
        $copyTo = $this->getQuestionHelper()->ask('Enter the path to where you want to copy the file to: ');

        $params = ['docker cp %s_%s_1:%s %s', $site, $container, $copyFrom, $copyTo];
        if (0 == $isUpload) {
            $params = ['docker cp %s %s_%s_1:%s', $copyFrom, $site, $container, $copyTo];
        }
        $copy = $this->getShellHelper()->execRealTime(...$params);

        if (!$copy) {
            return $this->getOutputStyle()->error('Unable to ' . $action . ' file.');
        }

        // Success message
        $successMessage = 'The file ' . $action . 'ed successfully.';
        $this->getOutputStyle()->success($successMessage);
    }
}
