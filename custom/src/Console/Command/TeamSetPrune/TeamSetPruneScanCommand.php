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


class TeamSetPruneScanCommand extends TeamSetPruneCommand implements InstanceModeInterface
{
    protected function configure()
    {
        $this
            ->setName('teamset:scan')
            ->setDescription('Scan the denormalization table for unused team sets entries and report the number found.')
            ->setHelp("
            Makes no changes, just reports how many unused team sets are in the denorm table and how
            many records those unused team sets represent
            ");

    }



    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->preflightCheck($output);
        $this->getPruner()->scan();
    }
}