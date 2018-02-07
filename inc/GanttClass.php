<?php
/**
 * Created by JetBrains PhpStorm.
 * User: IgnVV
 * Date: 30.09.13
 * Time: 16:57
 * To change this template use File | Settings | File Templates.
 */
class GanttClass
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

    public function GetRangeInfo($date_from, $date_to, $request_states, $request_type_id, $id_car)
    {
        $car_filter = "";
        if (!empty($id_car) && $id_car != 'Весь транспорт')
        {
            $car_filter = "AND id_car = $id_car";
        }
        $empty_car_filter = "";
        if ($request_type_id == 1)
        {
            $empty_car_filter = "AND id_car IS NOT NULL";
        }
        $query = "SELECT v.id_request_number, v.id_request, v.request_state,  v.request_state_text,
                      DATE_FORMAT(v.date_from, \"%Y\") AS date_from_year,
                      DATE_FORMAT(v.date_from, \"%m\") AS date_from_month,
                      DATE_FORMAT(v.date_from, \"%d\") AS date_from_day,
                      DATE_FORMAT(v.date_from, \"%H\") AS date_from_hour,
                      DATE_FORMAT(v.date_from, \"%i\") AS date_from_minute,
                      DATE_FORMAT(v.date_to, \"%Y\") AS date_to_year,
                      DATE_FORMAT(v.date_to, \"%m\") AS date_to_month,
                      DATE_FORMAT(v.date_to, \"%d\") AS date_to_day,
                      DATE_FORMAT(v.date_to, \"%H\") AS date_to_hour,
                      DATE_FORMAT(v.date_to, \"%i\") AS date_to_minute,
              v.id_car, v.number, v.model, v.type,
              IF(DATE_FORMAT(v.date_from, '%d.%m.%Y') = DATE_FORMAT(v.date_to, '%d.%m.%Y'),
                CONCAT(DATE_FORMAT(v.date_from, CONCAT(IF('$date_from' = '$date_to', '', '%d.%m.%Y '), '%H:%i')),'-',
                  DATE_FORMAT(v.date_to, '%H:%i')),
                CONCAT(DATE_FORMAT(v.date_from, CONCAT(IF('$date_from' = '$date_to', '', '%d.%m.%Y '), '%H:%i')),'-',
                  DATE_FORMAT(v.date_to, '%d.%m.%Y %H:%i'))
              ) AS date_label
            FROM (
            SELECT v.id_request_number, v.id_request, v.request_state,  v.request_state_text,
              v.date_from,
              DATE_ADD(DATE_ADD(v.date_from, INTERVAL v.hour_duration HOUR), INTERVAL v.minutes_duration MINUTE) AS date_to,
              v.id_car, v.number, v.model, v.type
            FROM (
            SELECT rn.id_request_number, rn.id_request,
              rn.request_date, rn.request_state, srs.request_status AS request_state_text,
              STR_TO_DATE(CONCAT(sdf.field_value, ' ', stf.field_value), '%d.%m.%Y %H:%i') AS date_from,
              SUBSTRING_INDEX(df.field_value, '.', 1) AS hour_duration,
              ROUND(SUBSTRING_INDEX(df.field_value, '.', -1)*60/100) AS minutes_duration, cftr.id_car, c.number, c.model, c.type
            FROM request_number rn
              INNER JOIN sp_request_status srs ON rn.request_state = srs.id_request_status
              INNER JOIN (
            SELECT rd.*
            FROM request_data rd
              INNER JOIN calendar_fields cf ON rd.id_field = cf.start_date_field
            WHERE cf.id_request = $request_type_id) sdf ON rn.id_request_number = sdf.id_request_number
              INNER JOIN (
            SELECT rd.*
            FROM request_data rd
              INNER JOIN calendar_fields cf ON rd.id_field = cf.start_time_field
            WHERE cf.id_request = $request_type_id) stf ON rn.id_request_number = stf.id_request_number
              INNER JOIN (
            SELECT rd.*
            FROM request_data rd
              INNER JOIN calendar_fields cf ON rd.id_field = cf.duration_field
            WHERE cf.id_request = $request_type_id) df ON rn.id_request_number = df.id_request_number
              LEFT JOIN cars_for_transport_requests cftr ON rn.id_request_number = cftr.id_request_number
              LEFT JOIN cars c ON cftr.id_car = c.id
              ) v
            WHERE v.date_from BETWEEN STR_TO_DATE('$date_from', '%d.%m.%Y') AND
              STR_TO_DATE('$date_to 23:59:59', '%d.%m.%Y %H:%i:%s') AND
              request_state IN ($request_states) $empty_car_filter $car_filter
            ORDER BY v.id_car, v.date_from) v";
        $result = mysqli_query($this->link, $query);
        $array = [];
        while($row = mysqli_fetch_assoc($result))
        {
            array_push($array, [
                "id_request_number" => $row["id_request_number"],
                "request_state" => $row["request_state"],
                "request_state_text" => $row["request_state_text"],
                "date_from_year" => $row["date_from_year"],
                "date_from_month" => $row["date_from_month"],
                "date_from_day" => $row["date_from_day"],
                "date_from_hour" => $row["date_from_hour"],
                "date_from_minute" => $row["date_from_minute"],
                "date_to_year" => $row["date_to_year"],
                "date_to_month" => $row["date_to_month"],
                "date_to_day" => $row["date_to_day"],
                "date_to_hour" => $row["date_to_hour"],
                "date_to_minute" => $row["date_to_minute"],
                "car_number" => $row["number"],
                "car_model" => $row["model"],
                "car_type" => $row["type"],
                "date_label" => $row["date_label"],
            ]);
        }
        return json_encode($array);
    }
}
