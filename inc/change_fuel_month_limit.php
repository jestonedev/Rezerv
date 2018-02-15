<?php

include_once 'CarsClass.php';
include_once('filter.php');


if ($_SERVER["REQUEST_METHOD"]==="POST") {
    $args=Helper::ClearArray($_POST);
    $carClass = new CarsClass();
    echo $carClass->UpdateFuelMonthLimit($args["car_id"], $args["fuel_month_limit"], $args["fuel_month_limit_date"]);
}