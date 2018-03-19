<?php

namespace DeyuSearch\Frameworks\ThinkPHP\Commands;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;

class FlushCommand extends Command
{

    protected function configure()
    {
        $this->setName('deyu:flush')->setDescription("Flush all of the model's records from the index");
        $this->addArgument('class', Argument::REQUIRED, "The name of the model class");
    }

    protected function execute(Input $input, Output $output)
    {
        $class = trim($input->getArgument('class'));

        if (!class_exists($class)) {
            $output->writeln('Class [' . $class . '] not exists.');
            return;
        }

        (new $class)->makeAllUnsearchable(function ($last_flushed_id) use ($output, $class) {
            $output->writeln('Flushed model [' . $class . '] up to ID: ' . $last_flushed_id);
        });

        $output->writeln('All ['.$class.'] records have been flushed.');
    }
}