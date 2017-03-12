<?php

/*
 * This file is part of the MooCommand package.
 *
 * (c) Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MooCommand\Command\Commit;

use MooCommand\Command\Commit;

/**
 * Class ImperativeMoodStyle.
 */
class ImperativeMoodStyle implements CommitStyleInterface
{
    /**
     * List of short cut messages.
     *
     * @var array
     */
    protected $shortcutMessages = [
        Commit::SHORTCUT_DEPENDENCIES => 'Update Composer dependencies',
        Commit::SHORTCUT_GITIGNORE    => 'Update .gitignore',
        Commit::SHORTCUT_CSFIXES      => 'Apply CS fixes',
    ];

    /**
     * List of commit allowed words.
     *
     * @var array
     */
    protected $types;

    /**
     * Instance of commit command class.
     *
     * @var Commit
     */
    protected $command;

    /**
     * ImperativeMoodStyle constructor.
     *
     * @param Commit $command
     */
    public function __construct(Commit $command)
    {
        $this->command = $command;
    }

    /**
     * Return the display name of the commit style.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return 'Imperative Mood';
    }

    /**
     * Return list of arguments for the command.
     *
     * @return array
     */
    public function getArguments()
    {
        return [
            'Message', 'Details',
        ];
    }

    /**
     * Return list of extra options to be added to the commit defaults.
     *
     * @return array
     */
    public function getOptions()
    {
        return [];
    }

    /**
     * Return list of short cut options.
     *
     * @return array
     */
    public function getShortcutOptions()
    {
        return [];
    }

    /**
     * Return list extra validators.
     *
     * [Input name].[Validator name] => class containing the validator.
     * (ie. 'Issue.Number' for method validateIssueNumber)
     *
     * @return array
     */
    public function getValidators()
    {
        return [
            'Message.ImperativeMood' => $this,
        ];
    }

    /**
     * Return message for a short option.
     *
     * @param string $shortcutOption
     *
     * @return string
     */
    public function getShortcutMessage($shortcutOption)
    {
        return $this->shortcutMessages[$shortcutOption];
    }

    /**
     * Return details for a short option.
     *
     * @param $shortcutOption
     *
     * @return string
     */
    public function getShortcutDetails($shortcutOption)
    {
        return '';
    }

    /**
     * Return an array of arguments for the commit command.
     *
     * @param string $message
     * @param string $details
     *
     * @return array
     */
    public function getCommitCommand($message, $details)
    {
        return [
            "git commit -m \"%s\n\n%s\"",
            ucfirst($message),
            $details,
        ];
    }

    /**
     * Execute code before interactive input message.
     *
     * @return void
     */
    public function beforeInputMessage()
    {
        $this->command->getOutputStyle()->info('Acceptable words to start commit with: ');
        $this->command->getOutputStyle()->line('If applied, this commit will ....your commit....', 'comment');

        $rows = array_chunk($this->getTypes(), 7);
        $this->command->getOutputStyle()->table([], $rows);
    }

    /**
     * Validate the first word of the input message.
     *
     * @param string $value
     *
     * @return string
     */
    public function validateMessageImperativeMood($value)
    {
        $imperativeMood = false;
        foreach ($this->types as $type) {
            if (stripos($value, $type . ' ') === 0) {
                $imperativeMood = true;
                break;
            }
        }

        if (!$imperativeMood) {
            $this->command->getOutputStyle()->error([
                'Commit message should start with an imperative mood.',
                'It should be able to complete the following sentence:',
                'If applied, this commit will <fg=green;bg=red>' . $value . '</fg=green;bg=red>',
            ]);
            $this->command->getOutputStyle()->note('If you think you are correct, then ask the developer to fix it.');
            throw new \InvalidArgumentException('Please try again');
        }

        return $value;
    }

    /**
     * Get an array of allowed commit words.
     *
     * @return array
     */
    public function getTypes()
    {
        if (null === $this->types) {
            $this->types = $this->command->getHelper('config')->getConfig('commit.words');
            sort($this->types, SORT_NATURAL);
        }

        return $this->types;
    }
}
