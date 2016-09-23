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

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class StyledOutput.
 *
 * @package MooCommand\Console
 */
class StyledOutput extends SymfonyStyle
{
    protected $outputMinWidth = 120;

    /**
     * @param InputInterface         $input
     * @param Output\OutputInterface $output
     */
    public function __construct(InputInterface $input, Output\OutputInterface $output)
    {
        parent::__construct($input, $output);

        // Windows cmd wraps lines as soon as the terminal width is reached, whether there are following chars or not.
        $this->outputMinWidth = min($this->getTerminalWidth() - (int) (DIRECTORY_SEPARATOR === '\\'), self::MAX_LINE_LENGTH);
    }

    /**
     * @param                 $string
     * @param null|int|string $verbosity
     *
     * @return void
     */
    public function success($string, $verbosity = null)
    {
        $this->loadStyle('success', ['default', 'green', ['bold']]);
        $this->line($string, 'success', 'SUCCESS', $verbosity);
    }

    /**
     * @param                 $string
     * @param null|int|string $verbosity
     *
     * @return void
     */
    public function debug($string, $verbosity = null)
    {
        $this->loadStyle('debug', ['yellow', 'black', ['bold']]);
        $this->line($string, 'debug', 'DEBUG', $verbosity);
    }

    /**
     * Format input to textual table.
     *
     * @param array $headers
     * @param array $rows
     *
     * @return void
     */
    public function table(array $headers, array $rows)
    {
        $style = clone Table::getStyleDefinition('default');
        $style->setCellHeaderFormat('<info>%s</info>');

        $table = new Table($this);
        $table->setHeaders($headers)->setRows($rows)->setStyle($style)->render();
    }

    /**
     * Write a string as information output.
     *
     * @param string $string
     *
     * @return void
     */
    public function info($string)
    {
        $this->loadStyle('info2', ['cyan', 'default']);
        $this->line($string, 'info2', 'INFO');
    }

    /**
     * Write a string as comment output.
     *
     * @param string          $string
     * @param null|int|string $verbosity
     *
     * @return void
     */
    public function comment($string, $verbosity = null)
    {
        $this->line($string, 'comment', 'COMMENT', $verbosity);
    }

    /**
     * Write a string as question output.
     *
     * @param string          $string
     * @param null|int|string $verbosity
     *
     * @return void
     */
    public function question($string, $verbosity = null)
    {
        $this->line($string, 'question', 'QUESTION', $verbosity);
    }

    /**
     * Write a string as error output.
     *
     * @param string          $string
     * @param null|int|string $verbosity
     *
     * @return void
     */
    public function error($string, $verbosity = null)
    {
        $this->line($string, 'error', 'ERROR', $verbosity);
    }

    /**
     * Write a string as warning output.
     *
     * @param string          $string
     * @param null|int|string $verbosity
     *
     * @return void
     */
    public function warning($string, $verbosity = null)
    {
        $this->loadStyle('warning', ['black', 'yellow']);
        $this->line($string, 'warning', 'WARNING', $verbosity);
    }

    /**
     * Draw a separator in the command output.
     *
     * @param string $character
     * @param string $style
     */
    public function separator($character = '_', $style = 'default')
    {
        $text = str_pad('', $this->outputMinWidth, $character);

        if ($style) {
            $text = "<$style>" . $text . "</$style>";
        }

        return $this->writeln($text);
    }

    /**
     * Write a string as standard output.
     *
     * @param string          $string
     * @param string          $style
     * @param string          $label
     * @param null|int|string $verbosity
     *
     * @return void
     */
    public function line($string, $style = null, $label = '', $verbosity = Output\OutputInterface::OUTPUT_NORMAL)
    {
        // If the string is an array of strings
        if (is_array($string) || $string instanceof \Traversable) {
            // First line to include the left side label
            if (!empty($label)) {
                $this->writeln($this->formatLine(array_shift($string), $style, $label), $verbosity);
            }

            // Display all other lines without the left label
            foreach ($string as $line) {
                $this->line($line, $style, '', $verbosity);
            }

            return;
        }

        // Split string into an array of lines & then print reach line, if the string contains \n
        if (strpos($string, "\n") !== false) {
            return $this->line(explode("\n", $string), $style, $label, $verbosity);
        }

        // Display one line of output
        return $this->writeln($this->formatLine(trim($string), $style, $label), $verbosity);
    }

    /**
     * Format string for console output.
     *
     * @param string      $string
     * @param null|string $style
     * @param string      $label
     *
     * @return string
     */
    public function formatLine($string, $style = null, $label = '')
    {
        $label = str_pad((!empty($label) ? $label . ':' : $label), 10);

        // side padding
        $padding = 1;
        $width   = $this->outputMinWidth - (strlen($label) + ($padding * 2));
        // Format output
        $string = " $label" . str_pad($string, $width) . ' ';

        if ($style) {
            return "<$style>" . $string . "</$style>";
        }

        return $string;
    }

    /**
     * Returns the output width.
     *
     * @return int
     */
    public function getOutputMinWidth()
    {
        return (int) $this->outputMinWidth;
    }

    /**
     * Calculate the terminal width.
     *
     * @return int
     */
    private function getTerminalWidth()
    {
        $application = new Application();
        $dimensions  = $application->getTerminalDimensions();

        return $dimensions[0] ?: self::MAX_LINE_LENGTH;
    }

    /**
     * Create and load output formatter style.
     *
     * @param string $name
     * @param array  $formatterStyle
     *
     * @return $this
     */
    protected function loadStyle($name, array $formatterStyle)
    {
        if (!$this->getFormatter()->hasStyle($name)) {
            $style = new OutputFormatterStyle(...$formatterStyle);
            $this->getFormatter()->setStyle($name, $style);
        }

        return $this;
    }
}