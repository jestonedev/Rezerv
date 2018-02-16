<?php

include_once "const.php";
include_once "ldap.php";
include_once "auth.php";

class CarReportClass
{
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

    private function CheckReportMonthFormat($month)
    {
        $monthParts = explode('.', $month);
        return count($monthParts) == 2 && strlen($monthParts[0]) == 2 && strlen($monthParts[1]) == 4;
    }

    public function GetFuelByMonthReportData($month)
    {
        if (!$this->CheckReportMonthFormat($month))
        {
            die('Передан некорректный формат месяца');
        }
        $query = "SELECT IFNULL(CONCAT(c.number,' г/н ', cm.model), c.type) AS car,
              IFNULL(fc.fuel_consumption, 'н/а') AS fuel_consumption,
              IFNULL(ROUND(fsm.fuel_start_month, 3), 'н/а') AS fuel_start_month,
              IFNULL(fmql.first_month_of_quartal_limit + smql.second_month_of_quartal_limit
               + tmql.third_month_of_quartal_limit, 'н/а') AS quartal_limit,
              IFNULL(ml.`limit`, 'н/а') AS month_limit,
              IFNULL(fe.factical_fuel, 'н/а') AS factical_fuel,
              IFNULL(fe.factical_mileages, 'н/а') AS factical_mileages,
              IFNULL(ROUND(fem.fuel_end_month, 3), 'н/а') AS fuel_end_month,
              IFNULL(ROUND(ml.`limit` - fe.factical_fuel, 3), 'н/а') AS deviation_fuel
            FROM cars c
              LEFT JOIN car_models cm ON c.id_model = cm.id_model
              LEFT JOIN (
                SELECT fc.id_car, fc.fuel_consumption
                FROM fuel_consumption fc INNER JOIN (
                SELECT fc.id_car, MAX(fc.start_date) AS max_start_date
                FROM fuel_consumption fc
                WHERE fc.start_date <= STR_TO_DATE('01.$month','%d.%m.%Y')
                GROUP BY fc.id_car) fcsd ON fc.id_car = fcsd.id_car AND fc.start_date = fcsd.max_start_date
              ) fc ON c.id = fc.id_car
              LEFT JOIN (SELECT w.id_car, w.end_date,
              IF(w.end_date = STR_TO_DATE(CONCAT(year(STR_TO_DATE('01.$month','%d.%m.%Y')),'-',month(STR_TO_DATE('01.$month','%d.%m.%Y')), '-01'), '%Y-%m-%d'), w.fuel_before,
              w.fuel_after) AS fuel_start_month
            FROM waybills w INNER JOIN (SELECT w.id_car, MIN(w.max_end_date) AS max_end_date
            FROM (
            SELECT w.id_car, MAX(w.end_date) AS max_end_date
                FROM waybills w
                WHERE w.deleted <> 1 AND
                  w.end_date <= STR_TO_DATE(CONCAT(year(STR_TO_DATE('01.$month','%d.%m.%Y')),'-',
                      month(STR_TO_DATE('01.$month','%d.%m.%Y')), '-01'), '%Y-%m-%d')
                GROUP BY w.id_car
            UNION ALL
            SELECT w.id_car, MIN(w.end_date) AS max_end_date
                FROM waybills w
                WHERE w.deleted <> 1 AND
                  w.end_date >= STR_TO_DATE(CONCAT(year(STR_TO_DATE('01.$month','%d.%m.%Y')),'-',
                      month(STR_TO_DATE('01.$month','%d.%m.%Y')), '-01'), '%Y-%m-%d')
                GROUP BY w.id_car) w
            GROUP BY w.id_car) wed ON w.id_car = wed.id_car AND w.end_date = wed.max_end_date) fsm ON c.id = fsm.id_car
              LEFT JOIN (SELECT w.id_car, w.end_date,
              w.fuel_after AS fuel_end_month
            FROM waybills w INNER JOIN (
                SELECT w.id_car, MAX(w.end_date) AS max_end_date
                FROM waybills w
                WHERE w.deleted <> 1 AND w.end_date <= LAST_DAY(STR_TO_DATE('01.$month','%d.%m.%Y'))
                GROUP BY w.id_car) wed ON w.id_car = wed.id_car AND w.end_date = wed.max_end_date) fem ON c.id = fem.id_car
              LEFT JOIN (SELECT fml.id_car, fml.`limit`, MONTH(STR_TO_DATE('01.$month','%d.%m.%Y')) AS month
                FROM fuel_month_limit fml INNER JOIN (
                SELECT fml.id_car, MAX(fml.start_date) AS max_start_date
                FROM fuel_month_limit fml
                WHERE fml.start_date <= STR_TO_DATE('01.$month','%d.%m.%Y') -- change
                GROUP BY fml.id_car) fmlsd ON fml.id_car = fmlsd.id_car AND fml.start_date = fmlsd.max_start_date) ml ON c.id = ml.id_car
              LEFT JOIN (SELECT fml.id_car, fml.`limit` AS first_month_of_quartal_limit
            FROM fuel_month_limit fml INNER JOIN (
            SELECT fml.id_car, MAX(fml.start_date) AS max_start_date
            FROM fuel_month_limit fml
            WHERE fml.start_date <=
              DATE_ADD(STR_TO_DATE(CONCAT('01.01.', YEAR(STR_TO_DATE('01.$month','%d.%m.%Y'))), '%d.%m.%Y'),
              INTERVAL (QUARTER(STR_TO_DATE('01.$month','%d.%m.%Y')) - 1)*3 MONTH)
            GROUP BY fml.id_car) fmlsd ON fml.id_car = fmlsd.id_car AND fml.start_date = fmlsd.max_start_date) fmql ON c.id = fmql.id_car
              LEFT JOIN (SELECT fml.id_car, fml.`limit` AS second_month_of_quartal_limit
            FROM fuel_month_limit fml INNER JOIN (
            SELECT fml.id_car, MAX(fml.start_date) AS max_start_date
            FROM fuel_month_limit fml
            WHERE fml.start_date <=
              DATE_ADD(STR_TO_DATE(CONCAT('01.02.', YEAR(STR_TO_DATE('01.$month','%d.%m.%Y'))), '%d.%m.%Y'),
              INTERVAL (QUARTER(STR_TO_DATE('01.$month','%d.%m.%Y')) - 1)*3 MONTH)
            GROUP BY fml.id_car) fmlsd ON fml.id_car = fmlsd.id_car AND fml.start_date = fmlsd.max_start_date) smql ON c.id = smql.id_car
              LEFT JOIN (SELECT fml.id_car, fml.`limit` AS third_month_of_quartal_limit
            FROM fuel_month_limit fml INNER JOIN (
            SELECT fml.id_car, MAX(fml.start_date) AS max_start_date
            FROM fuel_month_limit fml
            WHERE fml.start_date <=
              DATE_ADD(STR_TO_DATE(CONCAT('01.03.', YEAR(STR_TO_DATE('01.$month','%d.%m.%Y'))), '%d.%m.%Y'),
              INTERVAL (QUARTER(STR_TO_DATE('01.$month','%d.%m.%Y')) - 1)*3 MONTH)
            GROUP BY fml.id_car) fmlsd ON fml.id_car = fmlsd.id_car AND fml.start_date = fmlsd.max_start_date) tmql ON c.id = tmql.id_car
              LEFT JOIN (SELECT w.id_car, SUM(w.mileage_after - w.mileage_before) AS factical_mileages,
              ROUND(SUM(IFNULL(w.fuel_before, 0) + IFNULL(w.given_fuel, 0) - IFNULL(w.fuel_after, 0)), 3) AS factical_fuel
            FROM waybills w
            WHERE w.deleted <> 1 AND w.end_date BETWEEN STR_TO_DATE(CONCAT(year(STR_TO_DATE('01.$month','%d.%m.%Y')),'-',
            month(STR_TO_DATE('01.$month','%d.%m.%Y')), '-01'), '%Y-%m-%d')
              AND LAST_DAY(STR_TO_DATE('01.$month','%d.%m.%Y'))
            GROUP BY w.id_car) fe ON c.id = fe.id_car
            WHERE c.is_active = 1
            ORDER BY car";

        $result = mysqli_query($this->link, $query);
        $carFuelResultArray = [];
        while($row = mysqli_fetch_assoc($result))
        {
            $carFuelResultArray[] = $row;
        }
        return $carFuelResultArray;
    }

