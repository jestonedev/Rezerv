<?php

include_once "auth.php";

class Helper{

    private static $requestDate;

    public static function ClearArray($array){
        $arr=$array;
        array_walk($arr,'Helper::ClearString');
        return $arr;
    }
    
    public static function ClearString(&$val){
        if (is_array($val)) return;
        $val=addslashes($val);
    }

    public static function ClearJsonString($val){
        return str_replace('"', '\"', str_replace(array("\r\n", "\r", "\n"),'<br/>',htmlspecialchars(stripslashes($val))));
    }

    public static function TimeToFloat($time)
    {
        if (strpos($time,':'))
        {
            $time_parts = explode(':',$time);
            $hour = intval($time_parts[0]);
            $minute = round(($time_parts[1] / 60)*100);
            $time = $hour.".".$minute;
        }
        return $time;
    }
    
	//Валидация полей, $name - имя поля, $value - значение поля, $type - тип значения поля, $required - обязательное ли поле
    public static function Check($name, $value, $type, $required=0){
        if (empty($value) && ($required==1)) return "Поле [".$name."] обязательно для заполнения<br>";
        if (mb_strlen($value) > 255) return "Поле [".$name."] превышает максимально допустимую длину в 255 символов<br>";
        switch ($type){
            case "string":
                return "";
                break;
            case "int":
                //Бардак в том, что в функцию передаются числа в виде строк. И могут передаваться пустые(необязательные) строки-числа,
                //что приведет к ошибке. Перековыривать чужую архитектуру не хочу. Буду подстраиваться.
                if (empty($value))
                    $value = 0;
                if (filter_var($value,FILTER_VALIDATE_INT) == true)
                    return "";
                else
                    return "Поле [".$name."] заполнено некорректно<br>";
                break;
            case "float":
                if (empty($value))
                    $value = 0;
                //В float хранится время, преобразуем время к float (костыль!)
                $value = Helper::TimeToFloat($value);
                if (filter_var($value,FILTER_VALIDATE_FLOAT) == true)
                    return "";
                else
                    return "Поле [".$name."] заполнено некорректно. Правильный формат hh:mm<br>";
                break;
            case "date":
                $tmp = explode('.', $value );
                if (trim($tmp[0],"_") == $tmp[0] &&
                    trim($tmp[1],"_") == $tmp[1] &&
                    trim($tmp[2],"_") == $tmp[2] &&
                    checkdate($tmp[1],$tmp[0],$tmp[2]))
                {
                    Helper::$requestDate = $value;
                    return "";
                }
                else
                    return "Поле [".$name."] заполнено некорректно<br>";
                break;
            case "time":
                if (strtotime($value))
                {
                    //Если есть право подавать заявку на любое время, то сразу возвращаемся
                    if (Auth::hasPrivilege(AUTH_ANY_DATETIME_SEND_REQUEST))
                        return "";

                    //Если нет такого права, то проверяем, нормальная ли дата и время
                    if (!empty(Helper::$requestDate))
                    {
						date_default_timezone_set('Asia/Irkutsk');
                        $now = new DateTime();
                        $reqDate = new DateTime();
                        $dateParts = explode('.', Helper::$requestDate);
                        $reqDate->setDate($dateParts[2], $dateParts[1], $dateParts[0]);
                        $timeParts = explode(':', $value);
                        $reqDate->setTime($timeParts[0], $timeParts[1]);
                        //Если дата и время подачи заявки меньше, чем текущая + 8 часов, то выдать ошибку
                        //Следующий код необходим, т.к. на сервере не стоит PHP 5.3.0 и нет объекта DateInterval и функции date_diff
                        $req_date = mktime(
                            intval($reqDate->format('H')),
                            intval($reqDate->format('i')),
                            intval($reqDate->format('s')),
                            intval($reqDate->format('m')),
                            intval($reqDate->format('d')),
                            intval($reqDate->format('Y'))
                        );
                        $minimal_date = mktime(                 //Выражаем текущую дату в секундах
                            intval($now->format('H')),
                            intval($now->format('i')),
                            intval($now->format('s')),
                            intval($now->format('m')),
                            intval($now->format('d')),
                            intval($now->format('Y')));
                        //Если дата и время подачи заявки меньше минимальной даты и времени, выдаем ошибку
                        if ( $minimal_date > $req_date)
                            return "Разрешено подавать заявки не ранее, чем на текущую дату и время<br>";
                        //Если сейчас больше 16:00
                        if ( $now->format('H') >=16 )
                        {
                            //Если сейчас не пятница, то запретить подавать на завтра, а если пятница (и тем более выходные), то на все выходные
                            if (( date("w", $minimal_date) < 5) && (date("w", $minimal_date)) > 0)
                            {
                                //Выставляем минимальную дату на заявку как послезавтра
                                $minimal_date = mktime(
                                    0, 0, 0,
                                    intval($now->format('m')),
                                    intval($now->format('d')),
                                    intval($now->format('Y')));
                                $minimal_date = $minimal_date + 48*60*60;
                                //Если дата и время подачи заявки меньше минимальной даты и времени, выдаем ошибку
                                if ( $minimal_date > $req_date )
                                    return "Вы не можете подать заявку на после 16:00 на сегодня и завтра<br>";
                            } else
                            if (( date("w", $minimal_date) >= 5) || (date("w", $minimal_date) == 0))
                            {
                                //Выставляем минимальную дату на заявку как вторник
                                $minimal_date = mktime(
                                    0, 0, 0,
                                    intval($now->format('m')),
                                    intval($now->format('d')),
                                    intval($now->format('Y')));
                                $minimal_date = $minimal_date + 96*60*60;
                                //Если дата и время подачи заявки меньше минимальной даты и времени, выдаем ошибку
                                if ( $minimal_date > $req_date )
                                    return "Вы не можете подать заявку после 16:00 на период до вторника<br>";
                            }
                        }
                    }
                    return "";
                }
                else
                    return "Поле [".$name."] заполнено некорректно<br>";
                break;
            case "boolean":
                //Опять же, на самом деле булевские поля в программе на самом деле хранят значения числовых идентификаторов.
                //Делать проверку на принадлежность к логическому типу бессмысленно
                return "";
                break;
            default:
                return "Тип данных поля [".$name."] неизвестен<br>";
                break;
        }
    }
}