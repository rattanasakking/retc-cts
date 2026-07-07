<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MySQL CLI tool paths
    |--------------------------------------------------------------------------
    |
    | Used by Settings > Backup/Restore to shell out to mysqldump (backup)
    | and mysql (restore). On Linux/macOS these are usually just "mysqldump"
    | and "mysql" if they're on the PATH.
    |
    */

    'mysqldump_path' => env('MYSQLDUMP_PATH', 'mysqldump'),

    'mysql_cli_path' => env('MYSQL_CLI_PATH', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Storage disk and directory for backup files
    |--------------------------------------------------------------------------
    */

    'disk' => 'local',

    'directory' => 'backups',
];