    private function GetFirstDayOfQuarter($year, $quarter)
    {
        $month = 1 + ($quarter-1)*3;
        return '01.'.($month < 10 ? '0'.$month : $month).'.'.$year;
    }

    public function GetFuelByQuarterReportData($year, $quarter)
    {
        if (intval($year) == 0 || intval($quarter) == 0)
        {
            die('Некорректно задан квартал');
        }
        $year = intval($year);
        $quarter = intval($quarter);
        $day = $this->GetFirstDayOfQuarter($year, $quarter);
        $query = "SELECT IFNULL(CONCAT(c.number,' г/н ', cm.model), c.type) AS car,
              IFNULL(fc.fuel_consumption, 'н/а') AS fuel_consumption,
              IFNULL(ROUND(fsm.fuel_start_quartal, 3), 'н/а') AS fuel_start_quartal,
              IFNULL(fmql.first_month_of_quartal_limit + smql.second_month_of_quartal_limit
               + tmql.third_month_of_quartal_limit, 'н/а') AS quartal_limit,

              IFNULL(ffe.factical_fuel, 'н/а') AS factical_first_month_fuel,
              IFNULL(ffe.factical_mileages, 'н/а') AS factical_first_month_mileages,
              IFNULL(sfe.factical_fuel, 'н/а') AS factical_second_month_fuel,
              IFNULL(sfe.factical_mileages, 'н/а') AS factical_second_month_mileages,
              IFNULL(tfe.factical_fuel, 'н/а') AS factical_third_month_fuel,
              IFNULL(tfe.factical_mileages, 'н/а') AS factical_third_month_mileages,

              IFNULL(fe.factical_fuel, 'н/а') AS factical_quartal_fuel,
              IFNULL(fe.factical_mileages, 'н/а') AS factical_quartal_mileages,
              IFNULL(ROUND(fem.fuel_end_quartal, 3), 'н/а') AS fuel_end_quartal,
              IFNULL(ROUND(fmql.first_month_of_quartal_limit + smql.second_month_of_quartal_limit
               + tmql.third_month_of_quartal_limit - fe.factical_fuel, 3), 'н/а') AS deviation_fuel
            FROM cars c
              LEFT JOIN car_models cm ON c.id_model = cm.id_model
              LEFT JOIN (SELECT fc.id_car, fc.fuel_consumption
                FROM fuel_consumption fc INNER JOIN (
                SELECT fc.id_car, MAX(fc.start_date) AS max_start_date
                FROM fuel_consumption fc
                WHERE fc.start_date <= STR_TO_DATE('$day','%d.%m.%Y')
                GROUP BY fc.id_car) fcsd ON fc.id_car = fcsd.id_car AND fc.start_date = fcsd.max_start_date) fc ON c.id = fc.id_car
              LEFT JOIN (SELECT w.id_car, w.end_date,
              IF(w.end_date = STR_TO_DATE(CONCAT(year(STR_TO_DATE('$day','%d.%m.%Y')),'-',month(STR_TO_DATE('$day','%d.%m.%Y')), '-01'), '%Y-%m-%d'), w.fuel_before,
              w.fuel_after) AS fuel_start_quartal
            FROM waybills w INNER JOIN (SELECT w.id_car, MIN(w.max_end_date) AS max_end_date
            FROM (
            SELECT w.id_car, MAX(w.end_date) AS max_end_date
                FROM waybills w
                WHERE w.deleted <> 1 AND
                  w.end_date <= STR_TO_DATE(CONCAT(year(STR_TO_DATE('$day','%d.%m.%Y')),'-',
                      month(STR_TO_DATE('$day','%d.%m.%Y')), '-01'), '%Y-%m-%d')
                GROUP BY w.id_car
            UNION ALL
            SELECT w.id_car, MIN(w.end_date) AS max_end_date
                FROM waybills w
                WHERE w.deleted <> 1 AND
                  w.end_date >= STR_TO_DATE(CONCAT(year(STR_TO_DATE('$day','%d.%m.%Y')),'-',
                      month(STR_TO_DATE('$day','%d.%m.%Y')), '-01'), '%Y-%m-%d')
                GROUP BY w.id_car) w
            GROUP BY w.id_car) wed ON w.id_car = wed.id_car AND w.end_date = wed.max_end_date) fsm ON c.id = fsm.id_car
              LEFT JOIN (SELECT w.id_car, w.end_date,
              w.fuel_after AS fuel_end_quartal
              FROM waybills w INNER JOIN (
                  SELECT w.id_car, MAX(w.end_date) AS max_end_date
                  FROM waybills w
                  WHERE w.deleted <> 1 AND w.end_date < DATE_ADD(STR_TO_DATE('$day','%d.%m.%Y'), INTERVAL 3 MONTH)
                  GROUP BY w.id_car) wed ON w.id_car = wed.id_car AND w.end_date = wed.max_end_date) fem ON c.id = fem.id_car
              LEFT JOIN (SELECT fml.id_car, fml.`limit` AS first_month_of_quartal_limit
            FROM fuel_month_limit fml INNER JOIN (
            SELECT fml.id_car, MAX(fml.start_date) AS max_start_date
            FROM fuel_month_limit fml
            WHERE fml.start_date <=
              DATE_ADD(STR_TO_DATE(CONCAT('01.01.', YEAR(STR_TO_DATE('$day','%d.%m.%Y'))), '%d.%m.%Y'),
              INTERVAL (QUARTER(STR_TO_DATE('$day','%d.%m.%Y')) - 1)*3 MONTH)
            GROUP BY fml.id_car) fmlsd ON fml.id_car = fmlsd.id_car AND fml.start_date = fmlsd.max_start_date) fmql ON c.id = fmql.id_car
              LEFT JOIN (SELECT fml.id_car, fml.`limit` AS second_month_of_quartal_limit
            FROM fuel_month_limit fml INNER JOIN (
            SELECT fml.id_car, MAX(fml.start_date) AS max_start_date
            FROM fuel_month_limit fml
            WHERE fml.start_date <=
              DATE_ADD(STR_TO_DATE(CONCAT('01.02.', YEAR(STR_TO_DATE('$day','%d.%m.%Y'))), '%d.%m.%Y'),
              INTERVAL (QUARTER(STR_TO_DATE('$day','%d.%m.%Y')) - 1)*3 MONTH)
            GROUP BY fml.id_car) fmlsd ON fml.id_car = fmlsd.id_car AND fml.start_date = fmlsd.max_start_date) smql ON c.id = smql.id_car
              LEFT JOIN (SELECT fml.id_car, fml.`limit` AS third_month_of_quartal_limit
            FROM fuel_month_limit fml INNER JOIN (
            SELECT fml.id_car, MAX(fml.start_date) AS max_start_date
            FROM fuel_month_limit fml
            WHERE fml.start_date <=
              DATE_ADD(STR_TO_DATE(CONCAT('01.03.', YEAR(STR_TO_DATE('$day','%d.%m.%Y'))), '%d.%m.%Y'),
              INTERVAL (QUARTER(STR_TO_DATE('$day','%d.%m.%Y')) - 1)*3 MONTH)
            GROUP BY fml.id_car) fmlsd ON fml.id_car = fmlsd.id_car AND fml.start_date = fmlsd.max_start_date) tmql ON c.id = tmql.id_car
              LEFT JOIN (SELECT w.id_car, SUM(w.mileage_after - w.mileage_before) AS factical_mileages,
              ROUND(SUM(IFNULL(w.fuel_before, 0) + IFNULL(w.given_fuel, 0) - IFNULL(w.fuel_after, 0)), 3) AS factical_fuel
            FROM waybills w
            WHERE w.deleted <> 1 AND w.end_date BETWEEN
                STR_TO_DATE(CONCAT(year(STR_TO_DATE('$day','%d.%m.%Y')),'-',
                month(STR_TO_DATE('$day','%d.%m.%Y')), '-01'), '%Y-%m-%d')
              AND DATE_ADD(DATE_ADD(STR_TO_DATE('$day','%d.%m.%Y'), INTERVAL 3 MONTH), INTERVAL -1 MICROSECOND)
            GROUP BY w.id_car) fe ON c.id = fe.id_car
              LEFT JOIN (SELECT w.id_car, SUM(w.mileage_after - w.mileage_before) AS factical_mileages,
              ROUND(SUM(IFNULL(w.fuel_before, 0) + IFNULL(w.given_fuel, 0) - IFNULL(w.fuel_after, 0)), 3) AS factical_fuel
            FROM waybills w
            WHERE w.deleted <> 1 AND w.end_date BETWEEN
                STR_TO_DATE(CONCAT(year(STR_TO_DATE('$day','%d.%m.%Y')),'-',
                month(STR_TO_DATE('$day','%d.%m.%Y')), '-01'), '%Y-%m-%d')
              AND LAST_DAY(STR_TO_DATE('$day','%d.%m.%Y'))
            GROUP BY w.id_car) ffe ON c.id = ffe.id_car
             LEFT JOIN (SELECT w.id_car, SUM(w.mileage_after - w.mileage_before) AS factical_mileages,
              ROUND(SUM(IFNULL(w.fuel_before, 0) + IFNULL(w.given_fuel, 0) - IFNULL(w.fuel_after, 0)), 3) AS factical_fuel
            FROM waybills w
            WHERE w.deleted <> 1 AND w.end_date BETWEEN
                DATE_ADD(STR_TO_DATE(CONCAT(year(STR_TO_DATE('$day','%d.%m.%Y')),'-',
                month(STR_TO_DATE('$day','%d.%m.%Y')), '-01'), '%Y-%m-%d'), INTERVAL 1 MONTH)
              AND LAST_DAY(DATE_ADD(STR_TO_DATE('$day','%d.%m.%Y'), INTERVAL 1 MONTH))
            GROUP BY w.id_car) sfe ON c.id = sfe.id_car
               LEFT JOIN (SELECT w.id_car, SUM(w.mileage_after - w.mileage_before) AS factical_mileages,
              ROUND(SUM(IFNULL(w.fuel_before, 0) + IFNULL(w.given_fuel, 0) - IFNULL(w.fuel_after, 0)), 3) AS factical_fuel
            FROM waybills w
            WHERE w.deleted <> 1 AND w.end_date BETWEEN
                DATE_ADD(STR_TO_DATE(CONCAT(year(STR_TO_DATE('$day','%d.%m.%Y')),'-',
                month(STR_TO_DATE('$day','%d.%m.%Y')), '-01'), '%Y-%m-%d'), INTERVAL 2 MONTH)
              AND LAST_DAY(DATE_ADD(STR_TO_DATE('$day','%d.%m.%Y'), INTERVAL 2 MONTH))
            GROUP BY w.id_car) tfe ON c.id = tfe.id_car
            WHERE c.is_active = 1
            ORDER BY car;";

        $result = mysqli_query($this->link, $query);
        $carFuelResultArray = [];
        while($row = mysqli_fetch_assoc($result))
        {
            $carFuelResultArray[] = $row;
        }
        return $carFuelResultArray;
    }

}