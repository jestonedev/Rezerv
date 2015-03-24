<?php
/**
 * Created by JetBrains PhpStorm.
 * User: IgnVV
 * Date: 18.01.13
 * Time: 11:28
 * To change this template use File | Settings | File Templates.
 */

include_once "const.php";
include_once "ldap.php";
include_once "auth.php";

class ReportClass
{
    var $link;
    var $sTable = "";
    var $aColumns = array();
    var $sIndexColumn = "";
    var $report_id;
    var $users_buffer;
    var $rep_with_car_id = array(15, 35, 36, 37, 38, 40);           //Отчеты с идентификатором автомобиля
    var $rep_with_fuel_type_id = array(35, 36);                     //Отчеты с идентификатором типа горючего
    var $rep_witout_dep_and_date_type = array(17,35,36,37,38, 40);  //идентификаторы отчетов без списка выбора департамента и типа даты
    //Отчеты, в которых присутствует логин пользователя (оптимизация числа запросов к AD на резолвинг имен пользователей)
    var $rep_with_user_info = array(2,8,9,10,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,42);

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
        if (!isset($_POST['report_id']))
            $this->fatal_error("Вы пытаетесь сгенерировать отчет, но на сервер не был передан идентификатор отчета");
        $this->report_id = $_POST['report_id'];
        if (in_array($this->report_id, $this->rep_with_user_info))
        {
            $ldap = new LDAP();
            $this->users_buffer = $ldap->GetAllUsers();
        }
    }

    public function __destruct()
    {
        if ($this->link)
            mysqli_close($this->link);
    }

    public function GetReportNames()
    {
        $id_requests = '';
        $ids = '0';
        if (Auth::hasPrivilege(AUTH_READ_TRANSPORT_REQUEST) &&
            Auth::hasPrivilege(AUTH_READ_GREAT_HALL_REQUEST) &&
            Auth::hasPrivilege(AUTH_READ_SMALL_HALL_REQUEST))
        {
            $id_requests = '0';
        }
        if (Auth::hasPrivilege(AUTH_READ_TRANSPORT_REQUEST))
        {
            if (strlen($id_requests) != 0)
                $id_requests.=',';
            $id_requests.='1';
        }
        if (Auth::hasPrivilege(AUTH_READ_GREAT_HALL_REQUEST))
        {
            if (strlen($id_requests) != 0)
                $id_requests.=',';
            $id_requests.='2';
        }
        if (Auth::hasPrivilege(AUTH_READ_SMALL_HALL_REQUEST))
        {
            if (strlen($id_requests) != 0)
                $id_requests.=',';
            $id_requests.='3';
        }
        if (!Auth::hasPrivilege(AUTH_READ_TRANSPORT_MILEAGE))
        {
            $ids .= ', 17';
        }
        if (!Auth::hasPrivilege(AUTH_READ_WAYBILLS))
        {
            $ids .= ', 35, 36';
        }
        if (!Auth::hasPrivilege(AUTH_READ_REPAIR_ACTS))
        {
            $ids .= ', 37, 38';
        }
        $query = 'SELECT id, name, id_request FROM reports_info WHERE id_request IN ('.$id_requests.') AND id NOT IN ('.$ids.') ORDER BY id_request, id';
        $result = mysqli_query($this->link, $query) or
            $this->fatal_error("Ошибка выполнения запроса к базе данных");
        $html = "";
        $is_first_row = true;
        $id_request = -1;
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
        {
            $new_id_request = $row['id_request'];
            if ($id_request != $new_id_request)
            {
                $id_request = $new_id_request;
                if ($id_request != 0)
                    $html .= "</optgroup>";
                switch($id_request)
                {
                    case 0: $html .= '<optgroup class="report_group" label="Общие отчеты">';
                        break;
                    case 1: $html .= '<optgroup class="report_group" label="Отчеты по транспорту">';
                        break;
                    case 2: $html .= '<optgroup class="report_group" label="Отчеты по конференц-залу">';
                        break;
                    case 3: $html .= '<optgroup class="report_group" label="Отчеты по залу заседания думы">';
                        break;
                }
            }
            $html .= '<option class="report" value="'.$row['id'].'" '.($is_first_row?'selected="true"':'').'>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp'.$row['name'].'</option>';
            $is_first_row = false;
        }
        $html .= "</optgroup>";
        return $html;
    }

    ///////////////////////////////////////////////////////////////////////////
    //Интерфейс IQuery. Декларирует параметры для корректной работы DataTable//
    ///////////////////////////////////////////////////////////////////////////

    public function Columns() {
        if (!empty($this->aColumns))
            return $this->aColumns;
        $query = 'SELECT * FROM '.$this->Table().' LIMIT 1';
        $result = mysqli_query($this->link, $query);
        $fields = mysqli_fetch_array($result, MYSQLI_ASSOC);
        $array = array();
        foreach ($result->fetch_fields() as $v)
            array_push($array, $v->name);
        $this->aColumns = $array;
        return $array;
    }

    public function Table() {
        if (!empty($this->sTable))
           return $this->sTable;
        $query = 'SELECT query FROM reports_info WHERE id = '.addslashes($this->report_id);
        $result = mysqli_query($this->link, $query) or
            $this->fatal_error("Ошибка выполнения запроса к базе данных");
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        //Используем подзапрос, хранящийся в таблице, как виртуальную таблицу (вложенный запрос)
        $this->sTable = '('.$row['query'].') t';
        //Установка периода выборки
        if (!isset($_POST['start_date']) || !isset($_POST['end_date']))
            $this->fatal_error("Не задан обязательный параметр");
        $this->sTable = str_replace('%start_date%',$_POST['start_date'], $this->sTable);
        $this->sTable = str_replace('%end_date%',$_POST['end_date'], $this->sTable);
        //Если в отчете есть тип даты
        if (!in_array($this->report_id, $this->rep_witout_dep_and_date_type))
        {
            if ($_POST['date_id'] == 1)
                $date_id = 'event_date';
            else
                $date_id = 'request_date';
            $this->sTable = str_replace('%date_id%',$date_id, $this->sTable);
        }
        //Если в отчете есть идентификатор автомобиля
        if (in_array($this->report_id, $this->rep_with_car_id))
        {
            if ($_POST['car_id'] == 'Весь транспорт')
                $this->sTable = str_replace('%car_id%','', $this->sTable);
            else
                $this->sTable = str_replace('%car_id%','AND (id_car = '.$_POST['car_id'].')', $this->sTable);
        }
        //Если в отчете есть идентификатор марки горючего
        if (in_array($this->report_id, $this->rep_with_fuel_type_id))
        {
            if ($_POST['fuel_type_id'] == 'Все марки горючего')
                $this->sTable = str_replace('%fuel_type_id%','', $this->sTable);
            else
                $this->sTable = str_replace('%fuel_type_id%','AND (id_fuel_type = '.$_POST['fuel_type_id'].')', $this->sTable);
        }
        //Если в отчете есть department_filter
        //Строим department_filter в соответствии с правами пользователя и переданными значениями
        if (!in_array($this->report_id, $this->rep_witout_dep_and_date_type))
        {
            $ldap = new LDAP();
            $user_department = $ldap->GetLoginParam("COMPANY");
            if (!isset($_POST['department']))
                $this->fatal_error("Переданы некорректные параметры в Cookie");
            $cook_department = stripslashes($_POST['department']);
            $organization = explode(":",$cook_department);
            $department = stripslashes($organization[0]);
            $stage = stripslashes($organization[1]);

            if (Auth::hasPrivilege(AUTH_ALL_DEPARTMENTS_READ_DATA) && $department == 'Все департаменты')
                $this->sTable = str_replace('%department_filter%', "", $this->sTable);
            else
            if (Auth::hasPrivilege(AUTH_ALL_DEPARTMENTS_READ_DATA))
            {
                if (empty($stage))
                    $department_template = " AND (department ='".addslashes($department)."')";
                else
                    $department_template = " AND (department ='".addslashes($department)."') AND (stage ='".addslashes($stage)."')";
                $this->sTable = str_replace('%department_filter%', $department_template, $this->sTable);
            } else
            if ((!Auth::hasPrivilege(AUTH_ALL_DEPARTMENTS_READ_DATA)) && ($department == $user_department))
            {
                if (empty($stage))
                    $department_template = " AND (department ='".addslashes($department)."')";
                else
                    $department_template = " AND (department ='".addslashes($department)."') AND (stage ='".addslashes($stage)."')";
                $this->sTable = str_replace('%department_filter%', $department_template, $this->sTable);
            } else
                $this->fatal_error("Ошибка параметров отчета или привилегий пользователя");
        }
        return $this->sTable;
    }

    public function Where() {
        return "";
    }

    public function IndexColumn() {
        if (!empty($this->sIndexColumn))
            return $this->sIndexColumn;
        $query = 'SELECT index_column FROM reports_info WHERE id = '.addslashes($this->report_id);
        $result = mysqli_query($this->link, $query) or
            $this->fatal_error("Ошибка выполнения запроса к базе данных");
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        //Используем подзапрос, хранящийся в таблице, как виртуальную таблицу (вложенный запрос)
        $this->sIndexColumn = $row['index_column'];
        return $this->sIndexColumn;
    }

    //Вычисляем число строк заголовка
    private function RowCount($columns)
    {
        foreach ($columns as $column => $value)
        {
            if (is_array($value))
            {
                return 1 + $this->RowCount($value);
            }
        }
        return 1;
    }

    //Заполняем колонки в шапке таблицы {col_num, row_num}
    // {1,1}, {2,1},{3,1},{4,1},           {7,1}, {8,1}     {10,2}
    //                    {4,2},{5,2},{6,2}       {8,2}
    //                                            {8,3}{9,3}
    private function FillHeader($columns, $head_template, $row_num = 1, $column_num = 1, &$rowconf_array = Array())
    {
        $template_current_row = "";
        foreach ($columns as $column => $value)
        {
            $index = sizeof($rowconf_array);
            if (is_array($value))
            {
                $template_current_row .= "<th rowspan='%rowspan".$index."%' colspan='".sizeof($value)."'>".$column."</th>";
                $rowconf_array[$index] = Array($column_num, $row_num);
                $head_template = $this->FillHeader($value, $head_template, $row_num+1, $column_num, $rowconf_array);
            } else
            {
                $template_current_row .= "<th rowspan='%rowspan".$index."%'>".$column."</th>";
                $rowconf_array[$index] = Array($column_num, $row_num);
            }
            foreach ($rowconf_array as $rowconf)
            {
                if ($rowconf[0] >= $column_num)
                    $column_num = $rowconf[0]+1;
            }
        }
        $head_template = str_replace("%row".$row_num."%", $template_current_row."%row".$row_num."%", $head_template);
        if ($row_num == 1)
        {
            $row_count = 1; //Количество рядов в заголовке
            for ($i = 0; $i < sizeof($rowconf_array); $i++)
                if($row_count < $rowconf_array[$i][1])
                    $row_count = $rowconf_array[$i][1];
            for ($i = 0; $i < sizeof($rowconf_array); $i++)
            {
                //Получаем
                $column_num = $rowconf_array[$i][0];
                $row_num = $rowconf_array[$i][1];
                $column_count = 1;      //Число совпадающих колонок
                for ($j = 0; $j < sizeof($rowconf_array); $j++)
                {
                    if (($rowconf_array[$j][0] == $column_num) && ($row_num < $rowconf_array[$j][1]))
                        $column_count++;
                }
                if ($column_count == 1)
                    $head_template = str_replace("%rowspan".$i."%", $row_count - ($row_num-1), $head_template);
                else
                    $head_template = str_replace("%rowspan".$i."%", 1, $head_template);
            }
            for ($i = 0; $i < $row_count; $i++)
                $head_template = str_replace("%row".($i+1)."%", "", $head_template);
        }
        return $head_template;
    }

    private function FillFooter($columns)
    {
        $footer = "";
        foreach($columns as $column => $value)
        {
            if (is_array($value))
            {
                $footer .= $this->FillFooter($value);
            } else
                $footer .= "<th></th>";
        }
        return $footer;
    }

    public function DisplayColumnNames()
    {
        $query = 'SELECT columns_names FROM reports_info WHERE id = '.addslashes($this->report_id);
        $result = mysqli_query($this->link, $query) or
            $this->fatal_error("Ошибка выполнения запроса к базе данных");
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        $columns = json_decode($row['columns_names'], true);
        $rowcount = $this->RowCount($columns);
        $result_template = '{"head":"%head%","foot":"%foot%"}';
        $head_template = "";
        //Задаем шаблон строк
        for ($i = 0; $i < $rowcount; $i++)
        {
            $head_template .= "<tr>%row".($i+1)."%</tr>";
        }
        //Заполняем шаблон строк
        $head_template = $this->FillHeader($columns, $head_template);
        $result_template = str_replace("%head%",$head_template, $result_template);
        $foot_template = "<tr>".($this->FillFooter($columns))."</tr>";
        $result_template = str_replace("%foot%",$foot_template, $result_template);
        return $result_template;
    }

    public function FilterColumnsData($column, $data)
    {
        $sOutput = "";
        if ( $column == "request_status")
        {
            switch ($data)
            {
                case "Принята к исполнению":
                    $sOutput .= '<span class=\'req_accepted_status\'>'.Helper::ClearJsonString($data).'</span>';
                    break;
                case "Выполнена":
                    $sOutput .= '<span class=\'req_complete_status\'>'.Helper::ClearJsonString($data).'</span>';
                    break;
                case "Отказано диспетчером":case "Не выполнена":
                $sOutput .= '<span class=\'req_canceled_status\'>'.Helper::ClearJsonString($data).'</span>';
                break;
                case "Отменена пользователем":
                    $sOutput .= '<span class=\'req_canceled_by_user\'>'.Helper::ClearJsonString($data).'</span>';
                    break;
                default:
                    $sOutput .= Helper::ClearJsonString($data);
                    break;
            }
        } else
        if ( $column == "user")
        {
            $login = $data;
            $login = explode(" ", $login, 2);
            if (sizeof($login) > 1)
                $postfix = $login[1];
            $login = explode("\\", $login[0]);
            $FIO = $this->users_buffer[strtoupper($login[1])];
            $sOutput .= Helper::ClearJsonString($FIO);
        } else
        if ( $column == "department" )
        {
            $sOutput .= preg_replace('/не указан/', '<span class=\'req_without_stage\'>не указан</span>', Helper::ClearJsonString($data));
        } else
        if (($column == "different_mileage" ) && (intval($data) < 0))
        {
            $sOutput .= '<span class=\'expire_mileage\'>'.$data.'</span>';
        } else
        if (( $column == "different_req" ) && (intval($data) < 0))
        {
            $sOutput .= '<span class=\'expire_req_count\'>'.$data.'</span>';
        }
        else
        if ( $column == "edit_lbl")
        {
            $sOutput .= $data;
        }
        else
            $sOutput .= Helper::ClearJsonString($data);
        return $sOutput;
    }
}
