<?php

/*
 * This file is part of the MooCommand package.
 *
 * (c) Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MooCommand\Console;

use MooCommand\Console\Helper\ConfigHelper;
use MooCommand\Console\Helper\ShellHelper;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Command.
 *
 * @package MooCommand\Console
 */
class Command extends SymfonyCommand
{
    /**
     * @var bool
     */
    protected $runRoot = true;
    /**
     * The input interface implementation.
     *
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;
    /**
     * The output interface implementation.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * The stdErr output interface implementation.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $errorOutput;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '';
    protected $description;

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @var array
     */
    protected $options = [

    ];

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName($this->signature)
            ->setDescription($this->description);

        foreach ($this->arguments as $name => $argument) {
            if (!array_key_exists('default', $argument)) {
                $argument['default'] = null;
            }
            $this->addArgument($name, $argument['mode'], $argument['description'], $argument['default']);
        }

        foreach ($this->options as $name => $option) {
            $option = array_merge([
                'shortcut'    => null,
                'mode'        => InputOption::VALUE_NONE,
                'description' => '',
                'default'     => null,
            ], $option);
            $this->addOption($name, $option['shortcut'], $option['mode'], $option['description'], $option['default']);
        }
    }

    /**
     * @return ConfigHelper
     */
    protected function getConfigHelper()
    {
        return $this->getHelper('config');
    }

    /**
     * @return ShellHelper
     */
    protected function getShellHelper()
    {
        return $this->getHelper('shell');
    }

    /**
     * @return \MooCommand\Console\Helper\QuestionHelper
     */
    protected function getQuestionHelper()
    {
        return $this->getHelper('moo_question');
    }

    /**
     * Get the value of a command argument.
     *
     * @param string $key
     *
     * @return string|array
     */
    public function argument($key = null)
    {
        if (is_null($key)) {
            return $this->input->getArguments();
        }

        return $this->input->getArgument($key);
    }

    /**
     * Get the value of a command option.
     *
     * @param string $key
     *
     * @return string|array
     */
    public function option($key = null)
    {
        if (is_null($key)) {
            return $this->input->getOptions();
        }

        return $this->input->getOption($key);
    }

    /**
     * Get the output implementation.
     *
     * @return \Symfony\Component\Console\Output\OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return InputInterface
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Get the stdErr output implementation.
     *
     * @return \Symfony\Component\Console\Output\OutputInterface
     */
    public function getErrorOutput()
    {
        if (!$this->errorOutput instanceof ConsoleOutputInterface) {
            $this->errorOutput = $this->getOutput()->getErrorOutput();
        }

        return $this->errorOutput;
    }

    /**
     * Run the console command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * @throws \Exception
     * @throws \Symfony\Component\Console\Exception\ExceptionInterface
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        global $argv;

        $this->input  = $input;
        $this->output = $output;

        // Default $stdErr variable to output
        $this->errorOutput = $this->getOutput();

        if ($this->getOutput() instanceof ConsoleOutputInterface) {
            // If it's available, get stdErr output
            $this->errorOutput = $this->getOutput()->getErrorOutput();
        }

        if ($this->runRoot && posix_getuid() != 0) {
            throw new \Exception("Execute the moo command with sudo\nsudo " . implode(' ', $argv));
        }

        return parent::run($input, $output);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            // Pass this command to the helper set
            $this->getHelperSet()->setCommand($this);

            $this->fire();
        } catch (\Exception $e) {
            $this->stdErrError($e->getMessage());

            return 1;
        }

        return 0;
    }

    /**
     * @param $message
     *
     * @return void
     */
    public function debug($message)
    {
        if ($this->option('verbose')) {
            $this->getOutputStyle()->debug($message);
        }
    }

    /**
     * @return StyledOutput
     */
    public function getOutputStyle()
    {
        return new StyledOutput($this->input, $this->getOutput());
    }

    /**
     * @param $message
     *
     * @return void
     */
    protected function stdErrError($message)
    {
        $this->getErrorOutput()->writeln($this->getOutputStyle()->formatLine($message, 'error', 'ERROR'));
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->arguments as $name => $argument) {
            $method = 'interactInput' . ucfirst($name);
            if (method_exists($this, $method)) {
                $value = $this->$method($this->argument($name), $argument);
                $this->input->setArgument($name, $value);
            }
        }
    }

    /**
     * @return void
     */
    protected function fire()
    {
    }
}