<?php

namespace DeyuSearch\Frameworks\ThinkPHP\Commands;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class PublishCommand extends Command
{
    protected function configure()
    {
        $this->setName('deyu:publish')->setDescription('Publish files to the Framework.');
    }

    protected function execute(Input $input, Output $output)
    {
        $res = copy(dirname(__FILE__) . '/../config.php', APP_PATH . '/deyu.php');
        if ($res) {
            $output->writeln("Publish Successed!");
        } else {
            $output->writeln("Publish failed!");
        }
    }
}