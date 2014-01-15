<?php

include_once "const.php";

//Привелегии
define("AUTH_READ_DATA",1);                        //Минимальное право - чтение данных из БД. При отсутствии редирект на страницу запрета доступа
define("AUTH_MODIFY_STATUS_TRANSPORT_REQUEST",2);  //Право модификации статуса заявки на транспорт
define("AUTH_MODIFY_STATUS_SMALL_HALL_REQUEST",4); //Право модификации статуса заявки на зал заседания думы
define("AUTH_MODIFY_STATUS_GREAT_HALL_REQUEST",8); //Право модификации статуса заявки на конференц-зал
define("AUTH_CHANGE_DEPARTMENT_REQUEST",16);       //Право позволяет подавать заявки от другого департамента
define("AUTH_ANY_DATETIME_SEND_REQUEST",32);       //Разрешение на подачу заявок на любое время
define("AUTH_ALL_DEPARTMENTS_READ_DATA",64);       //Право на чтение данных по всем департаментам, включая отчеты по всем и календарь по всем департаментам
define("AUTH_SEND_TRANSPORT_REQUEST",128);         //Право позволяет подавать заявки на транспорт
define("AUTH_SEND_SMALL_HALL_REQUEST",256);        //Право позволяет подавать заявки на зал заседания думы
define("AUTH_SEND_GREAT_HALL_REQUEST",512);        //Право позволяет подавать заявки на конференц-зал
define("AUTH_READ_TRANSPORT_REQUEST",1024);        //Чтение транспортных заявок
define("AUTH_READ_SMALL_HALL_REQUEST",2048);       //Чтение заявок на зал заседания думы
define("AUTH_READ_GREAT_HALL_REQUEST",4096);       //Чтение заявок на конференц-зал
define("AUTH_READ_TRANSPORT_MILEAGE",8192);        //Чтение информации о пробеге транспорта
define("AUTH_UNLIMIT_REQUESTS",16384);             //Безлимит по заявкам
define("AUTH_READ_REPAIR_ACTS",32768);             //Чтение информации об актах выполненых работ по обслуживанию автотранспорта
define("AUTH_MODIFY_REPAIR_ACTS",65536);           //Создание и изменение актов выполненых работ по обслуживанию автотранспорта
define("AUTH_READ_WAYBILLS",131072);               //Чтение информации о путевых листах
define("AUTH_MODIFY_WAYBILLS",262144);             //Создание и изменение путевых листов

class Auth
{
    private static $privilege = -1;

    public static function hasPrivilege($privilege)
    {
        if (Auth::$privilege == -1)
        {
            $login = addslashes($_SERVER['AUTH_USER']);
            $query = "SELECT privilege FROM user_privileges WHERE user = '".$login."'";
            $link = mysqli_connect(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_BASE) or
                die("Не удалось подключиться к серверу базы данных");
            $result = mysqli_query($link, $query) or
                die("Не удалось выполнить запрос к базе данных");
            $row = mysqli_fetch_assoc($result);
            //Буферизируем привилегию для уменьшения количества запросов к БД
            Auth::$privilege = $row['privilege'];
            mysqli_close($link);
        }
        if ((Auth::$privilege & $privilege) == $privilege)
            return true;
        else
            return false;
    }

    //Есть ли права на модификацию статуса заявок
    public static function hasModifyStatusRequestsPrivileges($id_request_number)
    {
        if (!isset($_COOKIE["id_request"]))
            die("Не удалось найти идентификатор группы запросов");
        $id_request = $_COOKIE["id_request"];
        if ((($id_request == 1) && Auth::hasPrivilege(AUTH_MODIFY_STATUS_TRANSPORT_REQUEST)) ||
            (($id_request == 2) && Auth::hasPrivilege(AUTH_MODIFY_STATUS_GREAT_HALL_REQUEST)) ||
            (($id_request == 3) && Auth::hasPrivilege(AUTH_MODIFY_STATUS_SMALL_HALL_REQUEST)))
            return true;
        else
            return false;
    }

    public static function statusRequestIs($id_request_number, $id_request_status)
    {
        $query = "SELECT request_state FROM request_number WHERE id_request_number = ".$id_request_number;
        $link = mysqli_connect(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_BASE) or
            die("Не удалось подключиться к серверу базы данных");
        $result = mysqli_query($link, $query) or
            die("Не удалось выполнить запрос к базе данных");
        $row = mysqli_fetch_assoc($result);
        mysqli_close($link);
        if ($row && ($row['request_state'] == $id_request_status))
            return true;
        else
            return false;
    }

    //Заявка в статусе "Принята к рассмотрению"
    public static function isLookingRequest($id_request_number)
    {
        return Auth::statusRequestIs($id_request_number, 1);
    }

    //Заявка в статусе "Принята к исполнению"
    public static function isAcceptedRequest($id_request_number)
    {
        return Auth::statusRequestIs($id_request_number, 3);
    }

    public static function hasCancelYourselfRequestPrivileges($id_request_number)
    {
        $query = "SELECT request_state, user FROM request_number WHERE id_request_number = ".$id_request_number;
        $link = mysqli_connect(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_BASE) or
            die("Не удалось подключиться к серверу базы данных");
        $result = mysqli_query($link, $query) or
            die("Не удалось выполнить запрос к базе данных");
        $row = mysqli_fetch_assoc($result);
        mysqli_close($link);
        //Если статус заявки "В рассмотрении"
        if ($row && (($row['request_state'] == 1) || ($row['request_state'] == 3)))
        {
            //И если пользователь является хозяином заявки
            if ($row['user'] == $_SERVER['AUTH_USER'])
                return true;
            else
                return false;
        } else
            return false;
    }

    public static function hasCreateRequestPrivileges($id_request)
    {
        if (((($id_request == 1) && Auth::hasPrivilege(AUTH_SEND_TRANSPORT_REQUEST)) ||
            (($id_request == 2) && Auth::hasPrivilege(AUTH_SEND_GREAT_HALL_REQUEST)) ||
            (($id_request == 3) && Auth::hasPrivilege(AUTH_SEND_SMALL_HALL_REQUEST))))
            return true;
        else
            return false;
    }
}