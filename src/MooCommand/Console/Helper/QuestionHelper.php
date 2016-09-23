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
use Symfony\Component\Console\Helper\QuestionHelper as BaseQuestionHelper;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class QuestionHelper.
 *
 * @package MooCommand\Console\Helper
 */
class QuestionHelper extends Helper
{
    /**
     * Get instance of symfony question helper.
     *
     * @return BaseQuestionHelper
     */
    protected function getHelper()
    {
        return $this->getHelperSet()->get('question');
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

    /**
     * @param string $question
     * @param int    $default
     *
     * @return mixed
     */
    public function confirmAsk($question, $default = 0)
    {
        $command = $this->getCommand();
        $command->getOutputStyle()->question($question);
        $yesNoString = $command->getOutputStyle()->formatLine('[yes/no]', 'fg=green');
        $question    = new ConfirmationQuestion($yesNoString . "\n> ", (boolean) $default);

        return $this->getHelper()->ask($command->getInput(), $command->getOutput(), $question);
    }

    /**
     * @param $question
     *
     * @return mixed
     */
    public function ask($question)
    {
        $command = $this->getCommand();
        $command->getOutputStyle()->question($question);
        $question = new Question('> ');

        return $this->getHelper()->ask($command->getInput(), $command->getOutput(), $question);
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName()
    {
        return 'moo_question';
    }
}
