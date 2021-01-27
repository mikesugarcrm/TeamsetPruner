# The Team Set Pruner

### A sugar command line tool for safely removing unused team sets from the team sets tables in your sugar instance.

The team set pruner wil find all team sets in your database that are not used by any record and delete those unused team sets from the team sets tables, including the denorm table if denormalization is enabled. You do need to enable denormalization to use this tool.

It will create back up tables automatically in case you need to revert your changes, and provides a "restore from backup" command.

**NOTE: It's very important that you only use this tool during a planned outage. DO NOT USE this tool while users are active on the system.**

## REQUIREMENTS
- SUGAR 7.9 or later
- Your instance has enabled team set denormalization


## INSTALLING
**tar/zip**

You can tar/zip up the contents of the custom/ directory and untar/unzip them in your instance.
You will need to run QRR for the changes to take effect.

**Module Loadable Package**

With the provided manifest file, you can create a module loadable package with this zip command run from this repo's base directory:
```zip -r TeamSetPrunerLoadablePackage.zip manifest.php custom/*```
Then install the package as you would with any other package.


## RUNNING
After you have installed the Team Set Pruner, SSH into your server and navigate to your sugar root. From there, you can run any of the following commands:

To scan the team sets records for unused team sets, and generate a log file:
```
bin/sugarcrm teamset:scan
```

To back up all team set tables and then remove all unused team sets from all team sets tables:
```
bin/sugarcrm teamset:prune
```

To back up all team set tables only:
```
bin/sugarcrm teamset:backup
```

To see the SQL query used to find unused team sets, but take no other action:
```
bin/sugarcrm teamset:sql
```

To restore the pre-prune team set data from backup:
```
bin/sugarcrm teamset:restore_from_backup
```

You can get some additional information about these command with the help comand:
```
bin/sugarcrm help teamset:scan
```
