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

    public function GetMechanics()
    {
        $query = "SELECT *
            FROM mechanics m
            WHERE m.is_active = 1
            ORDER BY name";
        $result = mysqli_query($this->link, $query);
        $mechanics = [];
        while($mechanic = mysqli_fetch_assoc($result))
        {
            array_push($mechanics, [
                "id_mechanic" => $mechanic["id_mechanic"],
                "name" => $mechanic["name"]
            ]);
        }
        return $mechanics;
    }

    public function GetRespondents()
    {
        $query = "SELECT *
            FROM respondents r
            ORDER BY name";
        $result = mysqli_query($this->link, $query);
        $respondents = [];
        while($respondent = mysqli_fetch_assoc($result))
        {
            array_push($respondents, [
                "id_respondent" => $respondent["id_respondent"],
                "name" => $respondent["name"]
            ]);
        }
        return $respondents;
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
                   '<span class="label label-warning">Неактивный</span>' :
                   '<span class="label label-success">Активный</span>'
            ]);
        }
        return $cars;
    }

    public function GetCarInfo($id_car)
    {
        $query = "SELECT cm.model, c.type, c.number, ROUND(IFNULL(fc.fuel_consumption, 0), 3) AS fuel_consumption,
                IFNULL(m.mileage_after, 0) AS mileage_after,
                c.is_active, IFNULL(fl.`limit`, 0) AS fuel_month_limit
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
                LEFT JOIN (SELECT * FROM fuel_month_limit fl
                  WHERE fl.id_car = $id_car AND fl.start_date <= NOW()
                  ORDER BY fl.start_date DESC
                  LIMIT 1) fl ON c.id = fl.id_car
              WHERE c.id = $id_car";
        $result = mysqli_query($this->link, $query);
        $car = mysqli_fetch_assoc($result);
        return [
            "id_car" => $id_car,
            "model" => $car["model"],
            "number" => $car["number"],
            "type" => $car["type"],
            "current_fuel_consumption" => $car["fuel_consumption"],
            "current_fuel_month_limit" => $car["fuel_month_limit"],
            "current_mileages" => $car["mileage_after"],
            "is_active" => $car["is_active"]
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

    public function GetCarActsPageCount($id_car)
    {
        $query = "SELECT COUNT(*) AS cnt FROM cars_repair_acts WHERE id_car = $id_car";
        $result = mysqli_query($this->link, $query);
        return ceil(mysqli_fetch_assoc($result)["cnt"] / 10);
    }

    public function GetCarActs($id_car, $page)
    {
        $start_limit = ($page - 1)*10;
        $query = "SELECT
              cra.id_repair,
              cra.repair_act_number,
              cra.work_performed,
              e.materials,
              IF(cra.repair_start_date IS NULL AND cra.repair_end_date IS NOT NULL, DATE_FORMAT(cra.repair_end_date, '%d.%m.%Y %H:%i'),
              IF (cra.repair_end_date IS NULL AND cra.repair_start_date IS NOT NULL, DATE_FORMAT(cra.repair_start_date, '%d.%m.%Y %H:%i'),
              IF (date(cra.repair_start_date) <> date(cra.repair_end_date),
                  CONCAT(DATE_FORMAT(cra.repair_start_date, '%d.%m.%Y %H:%i'), ' - ', DATE_FORMAT(cra.repair_end_date, '%d.%m.%Y %H:%i')),
                  CONCAT(DATE_FORMAT(cra.repair_start_date, '%d.%m.%Y %H:%i'), '-', DATE_FORMAT(cra.repair_end_date, '%H:%i')))
              )) AS repair_date,
              IF(cra.wait_start_date IS NULL AND cra.wait_end_date IS NOT NULL, DATE_FORMAT(cra.wait_end_date, '%d.%m.%Y %H:%i'),
              IF (cra.repair_end_date IS NULL AND cra.wait_start_date IS NOT NULL, DATE_FORMAT(cra.wait_start_date, '%d.%m.%Y %H:%i'),
              IF (date(cra.wait_start_date) <> date(cra.wait_end_date),
                  CONCAT(DATE_FORMAT(cra.wait_start_date, '%d.%m.%Y %H:%i'), ' - ', DATE_FORMAT(cra.wait_end_date, '%d.%m.%Y %H:%i')),
                  CONCAT(DATE_FORMAT(cra.wait_start_date, '%d.%m.%Y %H:%i'), '-', DATE_FORMAT(cra.wait_end_date, '%H:%i')))
              )) AS wait_date, m.name AS mechanic, cra.reason_for_repairs,  cra.deleted
            FROM cars_repair_acts cra
              INNER JOIN mechanics m ON cra.id_performer = m.id_mechanic
              LEFT JOIN
                (SELECT e.id_repair, GROUP_CONCAT(e.material ORDER BY e.material SEPARATOR '<br>') AS materials
                  FROM expended e GROUP BY e.id_repair) e ON cra.id_repair = e.id_repair
            WHERE cra.id_car = $id_car
            ORDER BY cra.id_repair DESC
            LIMIT $start_limit, 10";
        $result = mysqli_query($this->link, $query);
        $acts = [];
        while($act = mysqli_fetch_assoc($result))
        {
            array_push($acts, [
                "id_repair" => $act["id_repair"],
                "number" => $act["repair_act_number"],
                "repair_date" => $act["repair_date"],
                "wait_date" => $act["wait_date"],
                "work_performed" => $act["work_performed"],
                "materials" => $act["materials"],
                "mechanic" => $act["mechanic"],
                "reason_for_repairs" => $act["reason_for_repairs"],
                "state" => $act["deleted"] == 1 ?
                    '<span class="label label-warning">Удаленный</span>' :
                    '<span class="label label-success">Действительный</span>',
                "deleted" => $act["deleted"]
            ]);
        }
        return $acts;
    }

    public function UpdateFuelConsumption($carId, $fuelConsumption, $fuelConsumptionDate)
    {
        $deleteQuery = "DELETE FROM fuel_consumption WHERE id_car = ? AND DATE_FORMAT(start_date, '%d.%m.%Y') = ?";
        $insertQuery = "INSERT INTO fuel_consumption(id_car, start_date, fuel_consumption)
                        VALUES(?,STR_TO_DATE(?, '%d.%m.%Y'),?)";
        $selectQuery = "SELECT CAST(fc.fuel_consumption AS DECIMAL(18,3)) AS fuel_consumption
                        FROM fuel_consumption fc
                        WHERE fc.id_car = $carId AND fc.start_date <= NOW()
                        ORDER BY fc.start_date DESC
                        LIMIT 1";
        $deleteStmt = mysqli_prepare($this->link, $deleteQuery);
        mysqli_stmt_bind_param($deleteStmt, 'is', $carId, $fuelConsumptionDate);
        mysqli_stmt_execute($deleteStmt);
        if (mysqli_errno($this->link) <> 0)
        {
            die('Ошибка удаления старой нормы расхода топлива из базы данных');
        }
        $insertQuery = mysqli_prepare($this->link, $insertQuery);
        mysqli_stmt_bind_param($insertQuery, 'isd', $carId, $fuelConsumptionDate, $fuelConsumption);
        mysqli_stmt_execute($insertQuery);
        if (mysqli_errno($this->link) <> 0)
        {
            die('Ошибка добавления нормы расхода топлива');
        }
        $result = mysqli_query($this->link, $selectQuery);
        $row = mysqli_fetch_assoc($result);
        if ($row == null)
        {
            return "0.000";
        }
        return $row["fuel_consumption"];
    }

    public function UpdateFuelMonthLimit($carId, $fuelMonthLimit, $fuelMonthLimitDate)
    {
        $deleteQuery = "DELETE FROM fuel_month_limit WHERE id_car = ? AND DATE_FORMAT(start_date, '%d.%m.%Y') = ?";
        $insertQuery = "INSERT INTO fuel_month_limit(id_car, start_date, `limit`)
                        VALUES(?,STR_TO_DATE(?, '%d.%m.%Y'),?)";
        $selectQuery = "SELECT CAST(fml.limit AS DECIMAL(18,3)) AS fuel_month_limit
                        FROM fuel_month_limit fml
                        WHERE fml.id_car = $carId AND fml.start_date <= NOW()
                        ORDER BY fml.start_date DESC
                        LIMIT 1";
        $deleteStmt = mysqli_prepare($this->link, $deleteQuery);
        mysqli_stmt_bind_param($deleteStmt, 'is', $carId, $fuelMonthLimitDate);
        mysqli_stmt_execute($deleteStmt);
        if (mysqli_errno($this->link) <> 0)
        {
            die('Ошибка удаления старого лимита расхода топлива в месяц из базы данных');
        }
        $insertQuery = mysqli_prepare($this->link, $insertQuery);
        mysqli_stmt_bind_param($insertQuery, 'isd', $carId, $fuelMonthLimitDate, $fuelMonthLimit);
        mysqli_stmt_execute($insertQuery);
        if (mysqli_errno($this->link) <> 0)
        {
            die('Ошибка добавления лимита расхода топлива в месяц');
        }
        $result = mysqli_query($this->link, $selectQuery);
        $row = mysqli_fetch_assoc($result);
        if ($row == null)
        {
            return "0.000";
        }
        return $row["fuel_month_limit"];
    }
}