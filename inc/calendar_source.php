<?php

include_once "const.php";
include_once "auth.php";
include_once "ldap.php";

class CalendarSource
{
    private $link;
    private $dep_abbrs;

    private function fatal_error ( $sErrorMessage = '' )
    {
        header( $_SERVER['SERVER_PROTOCOL'] .' 500 Internal Server Error' );
        die( $sErrorMessage );
    }

    public function __construct()
    {
        $this->link = mysqli_connect(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_BASE) or $this->fatal_error('Ошибка подключения к базе данных');
        mysqli_query($this->link, "SET NAMES 'utf8'");
        $query = 'SELECT * FROM dep_abbrs';
        $result = mysqli_query($this->link, $query);
        if (!$result)
            fatal_error('Не удалось выполнить запрос к базе данных');
        $this->dep_abbrs = array();
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
        {
            $this->dep_abbrs[$row["department"]] = $row["abbr"];
        }
    }

    public function __destruct()
    {
        if ($this->link)
            mysqli_close($this->link);
    }

    //Получить список id_field параметров для заданного $id_request
    private function GetKeyFieldsIDs($id_request)
    {
        $query = 'SELECT * FROM calendar_fields WHERE id_request = '.$id_request;
        $result = mysqli_query($this->link, $query);
        if (!$result)
            fatal_error('Не удалось выполнить запрос к базе данных');
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        if ($row)
        {
            return Array("start_date_field" => $row["start_date_field"],
                "start_time_field" => $row["start_time_field"],
                "duration_field" => $row["duration_field"]);
        } else
            return Array();
    }

    //Получить из таблицы request_data значения для ключевых полей и id_request_number, имеющих отношение к id_request
    private function GetDataForEvents($id_request, $key_fields)
    {
        //итак, у меня есть номер департамента, есть номер транспорта, есть массив реквестстейтов

        //Генерируем шаблоны для запроса
        $department_template = '';
        $transport_template = '';
        $request_state_template = '';
        if (!isset($_POST['department']))
            $this->fatal_error('Не указано наименование департамента');
        if ($_POST['department'] != 'Все департаменты')
        {
            $organization = explode(":",$_POST['department']);
            $department = stripslashes($organization[0]);
            $stage = stripslashes($organization[1]);
            if (empty($stage))
                $department_template = " AND (department ='".addslashes($department)."')";
            else
                $department_template = " AND (department ='".addslashes($department)."') AND (stage ='".addslashes($stage)."')";
        }
        if ($id_request == 1)
        {
            if (!isset($_POST['transport']))
                $this->fatal_error('Не указано наименование транспорта');
            if ($_POST['transport'] != 'Весь транспорт')
                $transport_template = " AND (id_car ='".addslashes($_POST['transport'])."')";
        }
        $request_state_template = 'AND (request_state IN (';
        foreach($_POST['requestStates'] as $state)
            $request_state_template .= $state.',';
        $request_state_template = substr($request_state_template, 0, strlen($request_state_template) - 1);
        $request_state_template .= '))';
        //Оформляем запрос
        $query = 'SELECT *
        FROM request_data INNER JOIN
            request_number USING(id_request_number)
        WHERE id_request_number IN (
            SELECT id_request_number
            FROM request_number LEFT JOIN
                cars_for_transport_requests USING(id_request_number)
            WHERE (id_request = '.$id_request.') '.$request_state_template.' '.$transport_template.' %department_filter%
        ) AND id_field IN ('. $key_fields["start_date_field"].','.$key_fields["start_time_field"].','.$key_fields["duration_field"].')';
        $query = str_replace('%department_filter%',$department_template,$query);
        $result = mysqli_query($this->link, $query);
        if (mysqli_errno($this->link) != 0) $this->fatal_error("Не удалось выполнить запрос к базе данных");
        return $result;
    }

    //Сгруппировать полученные значения в массив
    private function ResultEventsToArray($result)
    {
        $array = Array();
        $ldap = new LDAP();
        $department = $ldap->GetLoginParam("COMPANY");
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
        {
            $array[$row["id_request_number"]][$row["id_field"]] = $row["field_value"];
            $array[$row["id_request_number"]]["request_state"] = $row["request_state"];
            $array[$row["id_request_number"]]["department"] = $row["department"];
            if (Auth::hasPrivilege(AUTH_ALL_DEPARTMENTS_READ_DATA))
                $array[$row["id_request_number"]]["can_see_dep_info"] = true;
            else
            {
                if ($row["department"] != $department)
                    $array[$row["id_request_number"]]["can_see_dep_info"] = false;
                else
                    $array[$row["id_request_number"]]["can_see_dep_info"] = true;
            }
        }
        return $array;
    }

    private  function getColor($request_state)
    {
        switch ($request_state)
        {
            case 1: return "#9a9f9a";
                break;
            case 2: return "#ffa71a";
                break;
            case 3: return "#00a9e2";
                break;
            case 5: return "#42a506";
                break;
            default: return "red";
                break;
        }
    }

    //Извлечь аббривиатуру департамента из БД
    private function getDepartmentAbbr($department)
    {
        return $this->dep_abbrs[$department];
    }

    //Собрать структуру xml по полученным данным из массива
    private function ArrayToXml($array, $key_fields)
    {
        $xml = "<events>";
        $cal_start_date = $_POST['start'];
        $cal_end_date = $_POST['end'];
        $dt_cal_start_date = new DateTime($cal_start_date);
        $dt_cal_end_date = new DateTime($cal_end_date);

        foreach ($array as $key => $value)
        {
            $start_date = $this->ConvertDate($value[$key_fields["start_date_field"]], $value[$key_fields["start_time_field"]]);
            $end_date = $this->AddHours($start_date, $value[$key_fields["duration_field"]]);
            $dt_start_date = new DateTime($start_date);
            $dt_end_date = new DateTime($end_date);
            if (($dt_end_date < $dt_cal_start_date) || ($dt_start_date > $dt_cal_end_date))
                continue;
            if ($value["can_see_dep_info"] == false)
                $title = ' Занято';
            else
                $title = ' №'.$key.', '.$this->getDepartmentAbbr($value["department"]);
            $xml.='<event color="'.$this->getColor($value["request_state"]).'" title="'.$title.'" start="'.$start_date.'" end="'.$end_date.'" allDay="false"></event>';
        }
        $xml.= "</events>";
        return $xml;
    }

    //Функция парсинга даты и преобразования в нужный вид
    private function ConvertDate($russian_date, $russian_time)
    {
        $exploded_date = explode('.', $russian_date);
        return $exploded_date[2].'-'.$exploded_date[1].'-'.$exploded_date[0].' '.$russian_time;
    }

    //Функция прибавления времени
    private function AddHours($date, $hours)
    {

        $end_date = new DateTime($date);
        $minutes = round($hours*60);
        $end_date->modify('+'.$minutes.' minute');
        return $end_date->format('Y-m-d H:i');
    }

    public function GetSourceXml($id_request)
    {
        $key_fields = $this->GetKeyFieldsIDs($id_request);
        if (empty($key_fields))
            return "<events></events>";
        $result = $this->GetDataForEvents($id_request, $key_fields);
        $array = $this->ResultEventsToArray($result);
        return $this->ArrayToXml($array, $key_fields);
    }
}

if ($_SERVER["REQUEST_METHOD"]==="POST") {
    $id_request = 1;
    if (isset($_COOKIE['id_request']))
        $id_request = addslashes($_COOKIE['id_request']);
    else
        die('Не установлен идентификатор группы заявок');
    $cal_source = new CalendarSource();
    echo $cal_source->GetSourceXml($id_request);
}