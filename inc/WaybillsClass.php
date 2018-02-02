<?php
/**
 * Created by JetBrains PhpStorm.
 * User: IgnVV
 * Date: 04.10.13
 * Time: 10:46
 * To change this template use File | Settings | File Templates.
 */
include_once 'const.php';

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

    ///////////////////////////////////////////////////////////////////////////
    //Интерфейс IQuery. Декларирует параметры для корректной работы DataTable//
    ///////////////////////////////////////////////////////////////////////////

    public function Columns() {
        return array('edit_lbl','waybill_number','car','start_date','status');
    }

    public function Table() {
        return "(SELECT CONCAT('<img src=\'img/details_open.png\' value=\'',id_waybill,'\'>') AS edit_lbl, w.waybill_number,
            CONCAT(c.model,' г/н ',c.number) AS car,  DATE(w.start_date) AS start_date, IF(w.deleted = 0, 'Действительный', 'Удаленный') AS `status`
            FROM waybills w
            LEFT JOIN cars c ON (w.id_car = c.id)) t";
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
