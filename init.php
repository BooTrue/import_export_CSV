<?php

// Подключение к базе данных
class ConnectDb
{
    function __construct(string $server, string $user, string $passwd, string $db)
    {
        $this->server = $server;
        $this->user = $user;
        $this->passwd = $passwd;
        $this->db = $db;
    }

    public function connect()
    {
        return new mysqli($this->server, $this->user, $this->passwd, $this->db);
    }
}

// Два метода: экспорт и импорт csv
class ExpImpCsv
{
    public $num_update = 0;
    public $num_insert = 0;

    function __construct(string $name_file, string $name_tableDb)
    {
        $this->name_file = $name_file;
        $this->name_tableDb = $name_tableDb;
        
    }


    public function importCsv(ConnectDb $connectDb, int $id_user)
    {    
        $con = $connectDb->connect();
        $row = 1;    
        $title = [];

        // Преобразование массива в строку для запроса в mysql
        function editArray($str_mass){
            $res = explode(', ', $str_mass);
            $str = '';
            foreach ($res as $item) {
                if (is_numeric($item)){
                    if($item != $item[0]){
                        $str .= ' ROUND('.$item. ',2), ';
                    }else{
                        $str .= ' '.$item. ', ';
                    }
                }else{
                    $str .= ' "'.$item.'", ';
                }
            }
            return mb_substr($str, 0, -2);
        }

        // Чтение csv файла и формирование запроса для mysql
        if (($handle = fopen($this->name_file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1024, ",")) !== FALSE) {
                $num = count($data);
                if ($row == 1) {
                    for ($i = 0; $i < $num; $i++) {
                        array_push($title, $data[$i]);
                        $row++;
                    }
                } else {
                    $select_id = $con->query("SELECT id FROM ".$this->name_tableDb);
                    $list_id = [];
                    foreach ($select_id as $item){      // Получаем список id из БД
                        array_push($list_id, $item['id']);
                    }
                    if (in_array($data[0], $list_id)){  // Если значение id из csv файла есть в списке, обновляет значение в БД
                        $res_query = '';
                        for ($i = 0; $i < $num; $i++) {
                            $res_query .= $title[$i]." = ".editArray($data[$i]);
                            if (end($data)!=$data[$i]){
                                $res_query .= ", ";
                            }
                        }
                        if ($id_user == end($data)){
                            $con->query("UPDATE ".$this->name_tableDb." SET " .$res_query. " WHERE id=".$data[0]);
                            $this->num_update += 1;
                        }
                        
                    }else{  // Иначе вносит данные в БД
                        $con->query("INSERT " .$this->name_tableDb. " (" .implode(", ", $title). ") VALUES (".editArray(implode(", ", $data)).")");
                        $this->num_insert += 1;
                    }
                    $row++;
                }   
            }
            fclose($handle);
            $con->close();
        }
    }

    public function exportCsv(ConnectDb $connectDb)
    {
        $con = $connectDb->connect();
        $file = fopen($this->name_file, "w+");
        $data_table = $con->query("SELECT * FROM ".$this->name_tableDb);
        $list_title = [];

        foreach($data_table as $item){
            foreach ($item as $title=>$value){
                if (!in_array($title, $list_title)){
                    array_push($list_title, $title);
                }
            }
        }

        fputcsv($file, $list_title);
        foreach($data_table as $item){
            fputcsv($file, $item);
        }
        
        $con->close();
        fclose($file);
    }
}

// Проверка поля "small_text" из БД и добавление значения из 'big_text'
class CheckValues
{
    function __construct(string $name_tableDb)
    {
        $this->name_tableDb = $name_tableDb;
    }
    static public function runCheck (ConnectDb $connectDb, CheckValues $checkVal){

        $con = $connectDb->connect();
        $table = $con->query("SELECT * FROM ".$checkVal->name_tableDb);
        foreach ($table as $line){
            foreach ($line as $key=>$value){
                if (($key == 'small_text') && ($value=='')){
                    $st = strip_tags($line['big_text']);
                    $con->query('UPDATE '.$checkVal->name_tableDb.' SET small_text="' .substr($st, 0, 30). '" WHERE id='.$line['id']);
                }
            }
        }
        $con->close();
    }
}