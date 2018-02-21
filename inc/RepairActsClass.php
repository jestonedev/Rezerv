<?php
/**
 * Created by JetBrains PhpStorm.
 * User: IgnVV
 * Date: 30.09.13
 * Time: 16:57
 * To change this template use File | Settings | File Templates.
 */
class RepairActsClass
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

    //Изменить акт выполненных работ
    public function UpdateAct($args)
    {
        if (!Auth::hasPrivilege(AUTH_MANAGE_TRANSPORT))
            $this->fatal_error('У вас нет прав на изменение акта выполненных работ');
        $query = "UPDATE cars_repair_acts SET id_car = ?, id_performer = ?, id_driver = ?, id_respondent = ?, repair_act_number = ?, act_date = ?, reason_for_repairs = ?, work_performed = ?,
          odometer = ?, wait_start_date = ?, wait_end_date = ?, repair_start_date = ?, repair_end_date = ?, self_repair = ? WHERE id_repair = ?";
        $pq = mysqli_prepare($this->link, $query);
        $car_id = $args['car_id'];
        $act_number = (trim($args['act_number']) == "") ? null : trim($args['act_number']);
        $performer_id = $args['mechanic_id'];
        $driver_id = $args['driver_id'];
        $respondent_id = $args['responded_id'];
        $reason_for_repairs = $args['reason_for_repair'];
        $work_performed = $args['work_performed'];
        $odometer = (trim($args['act_odometer']) == "") ? null: trim($args['act_odometer']);
        if (trim($args['act_date']) == "")
            $act_date = null;
        else
        {
            $act_date_parts = explode('.', $args['act_date']);
            $act_date = $act_date_parts[2].'-'.$act_date_parts[1].'-'.$act_date_parts[0];
        }
        if (trim($args['act_wait_start_date']) == "")
            $act_wait_start_date = null;
        else
        {
            $act_wait_start_datetime_parts = explode(' ', $args['act_wait_start_date']);
            $act_wait_start_date_parts = explode('.', $act_wait_start_datetime_parts[0]);
            $act_wait_start_date = $act_wait_start_date_parts[2].'-'.$act_wait_start_date_parts[1].'-'.$act_wait_start_date_parts[0].' '.$act_wait_start_datetime_parts[1];
        }
        if (trim($args['act_wait_end_date']) == "")
            $act_wait_end_date = null;
        else
        {
            $act_wait_end_datetime_parts = explode(' ', $args['act_wait_end_date']);
            $act_wait_end_date_parts = explode('.', $act_wait_end_datetime_parts[0]);
            $act_wait_end_date = $act_wait_end_date_parts[2].'-'.$act_wait_end_date_parts[1].'-'.$act_wait_end_date_parts[0].' '.$act_wait_end_datetime_parts[1];
        }
        if (trim($args['act_repair_start_date']) == "")
            $act_repair_start_date = null;
        else
        {
            $act_repair_start_datetime_parts = explode(' ', $args['act_repair_start_date']);
            $act_repair_start_date_parts = explode('.', $act_repair_start_datetime_parts[0]);
            $act_repair_start_date = $act_repair_start_date_parts[2].'-'.$act_repair_start_date_parts[1].'-'.$act_repair_start_date_parts[0].' '.$act_repair_start_datetime_parts[1];
        }
        if (trim($args['act_repair_end_date']) == "")
            $act_repair_end_date = null;
        else
        {
            $act_repair_end_datetime_parts = explode(' ', $args['act_repair_end_date']);
            $act_repair_end_date_parts = explode('.', $act_repair_end_datetime_parts[0]);
            $act_repair_end_date = $act_repair_end_date_parts[2].'-'.$act_repair_end_date_parts[1].'-'.$act_repair_end_date_parts[0].' '.$act_repair_end_datetime_parts[1];
        }
        $self_repair = $args['self_repair'] === 'true' ? 1 : 0;
        $repair_id = $args['repair_id'];
        mysqli_stmt_bind_param($pq,'iiiiisssissssii',$car_id, $performer_id, $driver_id, $respondent_id, $act_number, $act_date,
            $reason_for_repairs, $work_performed, $odometer, $act_wait_start_date, $act_wait_end_date,
            $act_repair_start_date, $act_repair_end_date, $self_repair, $repair_id);
        mysqli_stmt_execute($pq);
        if (mysqli_errno($this->link)!=0)
        {
            mysqli_rollback($this->link);
            $this->fatal_error('Ошибка выполнения запроса к базе данных');
        }

        $query = "delete from expended where id_repair = ?";
        $pq = mysqli_prepare($this->link, $query);
        mysqli_stmt_bind_param($pq, 'i', $repair_id);
        mysqli_stmt_execute($pq);
        if (mysqli_errno($this->link)!=0)
        {
            mysqli_rollback($this->link);
            $this->fatal_error('Ошибка выполнения запроса к базе данных');
        }
        $expended_strs = explode('$', $args['expended_list']);
        foreach ($expended_strs as $expended_str)
        {
            if (empty($expended_str))
                continue;
            $args = explode('@',$expended_str);
            if (count($args) != 3)
            {
                die('Некорректный формат параметра "Расходные материалы"');
            }
            $query = "insert into expended (id_repair, material, `count`, description) values (?,?,?,?)";
            $pq = mysqli_prepare($this->link, $query);
            mysqli_stmt_bind_param($pq, 'isds', $repair_id, trim($args[0]), trim($args[1]), trim($args[2]));
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

    //Добавление акта выполненных работ в базу данных
    public function InsertAct($args)
    {
        if (!Auth::hasPrivilege(AUTH_MANAGE_TRANSPORT))
            $this->fatal_error('У вас нет прав на создание акта выполненных работ');
        $query = "INSERT INTO cars_repair_acts(id_car, id_performer, id_driver, id_respondent, repair_act_number, act_date,
        reason_for_repairs, work_performed, odometer, wait_start_date, wait_end_date, repair_start_date, repair_end_date, self_repair)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $pq = mysqli_prepare($this->link, $query);
        $car_id = $args['car_id'];
        $act_number = (trim($args['act_number']) == "") ? null : trim($args['act_number']);
        $performer_id = $args['mechanic_id'];
        $driver_id = $args['driver_id'];
        $respondent_id = $args['responded_id'];
        $reason_for_repairs = $args['reason_for_repair'];
        $work_performed = $args['work_performed'];
        $odometer = (trim($args['act_odometer']) == "") ? null: trim($args['act_odometer']);
        if (trim($args['act_date']) == "")
            $act_date = null;
        else
        {
            $act_date_parts = explode('.', $args['act_date']);
            $act_date = $act_date_parts[2].'-'.$act_date_parts[1].'-'.$act_date_parts[0];
        }
        if (trim($args['act_wait_start_date']) == "")
            $act_wait_start_date = null;
        else
        {
            $act_wait_start_datetime_parts = explode(' ', $args['act_wait_start_date']);
            $act_wait_start_date_parts = explode('.', $act_wait_start_datetime_parts[0]);
            $act_wait_start_date = $act_wait_start_date_parts[2].'-'.$act_wait_start_date_parts[1].'-'.$act_wait_start_date_parts[0].' '.$act_wait_start_datetime_parts[1];
        }
        if (trim($args['act_wait_end_date']) == "")
            $act_wait_end_date = null;
        else
        {
            $act_wait_end_datetime_parts = explode(' ', $args['act_wait_end_date']);
            $act_wait_end_date_parts = explode('.', $act_wait_end_datetime_parts[0]);
            $act_wait_end_date = $act_wait_end_date_parts[2].'-'.$act_wait_end_date_parts[1].'-'.$act_wait_end_date_parts[0].' '.$act_wait_end_datetime_parts[1];
        }
        if (trim($args['act_repair_start_date']) == "")
            $act_repair_start_date = null;
        else
        {
            $act_repair_start_datetime_parts = explode(' ', $args['act_repair_start_date']);
            $act_repair_start_date_parts = explode('.', $act_repair_start_datetime_parts[0]);
            $act_repair_start_date = $act_repair_start_date_parts[2].'-'.$act_repair_start_date_parts[1].'-'.$act_repair_start_date_parts[0].' '.$act_repair_start_datetime_parts[1];
        }
        if (trim($args['act_repair_end_date']) == "")
            $act_repair_end_date = null;
        else
        {
            $act_repair_end_datetime_parts = explode(' ', $args['act_repair_end_date']);
            $act_repair_end_date_parts = explode('.', $act_repair_end_datetime_parts[0]);
            $act_repair_end_date = $act_repair_end_date_parts[2].'-'.$act_repair_end_date_parts[1].'-'.$act_repair_end_date_parts[0].' '.$act_repair_end_datetime_parts[1];
        }
        $self_repair = $args['self_repair'] === 'true' ? 1 : 0;
        mysqli_stmt_bind_param($pq,'iiiiisssissssi',$car_id, $performer_id, $driver_id, $respondent_id, $act_number, $act_date,
            $reason_for_repairs, $work_performed, $odometer, $act_wait_start_date, $act_wait_end_date,
            $act_repair_start_date, $act_repair_end_date, $self_repair);
        mysqli_stmt_execute($pq);
        if (mysqli_errno($this->link)!=0)
        {
            mysqli_rollback($this->link);
            $this->fatal_error('Ошибка выполнения запроса к базе данных');
        }
        $repair_id = mysqli_insert_id($this->link);
        $expended_strs = explode('$', $args['expended_list']);
        foreach ($expended_strs as $expended_str)
        {
            if (empty($expended_str))
                continue;
            $args = explode('@',$expended_str);
            if (count($args) != 3)
            {
                $this->fatal_error('Некорректный формат параметра "Расходные материалы"');
            }
            $query = "insert into expended (id_repair, material, `count`, description) values (?,?,?,?)";
            $pq = mysqli_prepare($this->link, $query);
            mysqli_stmt_bind_param($pq, 'isds', $repair_id, trim($args[0]), trim($args[1]), trim($args[2]));
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

    //Пометка акта выполненных работ, как удаленного
    function DeleteAct($id_repair)
    {
        if (!Auth::hasPrivilege(AUTH_MANAGE_TRANSPORT))
            $this->fatal_error('У вас нет прав на удаление акта выполненных работ');
        $query = "UPDATE cars_repair_acts SET deleted = 1 WHERE id_repair = ?";
        $pq = mysqli_prepare($this->link, $query);
        mysqli_stmt_bind_param($pq,'i', $id_repair);
        mysqli_stmt_execute($pq);
        if (mysqli_errno($this->link)!=0)
        {
            mysqli_rollback($this->link);
            $this->fatal_error('Ошибка выполнения запроса к базе данных');
        }
        mysqli_stmt_close($pq);
        mysqli_commit($this->link);
    }

    public function AutoCompleteDetails($idCar)
    {
        $queryWaybill = "SELECT w.mileage_after
            FROM waybills w
            WHERE w.id_car = $idCar AND w.deleted <> 1
            ORDER BY w.start_date DESC, id_waybill DESC
            LIMIT 1";
        $queryCarInfo = "SELECT c.id_driver_default
            FROM cars c
            WHERE c.id = $idCar";
        $repairActNumberQuery = "SELECT IFNULL(MAX(cra.repair_act_number), 0) + 1 AS repair_act_number FROM cars_repair_acts cra";
        $resultWaybill = mysqli_query($this->link, $queryWaybill);
        $resultWaybill = mysqli_fetch_assoc($resultWaybill);
        $resultCarInfo = mysqli_query($this->link, $queryCarInfo);
        $resultCarInfo = mysqli_fetch_assoc($resultCarInfo);
        $repairActNumberInfo = mysqli_query($this->link, $repairActNumberQuery);
        $repairActNumberInfo = mysqli_fetch_assoc($repairActNumberInfo);
        $waybillInfo = ["mileage_after" => 0];
        $carInfo = ["id_driver_default" => 1];
        if ($resultWaybill != null)
        {
            $waybillInfo = ["mileage_after" => $resultWaybill["mileage_after"]];
        }
        if ($resultCarInfo != null)
        {
            $carInfo = ["id_driver_default" => $resultCarInfo["id_driver_default"]];
        }
        return array_merge($waybillInfo, $carInfo,
            ["repair_act_number" => $repairActNumberInfo["repair_act_number"],
             "id_mechanic" => 2,
             "id_respondent" => 2]);
    }

    public function GetRepairActInfo($idRepair)
    {
        $query = "SELECT
              cra.id_repair,
              c.id AS id_car,
              c.number,
              cra.repair_act_number,
              DATE_FORMAT(cra.act_date,'%d.%m.%Y') AS act_date,
              DATE_FORMAT(cra.repair_start_date, '%d.%m.%Y') AS repair_start_date,
              DATE_FORMAT(cra.repair_start_date, '%H:%i') AS repair_start_time,
              DATE_FORMAT(cra.repair_end_date, '%d.%m.%Y') AS repair_end_date,
              DATE_FORMAT(cra.repair_end_date, '%H:%i') AS repair_end_time,
              DATE_FORMAT(cra.wait_start_date, '%d.%m.%Y') AS wait_start_date,
              DATE_FORMAT(cra.wait_start_date, '%H:%i') AS wait_start_time,
              DATE_FORMAT(cra.wait_end_date, '%d.%m.%Y') AS wait_end_date,
              DATE_FORMAT(cra.wait_end_date, '%H:%i') AS wait_end_time,
              cra.odometer,
              cra.work_performed,
              cra.reason_for_repairs,
              cra.id_driver,
              cra.id_performer AS id_mechanic,
              cra.id_respondent
            FROM cars_repair_acts cra
              INNER JOIN cars c ON cra.id_car = c.id
            WHERE cra.id_repair = ".addslashes($idRepair);
        $queryMaterials = "SELECT e.material AS material_name, e.count AS material_count,
                  e.description AS material_description
                FROM expended e
                WHERE e.id_repair = ".addslashes($idRepair);
        $result = mysqli_query($this->link, $query);
        $resultMaterials = mysqli_query($this->link, $queryMaterials);
        if (!$result || !$resultMaterials)
            $this->fatal_error("Ошибка выполнения запроса");
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        foreach($row as $key => $value)
        {
            $row[$key] = stripslashes($value);
        }
        $array = array();
        while ($row_material = mysqli_fetch_array($resultMaterials, MYSQLI_ASSOC))
        {
            $row_material["material_name"] = stripslashes($row_material["material_name"]);
            $row_material["material_count"] = stripslashes($row_material["material_count"]);
            $row_material["material_description"] = stripslashes($row_material["material_description"]);
            array_push($array, $row_material);
        }
        $row["materials"] = $array;
        return $row;
    }

    ///////////////////////////////////////////////////////////////////////////
    //Интерфейс IQuery. Декларирует параметры для корректной работы DataTable//
    ///////////////////////////////////////////////////////////////////////////

    public function Columns() {
        return array('edit_lbl','repair_act_number','car','respondent','act_date','status');
    }

    public function Table() {
        return "(SELECT CONCAT('<img src=\'img/details_open.png\' value=\'',id_repair,'\'>') AS edit_lbl, cra.repair_act_number,
            IFNULL(CONCAT(cm.model,' г/н ',c.number), c.type) AS car,  r.name AS respondent,
            DATE(cra.act_date) AS act_date, IF(cra.deleted = 0, 'Действительный', 'Удаленный') AS `status`
          FROM cars_repair_acts cra
            LEFT JOIN respondents r ON (cra.id_respondent = r.id_respondent)
            LEFT JOIN cars c ON (cra.id_car = c.id)
            LEFT JOIN car_models cm ON c.id_model = cm.id_model) t";
    }

    public function Where() {
        return "";
    }

    public function IndexColumn() {
        return "repair_act_number";
    }

    public function DisplayColumnNames()
    {
        return '{"head":"<tr><th></th><th>Номер акта</th><th>Транспорт</th><th>Ответственный</th><th>Дата создания</th><th>Статус</th></tr>",
                 "foot":"<tr><th></th><th>Номер акта</th><th>Транспорт</th><th>Ответственный</th><th>Дата создания</th><th>Статус</th></tr>"}';
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
