<?php

namespace Sugarcrm\Sugarcrm\custom\Console\Command\TeamSetPrune;
use Sugarcrm\Sugarcrm\Console\CommandRegistry\Mode\InstanceModeInterface;
use Sugarcrm\Sugarcrm\custom\Denormalization\TeamSecurity\TeamsetPruner;
use Symfony\Component\Console\Command\Command as Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Sugarcrm\Sugarcrm\Denormalization\TeamSecurity\State;
use Sugarcrm\Sugarcrm\DependencyInjection\Container;

require_once('custom/src/TeamSecurity/TeamsetPruner.php');


class TeamSetPruneConfirmCommand extends TeamSetPruneCommand implements InstanceModeInterface
{
    protected function configure()
    {
        $this
            ->setName('teamset:confirm')
            ->setDescription('Checks that no active team sets are missing from the team_sets table or the denorm table.');
        $this->setHelp("
            This command confirms that every active team set (team sets that are used by at least one record in sugar)
            are represented in the denorm table and the team sets table.
        
            If you have experienced data loss in either your team sets or your denorm table, you can use this command
            to see if any active team sets are not recorded in either of those tables.
            
            If you've been updating record team_set_id fields outside of sugar's framework, those team set id's might not
            be in the denorm table or the team sets table. This command can help you find such records.
            
            Records in sugar that use a team set which is not listed in the denorm table will not be accessible in the UI.
            
            Records in sugar that use a team set which is not listed the the team sets table will likely become inaccessible
            if you rebuild your denorm table or run teamset:prune.
            
            Any active teams sets missing from either of these tables will be recorded in a log file, and the log file's
            location will be shown after the command completes.
            ");
    }



    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->preflightCheck($output);
        $this->getPruner()->confirmPrune();
    }
}