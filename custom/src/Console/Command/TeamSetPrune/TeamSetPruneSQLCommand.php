<?php

namespace Sugarcrm\Sugarcrm\custom\Console\Command\TeamSetPrune;
use Sugarcrm\Sugarcrm\Console\CommandRegistry\Mode\InstanceModeInterface;
use Sugarcrm\Sugarcrm\custom\Denormalization\TeamSecurity\TeamsetPruner;
use Symfony\Component\Console\Command\Command as Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Sugarcrm\Sugarcrm\Denormalization\TeamSecurity\State;
use Sugarcrm\Sugarcrm\DependencyInjection\Container;

require_once('custom/src/Denormalization/TeamSecurity/TeamsetPruner.php');


class TeamSetPruneSqlCommand extends TeamSetPruneCommand implements InstanceModeInterface
{
    protected function configure()
    {
        $this
            ->setName('teamset:sql')
            ->setDescription('Print the sql query used to search for unused teamsets')
            ->setHelp("
            Just displays the SQL used to search for unused team sets, in case you want to review it.
            ");
    }



    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->preflightCheck($output);
        $this->getPruner()->getSQL();
    }
}