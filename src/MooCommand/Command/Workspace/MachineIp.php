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

use Exception;
use MooCommand\Command\Workspace;

/**
 * MachineIp.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class MachineIp extends Workspace
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
     * @throws Exception
     */
    protected function fire(): void
    {
        $ip = $this->getMachineIp();

        if ($ip === '') {
            $this->getOutputStyle()->error('Unable to get docker ip');

            return;
        }

        $this->getOutputStyle()->success($ip);
    }
}
