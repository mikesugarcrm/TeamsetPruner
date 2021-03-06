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

require_once('custom/src/Denormalization/TeamSecurity/TeamsetPruner.php');


class TeamSetPruneBackupCommand extends TeamSetPruneCommand implements InstanceModeInterface
{
    protected function configure()
    {
        $this
            ->setName('teamset:backup')
            ->setDescription('Backs up the team_sets related tables.');
        $this->setHelp("
            You don't need to run this command before you run teamset:prune.
            This command only backs up the team set tables. This is performed automatically when you run teamset:prune.
            But if you want backups of these tables for some other purpose, you can use this command to do so easily.
            It will back up these tables: team_sets, team_sets_teams, team_sets_modules, and the active denorm table
            (team_sets_users_[1|2])if denormalization is enabled.
            ");
    }



    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->preflightCheck($output);
        $this->getPruner()->backupTables();
    }
}