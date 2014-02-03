<?php

include_once('tbs_class.php');
include_once('plugins/tbs_plugin_opentbs.php');
include_once 'const.php';

$TBS = new clsTinyButStrong;
$TBS->Plugin(TBS_INSTALL, OPENTBS_PLUGIN);

$TBS->LoadTemplate('../report_templates/repair_act.odt');
$TBS->SetOption('charset', 'UTF-8');

$con=mysqli_connect(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_BASE);
if (mysqli_connect_errno())
    die("Ошибка соединения с БД");
mysqli_query($con,"SET NAMES 'utf8'");

if (!isset($_GET['id_repair']))
    die('Не объявлена переменная id_repair');
$id_repair = $_GET['id_repair'];

$query = "SELECT cra.*, m.name AS mechanic, d.name AS driver, r.name AS respondent, c.number, c.model
                    FROM cars_repair_acts cra
                      LEFT JOIN mechanics m ON (cra.id_performer = m.id_mechanic)
                      LEFT JOIN cars c ON (cra.id_car = c.id)
                      LEFT JOIN drivers d ON (cra.id_driver = d.id_driver)
                      LEFT JOIN respondents r ON (cra.id_respondent = r.id_respondent) WHERE id_repair=".addslashes($id_repair);
$query_expended = "SELECT * FROM expended WHERE id_repair=".addslashes($id_repair);
$result = mysqli_query($con, $query);
$result_expended = mysqli_query($con, $query_expended);
if (!$result || !$result_expended)
    $die("Ошибка выполнения запроса");
$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
foreach($row as $key => $value)
{
    $row[$key] = stripslashes($value);
}
$array = array();
while ($row_expended = mysqli_fetch_array($result_expended, MYSQLI_ASSOC))
{
    $row_expended["material"] = stripslashes($row_expended["material"]);
    $row_expended["count"] = stripslashes($row_expended["count"]);
    array_push($array, $row_expended);
}
$row["expended"] = $array;

if ($con)
    mysqli_close($con);

$date = formatDate($row['act_date']);
$act_num = $row['repair_act_number'];
$responsible = formatName($row['respondent']);
$car = $row['model'];
$car_num = $row['number'];
$reason = $row['reason_for_repairs'];
$work_performed = $row['work_performed'];
$driver = formatName($row['driver']);
$mechanic = formatName($row['mechanic']);
$odometer = $row['odometer'];
$wait_start_date = formatDate($row['wait_start_date']);
$wait_end_date = formatDate($row['wait_end_date']);
$rep_start_date = formatDate($row['repair_start_date']);
$rep_end_date = formatDate($row['repair_end_date']);
$array = $row["expended"];
$expended_materials = '';
for ($i = 0; $i < sizeof($array); $i++)
{
    $material = $array[$i]["material"];
    $count = $array[$i]["count"];
    $expended_materials .= $material." - ".$count;
    if ($i < (sizeof($array) - 1))
        $expended_materials .= "; ";
}
$TBS->Show(OPENTBS_DOWNLOAD);

function formatDate($date)
{
    if (empty($date))
        return "";
    $date_time_parts = explode(' ', $date);
    $date_parts = $date_time_parts[0];
    $date_arr = explode('-', $date_parts);
    switch ($date_arr[1])
    {
        case 1: $date_arr[1] = "января";
            break;
        case 2: $date_arr[1] = "февраля";
            break;
        case 3: $date_arr[1] = "марта";
            break;
        case 4: $date_arr[1] = "апреля";
            break;
        case 5: $date_arr[1] = "мая";
            break;
        case 6: $date_arr[1] = "июня";
            break;
        case 7: $date_arr[1] = "июля";
            break;
        case 8: $date_arr[1] = "августа";
            break;
        case 9: $date_arr[1] = "сентября";
            break;
        case 10: $date_arr[1] = "октября";
            break;
        case 11: $date_arr[1] = "ноября";
            break;
        case 12: $date_arr[1] = "декабря";
            break;
    }
    return "«".$date_arr[2]."» ".$date_arr[1]." ".$date_arr[0]." г. ";
}

function formatName($name)
{
    $FIO = explode(' ', $name);
    $result_name = "";
    if (sizeof($FIO) >= 1)
        $result_name = $FIO[0].' ';
    for ($i = 1; $i < sizeof($FIO); $i++)
    {
        $initial = $FIO[$i];
        $result_name .= mb_substr($initial,0,1,'UTF-8').'.';
    }
    return $result_name;
}
?>
