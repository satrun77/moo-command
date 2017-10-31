<?php
/*
 * This file is part of the MooCommand package.
 *
 * (c) Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MooCommand\Command;

use MooCommand\Command\Commit\CommitStyleInterface;
use MooCommand\Console\Command;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Commit.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Commit extends Command
{
    const SHORTCUT_DEPENDENCIES = 'dependencies';
    const SHORTCUT_GITIGNORE    = 'gitignore';
    const SHORTCUT_CSFIXES      = 'csfixes';

    /**
     * @var bool
     */
    protected $runRoot = false;
    /**
     * @var string
     */
    protected $description = 'Git Commit wrapper to standardise the commit messages ( %s ).';
    /**
     * @var string
     */
    protected $signature = 'commit';

    /**
     * Hold data related to commit.
     *
     * @var array
     */
    protected $commit = [];

    /**
     * @var array
     */
    protected $options = [
        'oneline'                   => [
            'shortcut'    => 'o',
            'mode'        => InputOption::VALUE_NONE,
            'description' => 'Option to skip asking for the optional details.',
            'default'     => null,
        ],
        self::SHORTCUT_DEPENDENCIES => [
            'shortcut'    => 'd',
            'mode'        => InputOption::VALUE_NONE,
            'description' => 'Shortcut commit changes with default message about updating composer.json & composer.lock',
            'default'     => null,
        ],
        self::SHORTCUT_GITIGNORE    => [
            'shortcut'    => 'i',
            'mode'        => InputOption::VALUE_NONE,
            'description' => 'Shortcut commit changes with default message about updating .gitignore',
            'default'     => null,
        ],
        self::SHORTCUT_CSFIXES      => [
            'shortcut'    => 'c',
            'mode'        => InputOption::VALUE_NONE,
            'description' => 'Shortcut commit changes with default message about CS fixes',
            'default'     => null,
        ],
    ];

    /**
     * @var CommitStyleInterface
     */
    protected $style;

    /**
     * Commit constructor.
     *
     * @param null|string $name
     * @param Application $application
     */
    public function __construct($name, Application $application)
    {
        $this->setHelperSet($application->getHelperSet());
        $this->getHelperSet()->setCommand($this);

        // Merge style configurations with commit default
        $this->description = sprintf($this->description, $this->getStyle()->getDisplayName());
        $this->options     = array_merge($this->getStyle()->getOptions(), $this->options);

        parent::__construct($name);
    }

    /**
     * Initializes the command just after the input has been validated.
     * Check for special options that will auto commit changes with default message. (ie. update composer.json).
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if ($shortcutOption = $this->hasShortcutOption()) {
            // Message to print in console
            $this->callStyleOptionalAction('beforeShortcut', $shortcutOption);
        }
    }

    /**
     * Starts console interactive.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $shortcutOption = $this->hasShortcutOption();
        $arguments      = $this->getStyle()->getArguments();
        foreach ($arguments as $argument) {
            // If we are editing a message and we have a shortcut enabled, then skip interaction and display message
            // else interaction enabled if shortcut is disabled or editing details
            if ($argument === 'Message' && $shortcutOption) {
                $value = $this->getStyle()->getShortcutMessage($shortcutOption);
                $this->getOutputStyle()->separator('_', 'comment');
                $this->getOutputStyle()->line($value, 'comment', 'Commit');
                $this->getOutputStyle()->separator('_', 'comment');
            } elseif ($argument === 'Details' || !$shortcutOption) {
                $method = 'interactInput' . $argument;
                $this->callStyleOptionalAction('beforeInput' . $argument);
                $caller = method_exists($this->getStyle(), $method) ? $this->getStyle() : $this;
                $value  = $caller->$method();
            }

            // Set argument value if we have one
            if (!empty($value)) {
                $this->setArgument(strtolower($argument), $value);
            }
        }
    }

    /**
     * Main method to execute the command script.
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function fire()
    {
        $this->confirmStagedFiles();

        // Commit message details
        $message = $this->argument('message');
        $details = wordwrap($this->argument('details'), 70);

        // Execute git commit
        $commit  = $this->getStyle()->getCommitCommand($message, $details);
        $command = $this->getShellHelper()->exec(...$commit);
        if (!$command->isSuccessful()) {
            $this->getOutputStyle()->error('Failed to commit!');

            return;
        }

        // Success message
        $this->getOutputStyle()->success('Changes committed!');

        // Show git status
        $status = $this->getShellHelper()->exec('git status');
        $this->getOutputStyle()->comment($status->getOutput());
    }

    /**
     * Get an instance of the commit style class.
     *
     * @return CommitStyleInterface
     */
    protected function getStyle()
    {
        if (is_null($this->style)) {
            $class       = __NAMESPACE__ . '\\Commit\\' . $this->getConfigHelper()->getConfig('commit.style');
            $this->style = new $class($this);
        }

        return $this->style;
    }

    /**
     * Return list of short cut options.
     *
     * @return array
     */
    protected function getShortcutOptions()
    {
        return array_merge([
            self::SHORTCUT_CSFIXES,
            self::SHORTCUT_DEPENDENCIES,
            self::SHORTCUT_GITIGNORE,
        ], $this->getStyle()->getShortcutOptions());
    }

    /**
     * Whether we have a short cut option to execute or not.
     *
     * @return bool|string
     */
    protected function hasShortcutOption()
    {
        foreach ($this->getShortcutOptions() as $shortcut) {
            // Disable interactive we have a default message
            if ($this->option($shortcut)) {
                return $shortcut;
            }
        }

        return false;
    }

    /**
     * Set an argument value.
     *
     * @param string $name
     * @param string $value
     *
     * @return $this
     */
    protected function setArgument($name, $value)
    {
        $this->commit[$name] = $value;

        return $this;
    }

    /**
     * Get an argument value by its name.
     *
     * @param string $key
     *
     * @return string|null
     */
    public function argument($key = null)
    {
        if (array_key_exists($key, $this->commit)) {
            return $this->commit[$key];
        }

        return null;
    }

    /**
     * Display all of the staged files and ask the user to confirm.
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function confirmStagedFiles()
    {
        // Get list of staged files
        $command = $this->getShellHelper()->exec('git diff --name-only --cached');
        if (empty($command->getOutput())) {
            throw new \Exception('There are no files to commit.');
        }

        // Ask the user if these are the correct changes to commit
        $question = [
            'Are these the files you have changed & would like to commit them?',
            $command->getIterator(),
        ];
        $status = $this->getQuestionHelper()->confirmAsk($question);
        if (!$status) {
            throw new \Exception('Commit aborted by user.');
        }

        return $command;
    }

    /**
     * Interactive input to be executed by parent method
     * Ask & validate the commit type.
     *
     * @return mixed
     */
    protected function interactInputMessage()
    {
        $question = $this->getOutputStyle()->question('Enter Commit Message: ');

        return $this->validator($question, 'Message');
    }

    /**
     * Interactive input to be executed by parent method
     * Ask for the commit message details.
     *
     * @return mixed
     */
    protected function interactInputDetails()
    {
        if (!$this->option('oneline')) {
            $question = $this->getOutputStyle()->question('Enter Commit Details (optional): ');

            return $this->validator($question, 'Details');
        }

        return null;
    }

    /**
     * Get an array of all of the input validators.
     *
     * @return array
     */
    protected function getValidators()
    {
        return array_merge([
            'Message.Length' => $this,
        ], $this->getStyle()->getValidators());
    }

    /**
     * Execute ask and validate on all validators for a question.
     *
     * @param string $question
     * @param string $type
     *
     * @return mixed
     */
    public function validator($question, $type)
    {
        return $this->getHelper('dialog')->askAndValidate($this->getOutput(), $question, function ($value) use ($type) {
            foreach ($this->getValidators() as $name => $validator) {
                if (strpos($name, $type . '.') === 0) {
                    $method = 'validate' . str_replace('.', '', $name);
                    $value = $validator->{$method}($value);
                }
            }

            return $value;
        });
    }

    /**
     * Validate the message length. It must not be more than 60 chars.
     *
     * @param string $value
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    protected function validateMessageLength($value)
    {
        // Check the message size
        if (strlen($value) > 60) {
            $validLength = substr($value, 0, 60);
            $extraLength = substr($value, 60);
            $this->getOutputStyle()->warning($validLength . '<fg=red>' . $extraLength . '</>');
            throw new \InvalidArgumentException('Commit message must not be more than 60 characters.');
        }

        // Must not be empty
        if (empty($value)) {
            throw new \InvalidArgumentException('Commit message must not be empty.');
        }

        return $value;
    }

    /**
     * Call an optional method from the commit style class.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    protected function callStyleOptionalAction($method, ...$arguments)
    {
        if (method_exists($this->getStyle(), $method)) {
            return $this->getStyle()->$method(...$arguments);
        }

        return false;
    }
}
