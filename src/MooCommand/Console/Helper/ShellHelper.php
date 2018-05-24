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
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Process\Process;

/**
 * Class ShellHelper.
 */
class ShellHelper extends Helper
{
    /**
     * Execute a command from the current application.
     *
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     */
    public function execApplicationCommand($name, array $args = [])
    {
        $command = $this->getCommand()->getApplication()->find($name);
        $input   = new ArrayInput($args);

        return $command->run($input, $this->getCommand()->getOutput());
    }

    /**
     * Whether or not a command line installed in user machine.
     *
     * @param $name
     *
     * @return mixed
     */
    public function isCommandInstall($name)
    {
        return $this->exec('which %s', $name)->isSuccessful();
    }

    /**
     * Execute a shell command.
     *
     * @param array ...$params
     *
     * @return Process
     */
    public function exec(...$params)
    {
        $command = $this->sprintf($params);
        $process = new Process($command);
        $process->setTimeout(null);

        $this->getCommand()->debug('Command executed: ' . $command);

        $process->run();

        $this->getCommand()->debug('Command output(' . $process->getExitCode() . '):');
        $this->getCommand()->debug($process->getOutput());

        return $process;
    }

    /**
     * Execute a shell command with real time output.
     *
     * @param array ...$params
     *
     * @return bool
     */
    public function execRealTime(...$params)
    {
        $command = $this->sprintf($params);
        $return  = 0;
        $this->getCommand()->debug('Command executed: ' . $command);

        $this->getCommand()->getOutputStyle()->block('Start process...', null, 'bg=cyan;fg=black');
        passthru($command, $return);
        $this->getCommand()->getOutputStyle()->block('Process complete', null, 'bg=cyan;fg=black');

        return 0 === (int) $return;
    }

    /**
     * @param $args
     *
     * @return string
     */
    protected function sprintf($args)
    {
        if (count($args) > 1) {
            return call_user_func_array('sprintf', $args);
        }

        return $args[0];
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName()
    {
        return 'shell';
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
