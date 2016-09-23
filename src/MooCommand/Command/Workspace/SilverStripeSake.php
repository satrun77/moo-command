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
 * Build.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class SilverStripeSake extends WorkspaceAbstract
{
    /**
     * @var string
     */
    protected $description = 'Execute SilverStripe Sake command inside the php container.';
    /**
     * @var string
     */
    protected $childSignature = 'sake';
    /**
     * @var array
     */
    protected $arguments = [
        'argument' => [
            'mode'        => InputArgument::OPTIONAL,
            'description' => 'Argument to pass to SilverStripe Sake command',
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
        $argument = $this->argument('argument');
        if (empty($argument) || $argument === 'dev') {
            $argument = 'dev/build "flush=1"';
        }

        $this->execCommandInContainer('chmod', '+x ./framework/sake', 'php');
        $this->execCommandInContainer('./framework/sake', $argument, 'php', 'Unable to execute sake command inside the php container.');
    }
}
