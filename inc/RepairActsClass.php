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

    ///////////////////////////////////////////////////////////////////////////
    //Интерфейс IQuery. Декларирует параметры для корректной работы DataTable//
    ///////////////////////////////////////////////////////////////////////////

    public function Columns() {
        return array('edit_lbl','id_repair','car','respondent','act_date','status');
    }

    public function Table() {
        return "(SELECT CONCAT('<img src=\'img/details_open.png\' value=\'',id_repair,'\'>') AS edit_lbl, cra.id_repair, CONCAT(c.model,' г/н ',c.number) AS car,  r.name AS respondent, DATE(cra.act_date) AS act_date, IF(cra.deleted = 0, 'Действительный', 'Удаленный') AS `status`
          FROM cars_repair_acts cra
            LEFT JOIN respondents r ON (cra.id_performer = r.id_respondent)
            LEFT JOIN cars c ON (cra.id_car = c.id)) t";
    }

    public function Where() {
        return "";
    }

    public function IndexColumn() {
        return "id_repair";
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
