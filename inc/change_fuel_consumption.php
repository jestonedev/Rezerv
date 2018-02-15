<?php

include_once 'CarsClass.php';
include_once('filter.php');


if ($_SERVER["REQUEST_METHOD"]==="POST") {
    $args=Helper::ClearArray($_POST);
    $carClass = new CarsClass();
    echo $carClass->UpdateFuelConsumption($args["car_id"], $args["fuel_consumption"], $args["fuel_consumption_date"]);
}