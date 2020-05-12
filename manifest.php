<?php
$manifest = array(
    'key' => 'TeamSetPruner_v1.0',
    'name' => 'Team Set Pruner',
    'description' => 'The Team Set Pruner removed unused team sets from your team set tables, which can improve the performance of your instance.',
    'built_in_version' => '7.9',
    'version' => '1.0',
    'acceptable_sugar_versions' => array(
        'regex_matches' => array(
            '7.9.*',
            '8.*',
            '9.*',
            '10.*',
            '11.*',
        ),
    ),
    'acceptable_sugar_flavors' => array(
        'ENT',
        'PRO',
        'ULT',
    ),
    'author' => 'sugarcrm',
    'is_uninstallable' => true,
    'type' => 'module',
);

$installdefs = array(
    'id' => 'TeamSetPruner',
    'console' => array(
        array(
            'from' => '<basepath>/custom/Extension/application/Ext/Console/TeamSetPrune.php'
        )
    ),
    'copy' => array (
        array (
            'from' => '<basepath>/custom/src/Console/Command/TeamSetPrune/TeamSetPruneBackupCommand.php',
            'to' => 'custom/src/Console/Command/TeamSetPrune/TeamSetPruneBackupCommand.php',
        ),
        array (
            'from' => '<basepath>/custom/src/Console/Command/TeamSetPrune/TeamSetPruneCommand.php',
            'to' => 'custom/src/Console/Command/TeamSetPrune/TeamSetPruneCommand.php',
        ),
        array (
            'from' => '<basepath>/custom/src/Console/Command/TeamSetPrune/TeamSetPrunePruneCommand.php',
            'to' => 'custom/src/Console/Command/TeamSetPrune/TeamSetPrunePruneCommand.php',
        ),
        array (
            'from' => '<basepath>/custom/src/Console/Command/TeamSetPrune/TeamSetPruneRestoreFromBackupCommand.php',
            'to' => 'custom/src/Console/Command/TeamSetPrune/TeamSetPruneRestoreFromBackupCommand.php',
        ),
        array (
            'from' => '<basepath>/custom/src/Console/Command/TeamSetPrune/TeamSetPruneScanCommand.php',
            'to' => 'custom/src/Console/Command/TeamSetPrune/TeamSetPruneScanCommand.php',
        ),
        array (
            'from' => '<basepath>/custom/src/Console/Command/TeamSetPrune/TeamSetPruneSQLCommand.php',
            'to' => 'custom/src/Console/Command/TeamSetPrune/TeamSetPruneSQLCommand.php',
        ),
        array (
            'from' => '<basepath>/custom/src/Denormalization/TeamSecurity/TeamsetPruner.php',
            'to' => 'custom/src/Denormalization/TeamSecurity/TeamsetPruner.php',
        ),
    ),
);