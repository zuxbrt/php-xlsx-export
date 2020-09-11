<?php

include('../php-xlsx-export/Script/export.php');
use Script\Export as Export;

class Parser
{
    public $helpmessage = 
    'Required parameters:
        --host               - host url (example: --host=localhost)
        --database           - database name (example: --database=databasename)
        --username           - mysql username (example: --username=root)
        --password           - mysql username (example: --password=root)
        --tables             - tables that will be exported: (example: --tables=users,messages)
        --socket             - is unix socket used for mysql connection (example: --socket=true / --socket=false)
        --socket_path        - provide socket path (example: --socket_path=/Applications/MAMP/tmp/mysql/mysql.sock)
    Example command:
        php export --host=localhost --username=root --password=root --socket=false --socket_path=/Applications/MAMP/tmp/mysql/mysql.sock --database=licences --tables=users,licences,messages
        ';

    public function execute($commands)
    {
        session_start();
        // example command
        // php export --host=localhost --username=root --password=root --socket=false --database=databasename --tables=table1,table2,table3

        // example socket path
        // /Applications/MAMP/tmp/mysql/mysql.sock

        // check if help command is called to return help message right away
        if(count($commands) > 0){
            if($commands[1] == '--help'){
                echo $this->helpmessage;
                return;
            }
        }

        $file_exists = file_exists(__DIR__.'/config.txt');

        $export_tables = [];
        $host = null;
        $database = null;
        $username = null;
        $password = null;
        $uses_socket = false;
        $socket_path = null;

        if($file_exists){
            $config_file = file_get_contents(__DIR__.'/config.txt');
            $config = explode("\n", $config_file);
            
            foreach($config as $argument){
                $param = explode('=', $argument);
            
                switch ($param[0]) {
                    case 'host':
                        if(isset($param[1])){
                            $host = $param[1];
                            break;
                        } else {
                            echo 'Missing host url.'.PHP_EOL;
                        }
                        
                    case 'database':
                        if(isset($param[1])){
                            $database = $param[1];
                            break;
                        } else {
                            echo 'Missing database name.'.PHP_EOL;
                        }
            
                    case 'username':
                        if(isset($param[1])){
                            $username = $param[1];
                            break;
                        } else {
                            echo 'Missing username.'.PHP_EOL;
                        }
            
                    case 'password':
                        if(isset($param[1])){
                            $password = $param[1];
                            break;
                        } else {
                            echo 'Missing password.'.PHP_EOL;
                        }
            
                    case 'socket_path':
                        if(isset($param[1])){
                            $socket_path = $param[1];
                            break;
                        } else {
                            echo 'Missing socket path.'.PHP_EOL;
                        }
                
                    case 'tables':
                        if(count($param) < 2){
                            echo 'Missing tables.'.PHP_EOL;
                            return;
                        } else {
                            $tablenames = explode(',', $argument);
                            $len = 0;
                            foreach($tablenames as $tablename){
                                if($len < 1){
                                    $firstparam = explode('=', $tablename);
                                    if(count($firstparam) > 1){
                                        array_push($export_tables, $firstparam[1]);
                                    }
                                } else {
                                    array_push($export_tables, $tablename);
                                }
                                    $len++;
                            }
                        }
                        break;
                }
            }
            
            if(count($export_tables) < 1 || $database == null || $username == null || $password == null || $host = null){
                echo PHP_EOL.'Missing required parameters. Check config_example.txt.'.PHP_EOL;
                return;
            }
            
        } else {

            if(count($commands) <= 1){
                echo PHP_EOL.'No configuration file found. Create a new one or run export via terminal. Run php export --help to list commands.'.PHP_EOL.PHP_EOL;
                return;
            }
            
            $commands = ['--host', '--database', '--username', '--password', '--tables', '--socket', '--socket_path', '--help'];
            
            foreach($commands as $argument){
                if($argument !== 'export'){
            
                    $param = explode('=', $argument);
                    if(!in_array($param[0], $commands)){
                        echo PHP_EOL.'Unknown command. Run php export --help to list commands.'.PHP_EOL.PHP_EOL;
                        return;
                    }
            
                    switch ($param[0]) {
                        case '--host':
                            if(isset($param[1])){
                                $host = $param[1];
                                break;
                            } else {
                                echo 'Missing host url. Run php export --help to see an example'.PHP_EOL;
                            }
                        
                        case '--database':
                            if(isset($param[1])){
                                $database = $param[1];
                                break;
                            } else {
                                echo 'Missing database name. Run php export --help to see an example'.PHP_EOL;
                            }
            
                        case '--username':
                            if(isset($param[1])){
                                $username = $param[1];
                                break;
                            } else {
                                echo 'Missing username. Run php export --help to see an example'.PHP_EOL;
                            }
            
                        case '--password':
                            if(isset($param[1])){
                                $password = $param[1];
                                break;
                            } else {
                                echo 'Missing password. Run php export --help to see an example'.PHP_EOL;
                            }
            
                        case '--socket':
                            if(isset($param[1])){
                                $uses_socket = ($param[1] == 'true') ? true : false;
                                break;
                            } else {
                                echo 'Missing is using socket(true/false). Run php export --help to see an example'.PHP_EOL;
                            }
            
                        case '--socket_path':
                            if(isset($param[1])){
                                $socket_path = $param[1];
                                break;
                            } else {
                                echo 'Missing socket path. Run php export --help to see an example'.PHP_EOL;
                            }
                    
                        case '--tables':
                            if(count($param) < 2){
                                echo 'Missing parameter values.'.PHP_EOL;
                                return;
                            } else {
                                $tablenames = explode(',', $argument);
                                $len = 0;
                                foreach($tablenames as $tablename){
                                    if($len < 1){
                                        $firstparam = explode('=', $tablename);
                                        if(count($firstparam) > 1){
                                            array_push($export_tables, $firstparam[1]);
                                        }
                                    } else {
                                        array_push($export_tables, $tablename);
                                    }
                                        $len++;
                                }
                            }
                            break;
            
                        case '--help':
                            return $this->helpmessage;
                        
                        default:
                            echo 'Unknown command.'.PHP_EOL;
                            return;
                        }
                } else {
                    continue;
                }
            }
            
            if(count($export_tables) < 1 || $database == null || $username == null || $password == null || $uses_socket = null || $host = null){
                echo PHP_EOL.'Missing required parameters. Run php export --help to see an example'.PHP_EOL;
                return;
            }
            
        }

        if($socket_path !== ''){
            $uses_socket = true;
        }
        $export = new Export($host, $username, $password, $database, $uses_socket, $export_tables, $socket_path);
        $export->run();
        session_destroy();
    }
}

?>
