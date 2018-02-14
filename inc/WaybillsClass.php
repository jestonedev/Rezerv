<?php
/**
 * Created by JetBrains PhpStorm.
 * User: IgnVV
 * Date: 04.10.13
 * Time: 10:46
 * To change this template use File | Settings | File Templates.
 */
include_once 'const.php';
include_once 'auth.php';

class WaybillsClass
{
    var $link;

    private function fatal_error ( $sErrorMessage = '' )
    {
        header( $_SERVER['SERVER_PROTOCOL'] .' 500 Internal Server Error ' );
        die( $sErrorMessage );
    }

    public function __construct()
    {
        $this->link = mysqli_connect(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_BASE) or
            $this->fatal_error("Ошибка подключения к базе данных");
        mysqli_query($this->link,"SET NAMES 'utf8'");
    }

    public function __destruct()
    {
        if ($this->link)
            mysqli_close($this->link);
    }

    public function AutoCompleteDetails($idCar)
    {
        $queryWaybill = "SELECT w.mileage_after, w.fuel_after
            FROM waybills w
            WHERE w.id_car = $idCar AND w.deleted <> 1
            ORDER BY w.start_date DESC, id_waybill DESC
            LIMIT 1";
        $queryCarInfo = "SELECT c.id_fuel_default, c.id_driver_default, c.department_default
            FROM cars c
            WHERE c.id = $idCar";
        $waybillNumberQuery = "SELECT IFNULL(MAX(waybill_number), 0) + 1 AS waybill_number FROM waybills";
        $resultWaybill = mysqli_query($this->link, $queryWaybill);
        $resultWaybill = mysqli_fetch_assoc($resultWaybill);
        $resultCarInfo = mysqli_query($this->link, $queryCarInfo);
        $resultCarInfo = mysqli_fetch_assoc($resultCarInfo);
        $waybillNumberInfo = mysqli_query($this->link, $waybillNumberQuery);
        $waybillNumberInfo = mysqli_fetch_assoc($waybillNumberInfo);
        $waybillInfo = ["mileage_after" => 0,
            "fuel_after" => 0];
        $carInfo = ["id_fuel_default" => 1,
            "id_driver_default" => 1,
            "department_default" => "Администрация"];
        if ($resultWaybill != null)
        {
            $waybillInfo = ["mileage_after" => $resultWaybill["mileage_after"],
                "fuel_after" => $resultWaybill["fuel_after"]];
        }
        if ($resultCarInfo != null)
        {
            $carInfo = ["id_fuel_default" => $resultCarInfo["id_fuel_default"],
                "id_driver_default" => $resultCarInfo["id_driver_default"],
                "department_default" => $resultCarInfo["department_default"]];
        }
        return array_merge($waybillInfo, $carInfo, ["waybill_number" => $waybillNumberInfo["waybill_number"]]);
    }

    public function GetWaybillInfo($idWaybill)
    {
        $query = "SELECT
              w.id_waybill, w.id_car, w.id_driver, w.waybill_number,
              DATE_FORMAT(w.start_date,'%d.%m.%Y') AS start_date,
              DATE_FORMAT(w.end_date,'%d.%m.%Y') AS end_date,
              w.department, w.mileage_before,
              w.mileage_after, w.fuel_before, w.given_fuel,
              w.fuel_after, w.id_fuel_type , c.number AS car_number
            FROM waybills w INNER JOIN cars c ON w.id_car = c.id
            WHERE w.id_waybill=".addslashes($idWaybill);
        $query_ways = "SELECT * FROM ways WHERE id_waybill=".addslashes($idWaybill);
        $result = mysqli_query($this->link, $query);
        $result_ways = mysqli_query($this->link, $query_ways);
        if (!$result || !$result_ways)
            $this->fatal_error("Ошибка выполнения запроса");
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        foreach($row as $key => $value)
        {
            $row[$key] = stripslashes($value);
        }
        $array = array();
        while ($row_ways = mysqli_fetch_array($result_ways, MYSQLI_ASSOC))
        {
            $row_ways["way"] = stripslashes($row_ways["way"]);
            $row_ways["start_time"] = stripslashes($row_ways["start_time"]);
            $row_ways["end_time"] = stripslashes($row_ways["end_time"]);
            $row_ways["distance"] = stripslashes($row_ways["distance"]);
            array_push($array, $row_ways);
        }
        $row["ways"] = $array;
        return $row;
    }



