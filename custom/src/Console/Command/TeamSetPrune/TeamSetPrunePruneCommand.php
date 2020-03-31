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

require_once('custom/src/TeamSecurity/TeamsetPruner.php');


class TeamSetPrunePruneCommand extends TeamSetPruneCommand implements InstanceModeInterface
{
    protected function configure()
    {
        $this
            ->setName('teamset:prune')
            ->setDescription('Prune the denorm table and all team set tables of unused team sets. Original tables will be backed up automatically. DO NOT USE while users are logged into the system!')
            ->setHelp("
            
                NOTE: You should only run this during a planned outage. 
                
                Pruning the denorm table means
                1) Backing up the denorm table, and the team_sets, team_sets_teams and team_sets_modules tables.
                   Backup tables start with 'tsp_' and end with a datetime stamp, '_MMDDHHMM'
                2) Truncating the original tables.
                3) Copying the active team sets out of the backup tables and into the truncated original tables, 
                   and leaving out the unused team sets.
                   \"Active\" team sets are team sets that are used by at least one record in sugar, i.e. a Case or an Account.
            ");
    }



    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->preflightCheck($output);
        $this->getPruner()->prune();
    }
}