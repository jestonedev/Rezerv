<?php

include_once('const.php');
include_once('request.php');
include_once('filter.php');

if ($_SERVER["REQUEST_METHOD"]==="POST") {
    $req= new Request();
    if ($_POST["action"]=='insert_act'){
        $args=Helper::ClearArray($_POST);
        echo $req->InsertAct($args);
    } else
    if ($_POST["action"]=='delete_act'){
        if (!isset($_POST['id_repair']))
            die("Не указан номер акта выполненных работ");
        $req->DeleteAct($_POST['id_repair']);
    } else
    if ($_POST["action"]=='update_act'){
        $args=Helper::ClearArray($_POST);
        echo $req->UpdateAct($args);
    } else
    if ($_POST["action"]=='get_act_info'){
        if (!isset($_POST['id_repair']))
            die("Не указан номер акта выполненных работ");
        echo $req->GetActInfo($_POST['id_repair']);
    }
}