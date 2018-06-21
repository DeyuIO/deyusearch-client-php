<?php

namespace DeyuSearch\Frameworks\ThinkPHP\Commands;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;

class ImportCommand extends Command
{

    protected function configure()
    {
        $this->setName('deyu:import')->setDescription('Import the given model to search index');
        $this->addArgument('class', Argument::REQUIRED, "The name of the model class");
    }

    protected function execute(Input $input, Output $output)
    {
        $class = trim($input->getArgument('class'));

        if (!class_exists($class)) {
            $output->writeln('Class [' . $class . '] not exists.');
            return;
        }

        (new $class)->makeAllSearchable(function ($last_imported_id) use ($output, $class) {
            $output->writeln('Imported model [' . $class . '] up to ID: ' . $last_imported_id);
        });
    }
}