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

use Exception;
use Joli\JoliNotif\Notification;
use Joli\JoliNotif\NotifierFactory;
use MooCommand\Console\Helper\ConfigHelper;
use MooCommand\Console\Helper\QuestionHelper;
use MooCommand\Console\Helper\ShellHelper;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends SymfonyCommand
{
    /**
     * @var bool
     */
    protected $runRoot = true;

    /**
     * The input interface implementation.
     *
     * @var InputInterface
     */
    protected $input;

    /**
     * The output interface implementation.
     *
     * @var ConsoleOutput
     */
    protected $output;

    /**
     * The stdErr output interface implementation.
     *
     * @var OutputInterface
     */
    protected $errorOutput;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description;

    /**
     * List of arguments of the console command.
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * List of options of the console command.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Get the value of a command argument.
     *
     * @return null|string|string[]
     */
    public function argument(string $key)
    {
        return $this->input->getArgument($key);
    }

    /**
     * Get the value of a command option.
     *
     * @return null|bool|string|string[]
     */
    public function option(string $key)
    {
        if ($this->input instanceof InputInterface) {
            return $this->input->getOption($key);
        }

        return false;
    }

    /**
     * Get the output implementation.
     */
    public function getOutput(): ConsoleOutput
    {
        return $this->output;
    }

    public function getInput(): InputInterface
    {
        return $this->input;
    }

    /**
     * Get the stdErr output implementation.
     */
    public function getErrorOutput(): OutputInterface
    {
        if (!$this->errorOutput instanceof ConsoleOutputInterface) {
            $this->errorOutput = $this->getOutput()->getErrorOutput();
        }

        return $this->errorOutput;
    }

    /**
     * Run the console command.
     *
     * @throws Exception
     */
    public function run(InputInterface $input, OutputInterface $output): int
    {
        global $argv;

        $this->input  = $input;
        $this->output = $output;

        // Pass this command to the helper set
//        $this->getHelperSet()->setCommand($this);

        // Default $stdErr variable to output
        $this->errorOutput = $this->getOutput();

        if ($this->getOutput() instanceof ConsoleOutputInterface) {
            // If it's available, get stdErr output
            $this->errorOutput = $this->getOutput()->getErrorOutput();
        }

        if ($this->runRoot && 0 !== posix_getuid()) {
            throw new Exception("Execute the moo command with sudo\nsudo " . implode(' ', $argv));
        }

        return parent::run($input, $output);
    }

    /**
     * @param array|string $message
     */
    public function debug($message): void
    {
        if ($this->option('verbose')) {
            $this->getOutputStyle()->debug($message);
        }
    }

    public function getOutputStyle(): StyledOutput
    {
        return new StyledOutput($this->input, $this->getOutput());
    }

    protected function configure(): void
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

    protected function getConfigHelper(): ConfigHelper
    {
        $helper = $this->getHelper('config');
        $helper->setCommand($this);

        return $helper;
    }

    protected function getShellHelper(): ShellHelper
    {
        $helper = $this->getHelper('shell');
        $helper->setCommand($this);

        return $helper;
    }

    public function getQuestionHelper(): QuestionHelper
    {
        $helper = $this->getHelper('moo_question');
        $helper->setCommand($this);

        return $helper;
    }

    /**
     * @return int 0 on successful, 1 on error
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->fire();
        } catch (Exception $e) {
            $this->stdErrError($e->getMessage());

            return 1;
        }

        return 0;
    }

    protected function stdErrError(string $message): void
    {
        $this->getErrorOutput()->writeln($this->getOutputStyle()->formatLine($message, 'error', 'ERROR'));
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        foreach ($this->arguments as $name => $argument) {
            $method = 'interactInput' . ucfirst($name);
            if (method_exists($this, $method)) {
                $value = $this->{$method}($this->argument($name), $argument);
                $this->input->setArgument($name, $value);
            }
        }
    }

    protected function notify(string $text, string $body): void
    {
        // Create a Notifier
        $notifier = NotifierFactory::create();

        // Create notification - Sound only works on macOS (AppleScriptNotifier)
        $notification = (new Notification())
            ->setTitle($text)
            ->setBody($body)
            ->addOption('sound', 'Frog');

        // Send it
        $notifier->send($notification);
    }

    abstract protected function fire(): void;
}
