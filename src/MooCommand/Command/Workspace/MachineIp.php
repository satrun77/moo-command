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

/**
 * MachineIp.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class MachineIp extends WorkspaceAbstract
{
    /**
     * @var string
     */
    protected $description = 'Display the docker machine IP address.';
    /**
     * @var string
     */
    protected $childSignature = 'ip';

    /**
     * Main method to execute the command script.
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function fire()
    {
        $ip = $this->getMachineIp();

        if (false === $ip) {
            return $this->getOutputStyle()->error('Unable to get docker ip');
        }

        $this->getOutputStyle()->success($ip);
    }
}
