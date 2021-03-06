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

use MooCommand\Console\Command;

/**
 * Faq.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Faq extends Command
{
    /**
     * @var string
     */
    const DATA_SOURCE_LOCAL  = 'local';
    /**
     * @var string
     */
    const DATA_SOURCE_REMOTE = 'url';
    /**
     * @var string
     */
    protected $description = 'Display FAQs.';
    /**
     * @var string
     */
    protected $signature = 'faq';
    /**
     * @var bool
     */
    protected $runRoot = false;

    /**
     * Main method to execute the command script.
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function fire(): void
    {
        // FAQ data
        $faqs = $this->getData();

        // Ask user for a question to answer
        $question = $this->getQuestionHelper()->choices(
            'Please select a question',
            $faqs['questions'],
            0
        );

        // Display the question and the answer
        $this->getOutputStyle()->title($faqs['questions'][$question]);
        $this->getOutputStyle()->info($faqs['answers'][$question]);
    }

    /**
     * Collect FAQ questions from app, remote URL or config file.
     *
     * @return array
     */
    protected function getData(): array
    {
        // Core data
        $faqs          = $this->getConfigHelper()->getResource('faqs.yml');
        $userQuestions = $userAnswers = [];

        // Type of use question source
        $source = $this->getConfigHelper()->getConfig('faqs.source');

        // Load questions/answers from local config
        if (self::DATA_SOURCE_LOCAL === $source) {
            $userQuestions = $this->getConfigHelper()->getConfig('faqs.data.questions');
            $userAnswers   = $this->getConfigHelper()->getConfig('faqs.data.answers');
        }

        // Load question/answers from a URL
        if (self::DATA_SOURCE_REMOTE === $source) {
            $remoteQuestions = json_decode(file_get_contents($this->getConfigHelper()->getConfig('faqs.data')));
            $userQuestions   = $remoteQuestions->questions;
            $userAnswers     = $remoteQuestions->answers;
        }

        // Merge questions and answers
        foreach ($userQuestions as $index => $question) {
            $faqs['questions'][] = $question;
            $faqs['answers'][]   = $userAnswers[$index];
        }

        return $faqs;
    }
}
