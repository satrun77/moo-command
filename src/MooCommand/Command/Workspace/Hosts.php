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
 * Hosts.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Hosts extends WorkspaceAbstract
{
    /**
     * @var string
     */
    protected $description = 'Update the host file in user machine (/etc/hosts).';
    /**
     * @var string
     */
    protected $childSignature = 'hosts';

    /**
     * Main method to execute the command script.
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function fire()
    {
        $sites   = implode(' ', array_keys($this->getUsedPorts()));
        $ip      = $this->getMachineIp();
        $domains = $ip . '    ' . $sites . ' # Moo workspace';

        $this->getShellHelper()->exec('sudo sed -i.bk \'/# Moo workspace/d\' /etc/hosts');
        $this->getShellHelper()->exec('sudo -- sh -c -e "echo \'%s\' >> /etc/hosts"', $domains);

        $this->getOutputStyle()->success('Done.');
    }
}
