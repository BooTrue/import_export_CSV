<?php
include ('templates/default.html');
include ('init.php');

// id авторизованного пользователя
$user_id = 123;

// Параметры для подключения к БД
$con = (new ConnectDb(
    $server = 'localhost',
    $user = 'web',
    $passwd = 'super',
    $db = 'new_test'
));

// Проверка поля "small_text" из БД и добавление значения из 'big_text'
CheckValues::runCheck($con, (new CheckValues ($name_tableDb = 'product')));

// Экспорт CSV из БД
$csv = new ExpImpCsv (
    $name_file = 'export_db.csv',
    $name_tableDb = 'product'
);
$csv->exportCsv($con);

// Импорт CSV в БД
if (isset($_POST['button_import'])){
    if($_FILES['file_csv']['type'] == 'text/csv'){
        $csv = new ExpImpCsv (
            $name_file = $_FILES['file_csv']['tmp_name'],
            $name_tableDb = 'product'
        );
        $csv->importCsv($con, $user_id);
        echo("Импорт завершен! Добавлено {$csv->num_insert}/обновлено {$csv->num_update}");
    }
}

?>

