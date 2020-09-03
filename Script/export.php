<?php 

namespace Script; 

require 'vendor/autoload.php';

use Exception;
use PDO;
use PDOException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Export
{
    protected $connect;
    protected $export_tables;
    protected $database;

    public function __construct($host, $username, $password, $database, $uses_socket, $export_tables, $socket_path)
    {
        $this->export_tables    = $export_tables;
        $this->database = $database;

        try {
            if($uses_socket){
                $this->connect = new PDO("mysql:unix_socket=$socket_path; mysql:host=$host; dbname=$database", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));  
            } else {
                $this->connect = new PDO("mysql:host=$host; dbname=$database", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));  
            }
            $this->connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
        } catch(PDOException $error) {
            die($error->getMessage().PHP_EOL);
        }  
    
    }


    function run(){
        foreach($this->export_tables as $export_table_name){
            try{
                $this->export($export_table_name, $export_table_name.'_export.xlsx');
            } catch (Exception $e){
                die(print_r($e->getMessage()));
            }
        }
    }


    /**
     * Export table.
     * 
     * @param string $tablename
     * @param string $filename
     */
    function export($tablename, $filename){
        $spreadsheet = new Spreadsheet();
        $sql = "SELECT * FROM ".$tablename;
        $result = $this->connect->query($sql);

        $spreadsheet = new Spreadsheet();
        $Excel_writer = new Xlsx($spreadsheet);
        
        $spreadsheet->setActiveSheetIndex(0);

        $activeSheet = $spreadsheet->getActiveSheet();

        // set rows
        $row_names = [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 
            'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'
        ];

        // get export columns
        $sql_info = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = N'$tablename' AND TABLE_SCHEMA = '$this->database'";
        $sql_info_result = $this->connect->query($sql_info);
        $export_columns = [];
        while($single_info_column = $sql_info_result->fetch(PDO::FETCH_ASSOC)) {
            array_push($export_columns, $single_info_column['COLUMN_NAME']);
        }

        $count_query = "SELECT COUNT(*) as number_of_columns FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$this->database' AND table_name = '$tablename'";
        $count_query_result = $this->connect->query($count_query);
        while($res = $count_query_result->fetch(PDO::FETCH_ASSOC)) {
            $table_columns = $res['number_of_columns'];
        }

        // max size of one row
        $max_size = 25;
        $current_column = 1;
        $cell_index = 0;
        $cell_size = 1;
        $current_cell_focus_length = 0;
        $current_cell_focus_position = 0;
        $cells = [];

        $cells_increment = 0;
        $base_cell = 'A';
        // iterate over row names (96)
        while(count($cells) < $table_columns){

            if($cell_index > 25){
                // increase cell size
                $cell_size++;
                // set current cell focused for iteration
                $cell_index = 0;
                // increase number of cells
                $cells_increment++;
            }

            if($cell_size > 1){
                $current_cell_focus = $row_names[$cells_increment-1];
                $current_cell       = $current_cell_focus.$row_names[$cell_index].$current_column;
                array_push($cells, $current_cell);
            } else {
                array_push($cells, $row_names[$cell_index].$current_column);
            }

            $cell_index++;
        }

        $count = 0;
        foreach($export_columns as $column){
            if($column === "name" || $column === "slug" || $column === "created_at" || $column === "updated_at" || $column === "deleted_at"){
                continue;
            }
            if(isset($cells[$count])){
                $activeSheet->setCellValue($cells[$count], $column);
                $count++;
            }
        }

        // set auto size
        foreach($cells as $c){
            $spreadsheet->getActiveSheet()->getColumnDimension(substr($c,0,-1))->setAutoSize(true);
        }


        $record_index = 2;
        $col_index = 0;
        $max_cell = 95;

        $test = [];
        while($record = $result->fetch(PDO::FETCH_ASSOC)) {

            $counter = 0;
            while($counter < $table_columns){
                $activeSheet->setCellValue(
                    substr($cells[$counter], 0,-1).$record_index, 
                    $record[$export_columns[$counter]]
                );

                $counter++;
            }

            $record_index++;
        }
        
        // header('Content-Type: application/vnd.ms-excel');
        // header('Content-Disposition: attachment;filename="'. $filename);
        // header('Cache-Control: max-age=0');
        $Excel_writer->save($filename);
        echo(ucfirst($tablename).' exported successfully'.PHP_EOL);
    }
}

?>