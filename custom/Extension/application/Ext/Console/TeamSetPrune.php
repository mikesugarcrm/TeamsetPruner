<?php
$registry = Sugarcrm\Sugarcrm\Console\CommandRegistry\CommandRegistry::getInstance();
$registry->addCommands(array(
    new Sugarcrm\Sugarcrm\custom\Console\Command\TeamSetPrune\TeamSetPruneSqlCommand(),
    new Sugarcrm\Sugarcrm\custom\Console\Command\TeamSetPrune\TeamSetPruneScanCommand(),
    new Sugarcrm\Sugarcrm\custom\Console\Command\TeamSetPrune\TeamSetPrunePruneCommand(),
    new Sugarcrm\Sugarcrm\custom\Console\Command\TeamSetPrune\TeamSetPruneBackupCommand(),
    new Sugarcrm\Sugarcrm\custom\Console\Command\TeamSetPrune\TeamSetPruneRestoreFromBackupCommand(),
));