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


abstract class TeamSetPruneCommand extends Command implements InstanceModeInterface
{
    protected function configure(){}


    /**
     * Check the config table for denormalization being enabled, the table being available and rebuild is not running.
     *
     * @param OutputInterface $output
     * @return bool
     * @throws \Exception
     */
    protected function preflightCheck(OutputInterface $output)
    {
        $state = Container::getInstance()->get(State::class);

        if (!$state->isEnabled()) {
            $exception = new \Exception("Team Security Denormalization is not enabled!. Cannot prune team sets in an instance where team security is disabled.");
            throw $exception;
        }

        if (!$state->isAvailable()) {
            $exception = new \Exception("The Team Security Denormalization table is not available. It may not exist. You can create it with the team-security:rebuild command.");
            throw $exception;
        }

        if ($state->isRebuildRunning()) {
            $exception = new \Exception("The Team Security Denormalization table is currently being rebuilt. We cannot prune until the rebuild is complete");
            throw $exception;
        }
        return true;
    }


    protected function getPruner()
    {
        return new TeamsetPruner();
    }
}