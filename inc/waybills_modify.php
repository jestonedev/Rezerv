<?php

include_once('WaybillsClass.php');
include_once('filter.php');

if ($_SERVER["REQUEST_METHOD"]==="POST") {
    $waybillsClass = new WaybillsClass();
    if ($_POST["action"]=='insert_waybill'){
        $args=Helper::ClearArray($_POST);
        echo $waybillsClass->InsertWaybill($args);
    } else
        if ($_POST["action"]=='delete_waybill'){
            if (!isset($_POST['id_waybill']))
                die("Не указан номер путевого листа");
            $waybillsClass->DeleteWaybill($_POST['id_waybill']);
        } else
            if ($_POST["action"]=='update_waybill'){
                $args=Helper::ClearArray($_POST);
                echo $waybillsClass->UpdateWaybill($args);
            }
}