<?php

namespace MooCommand\Console\Helper;


use MooCommand\Console\Command;

trait CommandTrait
{
    protected ?Command $command = null;

    public function setCommand(Command $command): self
    {
        $this->command = $command;

        return $this;
    }

    public function getCommand(): ?Command
    {
        return $this->command;
    }
}
