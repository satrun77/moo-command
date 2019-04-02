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

/**
 * Interface CommitStyleInterface.
 */
interface CommitStyleInterface
{
    /**
     * Return the display name of the commit style.
     *
     * @return string
     */
    public function getDisplayName(): string;

    /**
     * Return list of arguments for the command.
     *
     * @return array
     */
    public function getArguments(): array;

    /**
     * Return list of extra options to be added to the commit defaults.
     *
     * @return array
     */
    public function getOptions(): array;

    /**
     * Return list of short cut options.
     *
     * @return array
     */
    public function getShortcutOptions(): array;

    /**
     * Return list extra validators.
     *
     * [Input name].[Validator name] => class containing the validator.
     * (ie. 'Issue.Number' for method validateIssueNumber)
     *
     * @return array
     */
    public function getValidators(): array;

    /**
     * Return message for a short option.
     *
     * @param string $shortcutOption
     *
     * @return string
     */
    public function getShortcutMessage(string $shortcutOption): string;

    /**
     * Return details for a short option.
     *
     * @param string $shortcutOption
     *
     * @return string
     */
    public function getShortcutDetails(string $shortcutOption): string;

    /**
     * Return an array of arguments for the commit command.
     *
     * @param string $message
     * @param string $details
     *
     * @return array
     */
    public function getCommitCommand(string $message, string $details): array;
}
