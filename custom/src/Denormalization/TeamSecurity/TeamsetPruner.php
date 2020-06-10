<?php

namespace Sugarcrm\Sugarcrm\custom\Denormalization\TeamSecurity;

use BeanFactory as BeanFactory;
/**
 * Class TeamsetPruner
 * @package Sugarcrm\Sugarcrm\custom\Denormalization\TeamSecurity
 *
 * Sugar creates a lot of team sets. Often, these team sets will be "unused", meaning there
 * are no records that refer to that team set. Over time, these unused team sets accumulate
 * in the denorm table, and the team_sets and team_sets_teams tables, which causes performance
 * problems generally in sugar.
 *
 * This class's purpose is to identify such team sets, count them, log their existence,
 * and ultimately remove them from these tables. Before actually pruning the tables, it
 * will create backups of those tables.
 */
class TeamsetPruner
{
    /* @var tables we don't want to search for team set id's */
    public $blacklistedTables = array(
        'team_sets',
        'team_sets_teams',
        'team_sets_modules'
    );


    /* @var array - we have to assign this dynamically in construct because the denorm table name is dynamic */
    public $tablesToPrune = array();

    /* @var string - we'll retrieve this from the config table if denormalization is enabled */
    public $denormTableName;

    /* @var array - list of tables that have been backed up, with the original table name as the key and the backed up table name as the value. */
    public $backedUpTables = array();

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
        $this->db->getConnection();
        $this->tablesToPrune = array(
            'team_sets' => 'id',
            'team_sets_teams' => 'team_set_id',
            'team_sets_modules' => 'team_set_id',
        );

