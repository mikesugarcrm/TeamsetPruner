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


class TeamSetPruneVerifyCommand extends TeamSetPruneCommand implements InstanceModeInterface
{
    protected function configure()
    {
        $this
            ->setName('teamset:verify')
            ->setDescription('Confirm it is safe to delete the team set records the pruner identifies as being unused.');
        $this->setHelp("
            If you have ever manually inserted teamset/user id data into your denorm table, you might
            want to run this command first and see if you're about to delete data your users depend on. 
            
            You should NEVER manaully insert data into the denorm table. If you do: 
                a) manual records give users access to records that their team memberships indicate they 
                   should not have access to.
                b) manual records will vanish if you run the team-security:rebuild cli command.
                
            But if you have inserted such records, this command will tell you which team sets that are in-use will be affected.
            The data will be written to a log file, the location of which will be shown after you run the command.
            ");
    }



    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->preflightCheck($output);
        $this->getPruner()->verifyTeamsetsToPrune();
    }
}