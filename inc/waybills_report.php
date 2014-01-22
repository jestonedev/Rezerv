<?php
include_once('tbs_class.php');
include_once('plugins/tbs_plugin_opentbs.php');
include_once 'const.php';

$TBS = new clsTinyButStrong;
$TBS->Plugin(TBS_INSTALL, OPENTBS_PLUGIN);

if (isset($_GET['id_report_type']) && $_GET['id_report_type'] == 2)
    $TBS->LoadTemplate('../report_templates/waybill_report_night.ods');
else
    $TBS->LoadTemplate('../report_templates/waybill_report_day.ods');
$TBS->SetOption('charset', 'UTF-8');

$con=mysqli_connect(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_BASE);
if (mysqli_connect_errno())
    die("Ошибка соединения с БД");
mysqli_query($con,"SET NAMES 'utf8'");

if (!isset($_GET['id_waybill']))
    die('Не объявлена переменная id_waybill');
$id_waybill = $_GET['id_waybill'];

$query = "SELECT w.waybill_number, w.start_date, w.end_date, c.model, c.number,
  d.name as driver, m.name as mechanic, ds.name as dispatcher, d.employee_code, d.license_number, d.class, da.abbr AS department, w.address_supply, ft.fuel_type, w.mileage_before, w.mileage_after,
  w.given_fuel, w.fuel_before, ROUND(w.fuel_before - ABS(w.mileage_after-w.mileage_before)*fc.fuel_consumption/100 + IFNULL(w.given_fuel, 0),3) AS fuel_after,
  fc.fuel_consumption AS rate_of_fuel_consumption, ROUND(ABS(w.mileage_after-w.mileage_before)*fc.fuel_consumption/100,3) AS rate_of_fuel_factical
FROM waybills w
  LEFT JOIN cars c ON (c.id = w.id_car)
  LEFT JOIN drivers d  USING (id_driver)
  LEFT JOIN mechanics m  USING (id_mechanic)
  LEFT JOIN dispatchers ds  USING (id_dispatcher)
  LEFT JOIN fuel_types ft USING (id_fuel_type)
  LEFT JOIN dep_abbrs da USING (department)
  LEFT JOIN fuel_consumption fc ON
    (c.id = fc.id_car AND DATE(fc.start_date) <= DATE(w.start_date) AND DATE(fc.end_date) >= DATE(w.start_date))
WHERE w.id_waybill = ".addslashes($id_waybill);

$query_expended = "SELECT * FROM ways WHERE id_waybill=".addslashes($id_waybill);
$result = mysqli_query($con, $query);
$result_ways = mysqli_query($con, $query_expended);
if (!$result || !$result_ways)
    $die("Ошибка выполнения запроса");
$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
foreach($row as $key => $value)
{
    $row[$key] = stripslashes($value);
}
$array = array();
while ($row_way = mysqli_fetch_array($result_ways, MYSQLI_ASSOC))
{
    $row_way["way"] = stripslashes($row_way["way"]);
    $row_way["start_time"] = stripslashes($row_way["start_time"]);
    $row_way["end_time"] = stripslashes($row_way["end_time"]);
    $row_way["distance"] = stripslashes($row_way["distance"]);
    array_push($array, $row_way);
}
$row["ways"] = $array;

if ($con)
    mysqli_close($con);

//Переменные, которые надо заполнить
$waybill_number = $row['waybill_number'];
$date = $row["start_date"];
$date_parts = explode(" ",$date);
$date_parts = explode("-", $date_parts[0]);
$day = intval($date_parts[2]);
$month = intval($date_parts[1]);
$year = intval(substr($date_parts[0], 2));

$date_end = $row["end_date"];
$date_parts_end = explode(" ",$date_end);
$date_parts_end = explode("-", $date_parts_end[0]);
$day_end = intval($date_parts_end[2]);
$month_end = intval($date_parts_end[1]);
$year_end = intval(substr($date_parts_end[0], 2));

$car_model = $row["model"];
$car_num = $row["number"];
$emp_code = $row["employee_code"];
$driver = formatName($row["driver"]);
$mechanic = formatName($row["mechanic"]);
$dispatcher = formatName($row["dispatcher"]);
$license_num = $row["license_number"];
$drive_class = $row["class"];
$department = $row["department"];
$address_supply = $row["address_supply"];
$mileage_before = $row["mileage_before"];
$mileage_after = $row["mileage_after"];
$fuel_type = $row["fuel_type"];
$given_fuel = $row["given_fuel"];
$fuel_before = $row["fuel_before"];
$fuel_after = $row["fuel_after"];
$rate_of_fuel_consumption = $row["rate_of_fuel_consumption"];
$fuel_factical = $row["rate_of_fuel_factical"];
$economy = "";
$overrun = "";
$time_work = "12";
$full_distance = "";

for ($i = 0; $i < 9; $i++)
{
    ${'num'.($i+1)} = "";
    ${'way'.($i+1)} = "";
    ${'hour'.($i+1)."_out"} = "";
    ${'min'.($i+1)."_out"} = "";
    ${'hour'.($i+1)."_return"} = "";
    ${'min'.($i+1)."_return"} = "";
    ${'distance'.($i+1)} = "";
}

for ($i = 0; $i < sizeof($array); $i++)
{
    ${'num'.($i+1)} = $i+1;
    ${'way'.($i+1)} = $array[$i]["way"];
    $start_time = $array[$i]["start_time"];
    $start_time_parts = explode(":", $start_time);
    ${'hour'.($i+1)."_out"} = intval($start_time_parts[0]);
    ${'min'.($i+1)."_out"} = $start_time_parts[1];
    $end_time = $array[$i]["end_time"];
    $end_time_parts = explode(":", $end_time);
    ${'hour'.($i+1)."_return"} = intval($end_time_parts[0]);
    ${'min'.($i+1)."_return"} = $end_time_parts[1];
    ${'distance'.($i+1)} = $array[$i]["distance"];
    $full_distance += $array[$i]["distance"];
}

$TBS->Show(OPENTBS_DOWNLOAD);

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