        if ($this->denormIsUsed()) {
            $this->denormTableName = $this->getActiveDenormTable();
            $this->tablesToPrune[$this->denormTableName] = 'team_set_id';
        }
    }


    public function execute()
    {
        switch($this->command) {
            case 'sql':
                $this->getSQL();
                break;
            case 'scan':
                $this->scan();
                break;
            case 'prune':
                $this->prune();
                break;
            case 'backup':
                $this->backupTables();
                break;
            case 'restore':
                $this->restoreFromBackup();
                break;
            case 'help':
            default:
                $this->printHelp();
                break;
        }
    }


    /**
     * Remove unused team sets. A team set is unused if no bean refers to it.
     *
     * It will backup each table in $this->tablesToPrune. @see TeamsetPruner::backupTable() for details.
     * Then it will truncate each table in $this->tablesToPrune.
     * Then it will copy only the active (in use) team sets from each backup table into the the original table.
     *
     * This process seems counter-intuitive, but truncating and inserting are generally faster than deleting, especially
     * if you're deleting most of the contents of a 100 million row denorm table.
     *
     */
    public function prune()
    {
        $this->stdOut("Starting prune");
        $this->scan();

        foreach ($this->tablesToPrune as $tableName => $idColumnName) {
            $this->pruneTable($tableName);
        }
        $this->saveBackupTableMappings();

        $this->stdOut("Finished prune");
    }


    /**
     * Backs up the specified table, then truncates it and then copies only the active team set ids from the backup
     * table back into the main table.
     *
     * @param string $tableName - the name of the table to operate on.
     */
    public function pruneTable($tableName)
    {
        $this->logMsg("Start pruning from $tableName");
        $this->backupTable($tableName);
        $this->truncateTable($tableName);
        $this->populateTableFromBackup($tableName);
        $this->logMsg("Finished pruning from $tableName");
    }


    /**
     * Query the denorm table for teamsets that are no longer in use, and log the results.
     */
    public function scan()
    {
        $this->stdOut("Starting Scan");
        // query the db.
        $unusedTeamSets = $this->searchForUnusedTeamsets();

        // compute total count of team sets and denorm entries.
        $count = count($unusedTeamSets);

        // log those values.
        $this->stdOut("TeamsetPruner::scan() found $count unused team sets");

        // write a complete dump to a separate file.
        $dateStamp = date("Y_m_d_H_i_s");
        $filePath = "TeamsetPruner/scan/unused_teamsets_{$dateStamp}";
        $this->stdOut("Details have been written to $filePath. This will be an array of unused team set id's.");
        $dataAsString = print_r($unusedTeamSets, true);
        $this->writeFile($filePath, $dataAsString);
        $this->stdOut("Finished Scan");
    }


    /**
     * Return the sql for the unused team sets query.
     */
    public function getSQL()
    {
        $this->stdOut($this->buildUnusedTeamsetsQuery());
    }


    /**
     * Runs the query to find unused team sets and returns the results as an array of
     * team_set_id => number of entries for that team set id in the denorm table.
     *
     * @return array
     */
    public function searchForUnusedTeamsets()
    {
        $unusedTeamsetsData = array();
        $sql = $this->buildUnusedTeamsetsQuery();
        $results = $this->db->query($sql);
        while ($row = $this->db->fetchByAssoc($results)) {
            $unusedTeamsetsData[] = $row['team_set_id'];

        }
        return $unusedTeamsetsData;
    }


    /**
     * Builds the sql query for unused team sets.
     *
     * This query needs to be built with several variations, which are addressed by the arguments passed into this
     * method.
     *
     * To search for unused team sets, $unused = true. But to search for team sets that are in use,
     * $unused = false.
     *
     * @param bool $unused
     * @param string $teamSetsTableName
     * @param array $userIDs
     * @return string
     */
    public function buildUnusedTeamsetsQuery($unused = true, $teamSetsTableName = 'team_sets')
    {
        $tables = $this->getTablesWithTeams();
        $unions = $this->buildUnionStatements($tables, $teamSetsTableName);

        if (strpos($teamSetsTableName, 'tsp_') === 0) {
            if (isset($this->backedUpTables['team_sets'])) {
                $teamSetsTableName = $this->backedUpTables['team_sets'];
            } else {
                $this->stdOut("$teamSetsTableName has not been backed up, so we can't use the backup in our queries for buildUnusedTeamsetsQuery(). Resorting to using original table $teamSetsTableName");
            }
        }

        if ($unused) {
            $whereInOperator = 'NOT IN';
        } else {
            $whereInOperator = 'IN';
        }

        $sql = "select ts.id team_set_id from $teamSetsTableName ts";
        $sql .= "\nwhere ts.id $whereInOperator (";
        $sql .= "\n$unions\n";
        $sql .= ")";
        return $sql;
    }


    /**
     * Takes an array of every table that uses team sets and builds a huge union statement that we use to select
     * all of the distinct team set id's from all tables that use team sets.
     *
     *
     * @param array $tables - an array of the name of every table in sugar that uses team sets.
     * @param string $teamSetsTableName - the name of the team_sets table (probably team_sets).
     * @return string - an sql query that will return every actively used team_set_id value.
     */
    public function buildUnionStatements($tables, $teamSetsTableName = 'team_sets')
    {
        $unionStatements = array();
        foreach ($tables as $table) {
            $unionStatements[] = <<<SQL
  select id from $teamSetsTableName where id in (
    select DISTINCT team_set_id from $table where team_set_id in (
      select id from $teamSetsTableName
      where deleted = 0
    )
  ) and deleted = 0
SQL;
        }

        return implode("\n  union\n", $unionStatements);
    }


    /**
     * Backs up the 4 tables we need to truncate and repopulate with active team sets only.
     */
    public function backupTables()
    {
        foreach($this->tablesToPrune as $tableName => $idColumnName) {
            $this->backupTable($tableName);
        }
    }


    /**
     * Creates a copy of the table to back up and names it with a date-stamped name, and the copies all of the data
     * from the original table into the backup.
     *
     * Returns false if the table has already been backed up.
     *
     * @param string $tableName
     * @return bool
     */
    public function backupTable($tableName)
    {
        $this->stdOut("Starting backup $tableName");

        if (isset($this->backedUpTables[$tableName])) {
            $this->stdOut("$tableName has already been backed up as {$this->backedUpTables[$tableName]}");
            return false;
        }

        $dateStamp = date("mdHi");
        $backupName = "tsp_{$tableName}_$dateStamp";
        $backupSQL = "create table {$backupName} as select * from {$tableName}";
        $this->db->query($backupSQL);
        $this->backedUpTables[$tableName] = $backupName;
        $this->stdOut("Finished backup $tableName to $backupName");
        return true;
    }


    /**
     * Truncate the specified table. Will NOT truncate if the table has not been backed up.
     *
     * @param string $tableName
     * @return bool
     */
    public function truncateTable($tableName)
    {
        $this->stdOut("Starting Truncate $tableName");

        if (!isset($this->backedUpTables[$tableName])) {
            $this->stdOut("$tableName has not been backed up! Aborting truncate!");
            return false;
        }

        $sql = "truncate table $tableName";
        $qb = $this->db->getConnection()->createQueryBuilder();
        $qb->getConnection()->executeUpdate($sql, []);

        $this->stdOut("Finished Truncate $tableName");
        return true;
    }


    /**
     * Repopulates the main tables with only active, in-use team sets.
     *
     * We use the buildUnusedTeamsetQuery() method with false as the second arg to build a query that will return
     * only active team sets.
     *
     * We use that sql to run an "insert select" query that copies our desired data from the backup table into
     * the original table.
     *
     * @param string $tableName
     * @return bool
     */
    public function populateTableFromBackup($tableName)
    {
        $this->stdOut("Starting Populate from backup for $tableName");

        if (!isset($this->backedUpTables[$tableName])) {
            $this->stdOut("$tableName has not been backed up! Aborting Populate!");
            return false;
        }

        $teamSetsTableBackup = $this->backedUpTables['team_sets'];
        $activeTeamsetSQL = $this->buildUnusedTeamsetsQuery(false, $teamSetsTableBackup);
        $backupTable = $this->backedUpTables[$tableName];
        $fields = implode(', ', $this->getFieldsForTable($tableName));

        if (empty($fields)) {
            $this->stdOut("Cannot establish fields for $tableName. Aborting Populate!");
            return false;
        }

        $teamSetIdColumn = $this->tablesToPrune[$tableName];
        $copySQL = <<<SQL
insert into $tableName ($fields) 
select $fields 
from $backupTable 
where $teamSetIdColumn in ($activeTeamsetSQL)
SQL;
        $this->logMsg("Populate from Backup SQL:\n$copySQL\n");
        $qb = $this->db->getConnection()->createQueryBuilder();
        $qb->getConnection()->executeUpdate($copySQL, []);
        $this->stdOut("Finished Populate from backup for $tableName");
        return true;
    }


    /**
     * Gives us a list of the fields for a specific table based on that table's metadata.
     *
     * Uses a hard-coded map of table names to $dictionary key names. Not ideal if those names change in the future.
     *
     * If it can't find the fields, it will return an empty array. Since we use this method to establish which fields
     * you want to insert to for your sql insert statement, an empty array here will produce invalid sql.
     *
     * @param $tableName
     * @return array - an array of table field names.
     */
    public function getFieldsForTable($tableName)
    {
        $tableNameToDictionaryMapping = array(
            'team_sets_teams' => 'team_sets_teams',
            'team_sets' => 'TeamSet',
            'team_sets_modules' => 'TeamSetModule',
        );

        if ($this->denormIsUsed()) {
            $tableNameToDictionaryMapping[$this->denormTableName] = $this->denormTableName;
        }

        BeanFactory::getBean('TeamSets');
        BeanFactory::getBean('TeamSetModules');

        $dictionaryKey = $tableNameToDictionaryMapping[$tableName];

        if (!isset($GLOBALS['dictionary'][$dictionaryKey])) {
            $this->stdOut("There is no dictionary entry for $dictionaryKey.");
            return array();
        }

        if (!isset($GLOBALS['dictionary'][$dictionaryKey]['fields'])) {
            $this->stdOut("Dictionary entry for $dictionaryKey has no fields.");
            return array();
        }

        $fields = array_filter($GLOBALS['dictionary'][$dictionaryKey]['fields'], function($fieldDef) {
            if (isset($fieldDef['source'])) {
                if ($fieldDef['source'] == 'non-db') {
                    return false;
                }
            }
            return true;
        });

        return array_keys($fields);
    }

    /**
     * Returns the name of the active denorm table.
     *
     * @return mixed - string if there is an active denorm table.
     */
    public function getActiveDenormTable()
    {
        $di = \Sugarcrm\Sugarcrm\DependencyInjection\Container::getInstance();
        $state = $di->get(\Sugarcrm\Sugarcrm\Denormalization\TeamSecurity\State::class);
        return $state->getActiveTable();
    }


    /**
     * Returns true if denormalization is enabled and the the denorm table is available.
     *
     * @return bool - true if denorm is enabled and the table is available.
     */
    public function denormIsUsed()
    {
        $di = \Sugarcrm\Sugarcrm\DependencyInjection\Container::getInstance();
        $state = $di->get(\Sugarcrm\Sugarcrm\Denormalization\TeamSecurity\State::class);
        return ($state->isEnabled() && $state->isAvailable());
    }


    /**
     * Returns an array of all tables that use team sets. The metric is just "does this table have a team_set_id field?",
     * excluding certain tables we don't want to consider.
     *
     * @return array - an array of table names.
     *
     */
    public function getTablesWithTeams()
    {
        global $beanFiles;
        $tables = array();

        foreach ($beanFiles as $bean => $file) {
            if (file_exists($file)) {
                $focus = BeanFactory::newBeanByName($bean);
                if ($focus instanceOf \SugarBean) {
                    if(isset($focus->field_defs['team_set_id']) && !in_array($focus->table_name, $this->blacklistedTables)) {
                        $tables[] = $focus->table_name;
                    }
                }
            }
        }
        return array_unique($tables);
    }


    /**
     * Writes a file to store a mapping between team set tables and their backups so we can restore later.
     */
    public function saveBackupTableMappings()
    {
        $backupString = "<?php\n\$backedupTables = " . var_export($this->backedUpTables, true) . ";";
        $this->writeFile('TeamsetPruner/backupTables.php', $backupString);
    }


    /**
     * Truncates each of the team set tables and then copies the data from each table's backup into the table. This
     * restores the original state of all of the team set tables.
     *
     * NOTE: if users have been adding new team sets since you ran the backup, you will loose any data that was added
     * between the time you ran the backup and the time you run restoreFromBackup().
     *
     * @return bool
     */
    public function restoreFromBackup()
    {
        $this->stdOut("Starting Restore");
        $backedupTables = array();
        if (!file_exists('TeamsetPruner/backupTables.php')) {
            $this->stdOut("'TeamsetPruner/backupTables.php' does not exist - cannot restore from non-existent backups.");
            return false;
        }
        include('TeamsetPruner/backupTables.php');
        $this->backedUpTables = $backedupTables;
        // $backedupTables should be defined in backupTables.php
        foreach ($backedupTables as $tableName => $backupTableName) {
            $this->truncateTable($tableName);
            $resetSQL = "insert into $tableName select * from $backupTableName";
            $this->db->query($resetSQL);
            $this->stdOut("Restored $tableName from $backupTableName");
        }
        $this->stdOut("Finished Restore");
        return true;
    }


    /**
     * Just a wrapper for creating a directory and writing out a file.
     *
     * @param $path - the destination of the file to write.
     * @param $data - the contents to write.
     * @return bool - true if we are able to create any necessary directories and successfully wrote the file contents.
     *  false otherwise.
     */
    public function writeFile($path, $data)
    {
        $dir = pathinfo($path, PATHINFO_DIRNAME);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                $this->stdOut("Failed to create directory $dir");
                return false;
            }
        }

        if (file_put_contents($path, $data) === false) {
            $this->stdOut("Failed to write to $path");
            return false;
        }

        return true;
    }


    /**
     * Just a logging wrapper.
     *
     * @param string $msg - message to log.
     */
    public function logMsg($msg)
    {
        $GLOBALS['log']->fatal($msg);
    }


    /**
     * Prints and logs the message.
     * @param string $msg - message to log.
     */
    public function stdOut($msg)
    {
        $GLOBALS['log']->fatal($msg);
        print("$msg\n");
    }


    /**
     * Prints help for the various commands.
     */
    public function printHelp()
    {
        $help = <<<HELP


TeamsetPruner class - searches for team sets in the denorm table that are not in use for any bean and deletes them.

Valid Commands:
    sql                 - prints the SQL used to query the denormalization table for unused team sets.
    
    scan                - queries the denorm table and logs the results.
    
    prune               - Removes unused Team Set entries from the denorm table ({$this->denormTableName}), team_sets, team_sets_teams 
                          and team_sets_modules tables.

    restore             - Truncates all of the team set tables and copies the data from the backup tables back into the
                          original tables. This completely reverses the effects of pruning. NOTE: don't do this on a live
                          system people are using, you could loose data.
    
    help                - Prints this help message.

NOTE: you need to set these values in config_override.php:


\$sugar_config['teamset_cleanup_debug']['users'][] = 'all';
\$sugar_config['teamset_cleanup_debug']['file'] = 'teamset_cleanup';

HELP;
        print($help);
    }
}