<?php
include_once "const.php";

class CarsClass
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

    public function GetDrivers()
    {
        $query = "SELECT *
            FROM drivers d
            WHERE d.is_active = 1
            ORDER BY name";
        $result = mysqli_query($this->link, $query);
        $drivers = [];
        while($driver = mysqli_fetch_assoc($result))
        {
            array_push($drivers, [
                "id_driver" => $driver["id_driver"],
                "name" => $driver["name"]
            ]);
        }
        return $drivers;
    }

    public function GetFuelTypes()
    {
        $query = "SELECT *
            FROM fuel_types ft
            ORDER BY fuel_type";
        $result = mysqli_query($this->link, $query);
        $fuelTypes = [];
        while($driver = mysqli_fetch_assoc($result))
        {
            array_push($fuelTypes, [
                "id_fuel_type" => $driver["id_fuel_type"],
                "fuel_type" => $driver["fuel_type"]
            ]);
        }
        return $fuelTypes;
    }

    public function GetCars()
    {
        $query = "SELECT c.id, cm.model, c.number, c.type, cc.name AS chief, c.department_default,
            c.is_active
            FROM cars c
              INNER JOIN car_models cm ON c.id_model = cm.id_model
              INNER JOIN cars_chiefs cc ON c.id_chief_default = cc.id_chief
            ORDER BY c.number";
        $result = mysqli_query($this->link, $query);
        $cars = [];
        while($car = mysqli_fetch_assoc($result))
        {
            array_push($cars, [
               "id" => $car["id"],
               "model" => $car["model"],
               "number" => $car["number"],
               "type" => $car["type"],
               "chief" => $car["chief"],
               "department_default" => $car["department_default"],
               "state" => $car["is_active"] == 0 ?
                   '<span class="label label-warning">Не активный</span>' :
                   '<span class="label label-success">Активный</span>'
            ]);
        }
        return $cars;
    }

    public function GetCarInfo($id_car)
    {
        $query = "SELECT cm.model, c.type, c.number, ROUND(IFNULL(fc.fuel_consumption, 0), 3) AS fuel_consumption,
                  IFNULL(m.mileage_after, 0) AS mileage_after
                FROM cars c
                  LEFT JOIN car_models cm ON c.id_model = cm.id_model
                  LEFT JOIN (
                      SELECT fc.fuel_consumption, fc.id_car
                      FROM fuel_consumption fc
                      WHERE fc.id_car = $id_car AND fc.start_date <= NOW()
                      ORDER BY fc.start_date DESC
                      LIMIT 1
                      ) fc ON c.id = fc.id_car
                  LEFT JOIN (SELECT w.id_car, w.mileage_after
                      FROM waybills w
                      WHERE w.id_car  = $id_car AND w.deleted <> 1 AND w.end_date <= NOW()
                      ORDER BY w.start_date DESC
                      LIMIT 1) m ON c.id = m.id_car
                WHERE c.id = $id_car";
        $result = mysqli_query($this->link, $query);
        $car = mysqli_fetch_assoc($result);
        return [
            "id_car" => $id_car,
            "model" => $car["model"],
            "number" => $car["number"],
            "type" => $car["type"],
            "current_fuel_consumption" => $car["fuel_consumption"],
            "current_mileages" => $car["mileage_after"]
        ];
    }

    public function GetCarWaybillsPageCount($id_car)
    {
        $query = "SELECT COUNT(*) AS cnt FROM waybills WHERE id_car = $id_car";
        $result = mysqli_query($this->link, $query);
        return ceil(mysqli_fetch_assoc($result)["cnt"] / 10);
    }

    public function GetCarWaybills($id_car, $page)
    {
        $start_limit = ($page - 1)*10;
        $query = "SELECT w.id_waybill, w.waybill_number,
              IF (w.start_date <> w.end_date,
              CONCAT(DATE_FORMAT(w.start_date, '%d.%m.%Y'), ' - ', DATE_FORMAT(w.end_date, '%d.%m.%Y')),
              DATE_FORMAT(w.start_date, '%d.%m.%Y')) AS travel_date,
              w.mileage_after - w.mileage_before AS mileages,
              ROUND((w.mileage_after - w.mileage_before)*v.fuel_consumption/100, 3) AS fuel_consumption,
              w.deleted AS deleted
            FROM waybills w LEFT JOIN (
            SELECT w.id_waybill, fc.*
            FROM waybills w INNER JOIN fuel_consumption fc ON w.id_car = fc.id_car
              INNER JOIN (
            SELECT w.id_waybill, MAX(fc.start_date) AS fc_start_date
            FROM waybills w LEFT JOIN
              fuel_consumption fc ON w.id_car = fc.id_car
            WHERE fc.start_date < w.start_date
            GROUP BY w.id_waybill) v ON fc .start_date = v.fc_start_date AND w.id_waybill = v.id_waybill) v ON w.id_waybill = v.id_waybill
            WHERE w.id_car = $id_car
            ORDER BY w.start_date DESC
            LIMIT $start_limit, 10";
        $result = mysqli_query($this->link, $query);
        $waybills = [];
        while($waybill = mysqli_fetch_assoc($result))
        {
            array_push($waybills, [
                "id_waybill" => $waybill["id_waybill"],
                "number" => $waybill["waybill_number"],
                "travel_date" => $waybill["travel_date"],
                "mileages" => $waybill["mileages"],
                "fuel_consumption" => $waybill["fuel_consumption"],
                "state" => $waybill["deleted"] == 1 ?
                '<span class="label label-warning">Удаленный</span>' :
                '<span class="label label-success">Действительный</span>',
                "deleted" => $waybill["deleted"]
            ]);
        }
        return $waybills;
    }
}