    public function InsertWaybill($args)
    {
        if (!Auth::hasPrivilege(AUTH_MANAGE_TRANSPORT))
            $this->fatal_error('У вас нет прав на создание путевых листов');
        $query = "INSERT INTO waybills(id_car, id_driver, waybill_number,
            start_date, end_date, department,
            mileage_before, mileage_after, fuel_before, given_fuel, fuel_after, id_fuel_type)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
        $pq = mysqli_prepare($this->link, $query);

        $car_id = intval($args['car_id']);
        $driver_id = intval($args['driver_id']);
        $number = $args['number'];
        $number = (trim($number) == "") ? null : trim($number);
        $department = stripslashes($args['department']);
        $mileage_before = $args['mileage_before'];
        $mileage_before = (trim($mileage_before) == "") ? null : trim($mileage_before);
        $mileages = $args['mileages'];
        $mileages = (trim($mileages) == "") ? null : trim($mileages);
        $mileage_after = null;
        if ($mileage_before != null && $mileages != null) {
            $mileage_after = intval($mileage_before) + intval($mileages);
        } else
            if ($mileage_before != null) {
                $mileage_after = $mileage_before;
            }
        $fuel_before = $args['fuel_before'];
        $fuel_before = (trim($fuel_before) == "") ? null : trim($fuel_before);
        $given_fuel = $args['given_fuel'];
        $given_fuel = (trim($given_fuel) == "") ? null : trim($given_fuel);
        $fuel_type_id = $args['fuel_type_id'];
        $start_date_parts = explode('.', $args['start_date']);
        $start_date = $start_date_parts[2].'-'.$start_date_parts[1].'-'.$start_date_parts[0];
        $end_date_parts = explode('.', $args['end_date']);
        $end_date = $end_date_parts[2].'-'.$end_date_parts[1].'-'.$end_date_parts[0];

        $fuelConsumptionQuery = "SELECT fc.fuel_consumption
            FROM fuel_consumption fc
            WHERE fc.id_car = $car_id AND date(fc.start_date) <= STR_TO_DATE('$start_date', '%Y-%m-%d')
            ORDER BY fc.start_date DESC
            LIMIT 1";
        $fuelConsumptionResult = mysqli_query($this->link, $fuelConsumptionQuery);
        if (!$fuelConsumptionResult)
            $this->fatal_error('Ошибка при выполнении запроса к базе данных');
        $fuelConsumptionRow = mysqli_fetch_array($fuelConsumptionResult, MYSQLI_ASSOC);
        $fuelConsumption = 0;
        if ($fuelConsumptionRow != null)
            $fuelConsumption = $fuelConsumptionRow["fuel_consumption"];
        $fuel_after = floatval($fuel_before == null ? 0 : $fuel_before) -
            (intval($mileage_after == null ? 0 : $mileage_after) -
                intval($mileage_before == null ? 0 : $mileage_before))*$fuelConsumption / 100 +
            ($given_fuel == null ? 0 : floatval($given_fuel));

        mysqli_stmt_bind_param($pq,'iiisssiidddi',$car_id, $driver_id, $number, $start_date,
            $end_date, $department,
            $mileage_before, $mileage_after, $fuel_before, $given_fuel, $fuel_after, $fuel_type_id);
        mysqli_stmt_execute($pq);
        if (mysqli_errno($this->link)!=0)
        {
            mysqli_rollback($this->link);
            $this->fatal_error('Ошибка выполнения запроса к базе данных');
        }
        $waybill_id = mysqli_insert_id($this->link);

