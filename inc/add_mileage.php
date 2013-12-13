<?php
/**
 * Created by JetBrains PhpStorm.
 * User: IgnVV
 * Date: 03.04.13
 * Time: 12:00
 * To change this template use File | Settings | File Templates.
 */

include_once "CarsInfoClass.php";

if ($_SERVER["REQUEST_METHOD"]==="POST") {
    if (isset($_POST['id_car']))
        $id_car = addslashes($_POST['id_car']);
    else
        die('Отсутствует переменная id_car');
    if (isset($_POST['milage_date']))
        $id_car = addslashes($_POST['milage_date']);
    else
        die('Отсутствует переменная milage_date');
    if (isset($_POST['milage_value']))
        $id_car = addslashes($_POST['milage_value']);
    else
        die('Отсутствует переменная milage_value');
    if (isset($_POST['mileage_type']))
        $id_car = addslashes($_POST['mileage_type']);
    else
        die('Отсутствует переменная mileage_type');
    $mil = new CarsInfoClass();
    echo $mil->AddMileAge($_POST['id_car'], $_POST['milage_date'], $_POST['milage_value'], $_POST['mileage_type']);
}