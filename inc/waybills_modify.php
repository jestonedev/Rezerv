<?php

include_once('const.php');
include_once('request.php');
include_once('filter.php');

if ($_SERVER["REQUEST_METHOD"]==="POST") {
    $req= new Request();
    if ($_POST["action"]=='insert_waybill'){
        $args=Helper::ClearArray($_POST);
        echo $req->InsertWaybill($args);
    } else
        if ($_POST["action"]=='delete_waybill'){
            if (!isset($_POST['id_waybill']))
                die("Не указан номер путевого листа");
            $req->DeleteWaybill($_POST['id_waybill']);
        } else
            if ($_POST["action"]=='update_waybill'){
                $args=Helper::ClearArray($_POST);
                echo $req->UpdateWaybill($args);
            } else
                if ($_POST["action"]=='get_waybill_info'){
                    if (!isset($_POST['id_waybill']))
                        die("Не указан номер путевого листа");
                    echo $req->GetWaybillInfo($_POST['id_waybill']);
                }
}