        $ways_strs = explode('$', $args['ways_list']);
        foreach ($ways_strs as $ways_str)
        {
            if (empty($ways_str))
                continue;
            $args = explode('@',$ways_str);
            if (count($args) != 4)
            {
                $this->fatal_error('Некорректный формат параметра "Маршрут следования"');
            }
            $query = "insert into ways (id_waybill, way, start_time, end_time, distance) values (?,?,?,?,?)";
            $pq = mysqli_prepare($this->link, $query);
            $way = (trim($args[0]) == "") ? null : trim($args[0]);
            $startDate = (trim($args[1]) == "") ? null : trim($args[1]);
            $endDate = (trim($args[2]) == "") ? null : trim($args[2]);
            $distance = (trim($args[3]) == "") ? null : trim($args[3]);
            mysqli_stmt_bind_param($pq, 'isssi', $waybill_id, $way, $startDate, $endDate, $distance);
            mysqli_stmt_execute($pq);
            if (mysqli_errno($this->link)!=0)
            {
                mysqli_rollback($this->link);
                $this->fatal_error('Ошибка выполнения запроса к базе данных');
            }
        }
        mysqli_stmt_close($pq);
        mysqli_commit($this->link);
        return "";
    }

    public function DeleteWaybill($id_waybill)
    {
        if (!Auth::hasPrivilege(AUTH_MANAGE_TRANSPORT))
            $this->fatal_error('У вас нет прав на удаление акта выполненных работ');
        $query = "UPDATE waybills SET deleted = 1 WHERE id_waybill = ?";
        $pq = mysqli_prepare($this->link, $query);
        mysqli_stmt_bind_param($pq,'i', $id_waybill);
        mysqli_stmt_execute($pq);
        if (mysqli_errno($this->link)!=0)
        {
            mysqli_rollback($this->link);
            $this->fatal_error('Ошибка выполнения запроса к базе данных');
        }
        mysqli_stmt_close($pq);
        mysqli_commit($this->link);
    }

    public function UpdateWaybill($args)
    {
        if (!Auth::hasPrivilege(AUTH_MANAGE_TRANSPORT))
            $this->fatal_error('У вас нет прав на изменение путевого листа');
        $query = "UPDATE waybills SET id_car = ?, id_driver = ?, waybill_number = ?, start_date = ?, end_date = ?,
          department = ?, mileage_before = ?, mileage_after = ?, fuel_before = ?,
          given_fuel = ?, fuel_after = ?, id_fuel_type = ? WHERE id_waybill = ?";
        $pq = mysqli_prepare($this->link, $query);
        $car_id = intval($args['car_id']);
        $driver_id = intval($args['driver_id']);
        $number = $args['number'];
        $number = (trim($number) == "") ? null : trim($number);
        $department = stripslashes($args['department']);
        $mileage_before = $args['mileage_before'];
        $mileage_before = (trim($mileage_before) == "") ? null : trim($mileage_before);
        $mileages = $args['mileages'];
        $mileages = (trim($mileages) == "") ? null : trim($mileages);
        $mileage_after = null;
        if ($mileage_before != null && $mileages != null) {
            $mileage_after = intval($mileage_before) + intval($mileages);
        } else
            if ($mileage_before != null) {
                $mileage_after = $mileage_before;
            }
        $fuel_before = $args['fuel_before'];
        $fuel_before = (trim($fuel_before) == "") ? null : trim($fuel_before);
        $given_fuel = $args['given_fuel'];
        $given_fuel = (trim($given_fuel) == "") ? null : trim($given_fuel);
        $fuel_type_id = $args['fuel_type_id'];
        $start_date_parts = explode('.', $args['start_date']);
        $start_date = $start_date_parts[2].'-'.$start_date_parts[1].'-'.$start_date_parts[0];
        $end_date_parts = explode('.', $args['end_date']);
        $end_date = $end_date_parts[2].'-'.$end_date_parts[1].'-'.$end_date_parts[0];
        $waybill_id = $args['waybill_id'];
        $fuelConsumptionQuery = "SELECT fc.fuel_consumption
            FROM fuel_consumption fc
            WHERE fc.id_car = $car_id AND date(fc.start_date) <= STR_TO_DATE('$start_date', '%Y-%m-%d')
            ORDER BY fc.start_date DESC
            LIMIT 1";
        $fuelConsumptionResult = mysqli_query($this->link, $fuelConsumptionQuery);
        if (!$fuelConsumptionResult)
            $this->fatal_error('Ошибка при выполнении запроса к базе данных');
        $fuelConsumptionRow = mysqli_fetch_array($fuelConsumptionResult, MYSQLI_ASSOC);
        $fuelConsumption = 0;
        if ($fuelConsumptionRow != null)
            $fuelConsumption = $fuelConsumptionRow["fuel_consumption"];
        $fuel_after = floatval($fuel_before == null ? 0 : $fuel_before) -
            (intval($mileage_after == null ? 0 : $mileage_after) -
                intval($mileage_before == null ? 0 : $mileage_before))*$fuelConsumption / 100 +
            ($given_fuel == null ? 0 : floatval($given_fuel));

        mysqli_stmt_bind_param($pq,'iiisssiidddii',$car_id, $driver_id, $number, $start_date, $end_date,
            $department, $mileage_before, $mileage_after, $fuel_before, $given_fuel, $fuel_after,
            $fuel_type_id, $waybill_id);
        mysqli_stmt_execute($pq);
        if (mysqli_errno($this->link)!=0)
        {
            mysqli_rollback($this->link);
            $this->fatal_error('Ошибка выполнения запроса к базе данных');
        }

        $query = "delete from ways where id_waybill = ?";
        $pq = mysqli_prepare($this->link, $query);
        mysqli_stmt_bind_param($pq, 'i', $waybill_id);
        mysqli_stmt_execute($pq);
        if (mysqli_errno($this->link)!=0)
        {
            mysqli_rollback($this->link);
            $this->fatal_error('Ошибка выполнения запроса к базе данных');
        }
        $ways_strs = explode('$', $args['ways_list']);
        foreach ($ways_strs as $ways_str)
        {
            if (empty($ways_str))
                continue;
            $args = explode('@',$ways_str);
            if (count($args) != 4)
            {
                die('Некорректный формат параметра "Маршрут следования"');
            }
            $query = "insert into ways (id_waybill, way, start_time, end_time, distance) values (?,?,?,?,?)";
            $pq = mysqli_prepare($this->link, $query);
            $way = (trim($args[0]) == "") ? null : trim($args[0]);
            $startDate = (trim($args[1]) == "") ? null : trim($args[1]);
            $endDate = (trim($args[2]) == "") ? null : trim($args[2]);
            $distance = (trim($args[3]) == "") ? null : trim($args[3]);
            mysqli_stmt_bind_param($pq, 'isssi', $waybill_id, $way, $startDate, $endDate, $distance);
            mysqli_stmt_execute($pq);
            if (mysqli_errno($this->link)!=0)
            {
                mysqli_rollback($this->link);
                $this->fatal_error('Ошибка выполнения запроса к базе данных');
            }
        }
        mysqli_stmt_close($pq);
        mysqli_commit($this->link);
        return "";
    }

    ///////////////////////////////////////////////////////////////////////////
    //Интерфейс IQuery. Декларирует параметры для корректной работы DataTable//
    ///////////////////////////////////////////////////////////////////////////

    public function Columns() {
        return array('edit_lbl','waybill_number','car','start_date','status');
    }

    public function Table() {
        return "(SELECT CONCAT('<img src=\'img/details_open.png\' value=\'',id_waybill,'\'>') AS edit_lbl, w.waybill_number,
            IFNULL(CONCAT(cm.model,' г/н ',c.number), c.type) AS car,  DATE(w.start_date) AS start_date, IF(w.deleted = 0, 'Действительный', 'Удаленный') AS `status`
            FROM waybills w
            LEFT JOIN cars c ON (w.id_car = c.id)
            LEFT JOIN car_models cm ON c.id_model = cm.id_model) t";
    }

    public function Where() {
        return "";
    }

    public function IndexColumn() {
        return "waybill_number";
    }

    public function DisplayColumnNames()
    {
        return '{"head":"<tr><th></th><th>Номер листа</th><th>Транспорт</th><th>Дата создания</th><th>Статус</th></tr>",
                 "foot":"<tr><th></th><th>Номер листа</th><th>Транспорт</th><th>Дата создания</th><th>Статус</th></tr>"}';
    }

    public function FilterColumnsData($column, $data)
    {
        $sOutput = "";
        if ($column == "status")
        {
            switch ($data)
            {
                case "Действительный":
                    $sOutput .= '<span class=\'act_active_status\'>'.Helper::ClearJsonString($data).'</span>';
                    break;
                case "Удаленный":
                    $sOutput .= '<span class=\'act_deleted_status\'>'.Helper::ClearJsonString($data).'</span>';
                    break;
                default:
                    $sOutput .= Helper::ClearJsonString($data);
                    break;
            }
        } else
        if ( $column == "edit_lbl")
        {
            $sOutput .= $data;
        } else
            $sOutput .= Helper::ClearJsonString($data);
        return $sOutput;
    }
}
