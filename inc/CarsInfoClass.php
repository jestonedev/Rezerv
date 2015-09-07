<?php
/**
 * Created by JetBrains PhpStorm.
 * User: IgnVV
 * Date: 03.04.13
 * Time: 9:43
 * To change this template use File | Settings | File Templates.
 */
include_once "const.php";

class CarsInfoClass
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

    public function AddMileAge($id_car, $date, $mileage, $mileage_type, $car_chief)
    {
        $query = "SELECT * FROM mileages WHERE id_car = ".$id_car." AND `date` = str_to_date('".$date."','%d.%m.%Y') AND mileage_type = $mileage_type";
        $result = mysqli_query($this->link, $query);
        $car_chief = ($car_chief == null ? "NULL" : $car_chief);
        if (mysqli_errno($this->con)!=0)
        {
            $this->fatal_error('Ошибка при выполнении запроса к базе данных');
            echo 'Ошибка при выполнении запроса';
        }
        if (mysqli_num_rows($result) > 0)
        {
            $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
            $id = $row["id"];
            $query = "UPDATE mileages SET mileage = ".$mileage.", id_car_chief = $car_chief WHERE id = ".$id;
        } else
            $query = "INSERT INTO mileages (id_car, id_car_chief, mileage, `date`, mileage_type) VALUES (".$id_car.",".$car_chief.",".$mileage.", str_to_date('".$date."','%d.%m.%Y'),".$mileage_type.")";
        mysqli_free_result($result);
        $pq = mysqli_prepare($this->link, $query);
        mysqli_stmt_execute($pq);
        if (mysqli_errno($this->con)!=0)
        {
            $this->fatal_error('Ошибка при выполнении запроса к базе данных');
            echo 'Ошибка при выполнении запроса';
        }
        mysqli_stmt_close($pq);
    }


    private function LastDayOfMonth($month, $year)
    {
        switch ($month)
        {
            case 1: case 3: case 5: case 7:
            case 8: case 10: case 12:
                return 31;
                break;
            case 2:
                if (($year % 100 == 0) && ($year % 400 != 0))
                    return 28;
                else
                if ($year % 4 == 0)
                    return 29;
                else
                    return 28;
                break;
            default: return 30;
        }
    }

    ///////////////////////////////////////////////////////////////////////////
    //Интерфейс IQuery. Декларирует параметры для корректной работы DataTable//
    ///////////////////////////////////////////////////////////////////////////

    public function Columns() {
        return array('edit_lbl','car','limit_date','limit_mileage','fact_mileage', 'last_fact_mileage_date');
    }

    public function Table() {
        $now = new DateTime();
        $start_date = '01.'.$now->format('m').'.'.$now->format('Y');
        $end_date = $this->LastDayOfMonth(intval($now->format('m')), intval($now->format('Y'))).'.'.$now->format('m').'.'.$now->format('Y');

        return "(SELECT CONCAT('<img src=\'img/details_open.png\' value=\'',cars.id,'\' data-id-chief-default=\'',IFNULL(cars.id_chief_default, 0),'\'>') AS edit_lbl,
               IF(cars.model = '' AND cars.number = '', cars.type, CONCAT(cars.model,' г/н ',cars.number)) AS car
             , IFNULL(date(l.`date`), 'Не указано') AS limit_date
             , IFNULL(l.mileage, 'Без ограничений') AS limit_mileage
             , IFNULL(m.mileage, 0) AS fact_mileage
             , IFNULL(date(m.`date`), 'Не указано') AS last_fact_mileage_date
        FROM
          cars
        LEFT JOIN
        (SELECT id_car
              , sum(mileage) AS mileage, max(`date`) AS `date`
         FROM
           mileages
         WHERE
           ((mileage_type = 0) OR (mileage_type = 2))
           AND (`date` BETWEEN str_to_date('".$start_date."', '%d.%m.%Y') AND str_to_date('".$end_date."', '%d.%m.%Y'))
         GROUP BY
           id_car) m
        ON (cars.id = m.id_car)
        LEFT JOIN
        (SELECT mileages.*
         FROM
           mileages
         INNER JOIN
           (SELECT id_car
                 , max(`date`) AS `date`
            FROM
              mileages
            WHERE
              (mileage_type = 1)
              AND (`date` <= now())
            GROUP BY
              id_car) m
         ON (mileages.id_car = m.id_car AND mileages.`date` = m.`date`)) l
        ON (cars.id = l.id_car)
        WHERE
  (mileage_type = 1)
  OR (mileage_type IS NULL)
        ) t";
    }

    public function Where() {
        return "";
    }

    public function IndexColumn() {
        return "car";
    }

    public function DisplayColumnNames()
    {
        return '{"head":"<tr><th></th><th>Транспорт</th><th>Лимит установлен</th><th>Лимит пробега</th><th>Фактический пробег</th><th>Пробег изменен</th></tr>",
                 "foot":"<tr><th></th><th>Транспорт</th><th>Лимит установлен</th><th>Лимит пробега</th><th>Фактический пробег</th><th>Пробег изменен</th></tr>"}';
    }

    public function FilterColumnsData($column, $data)
    {
        if ( $column == "edit_lbl")
        {
            return $data;
        } else
            return Helper::ClearJsonString($data);;
    }
}
