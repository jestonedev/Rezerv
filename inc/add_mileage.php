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
        $milage_date = addslashes($_POST['milage_date']);
    else
        die('Отсутствует переменная milage_date');
    if (isset($_POST['milage_value']))
        $milage_value = addslashes($_POST['milage_value']);
    else
        die('Отсутствует переменная milage_value');
    if (isset($_POST['mileage_type']))
        $mileage_type = addslashes($_POST['mileage_type']);
    else
        die('Отсутствует переменная mileage_type');
    if (isset($_POST['car_chief']) && trim($_POST['car_chief']) !== '')
        $car_chief = addslashes($_POST['car_chief']);
    else
        $car_chief = null;

    $mil = new CarsInfoClass();
    echo $mil->AddMileAge($id_car, $milage_date, $milage_value, $mileage_type, $car_chief);
}