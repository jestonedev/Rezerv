<?php

include_once('const.php');
include_once('request.php');
include_once('filter.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $req = new Request();
    $args = Helper::ClearArray($_POST);
    if ($_POST["action"] == 'insert_act') {
        echo $req->InsertAct($args);
    } else
        if ($_POST["action"] == 'delete_act') {
            if (!isset($_POST['id_repair']))
                die("Не указан номер акта выполненных работ");
            $req->DeleteAct($_POST['id_repair']);
        } else
            if ($_POST["action"] == 'update_act') {
                echo $req->UpdateAct($args);
            } else
                if ($_POST["action"] == 'get_act_info') {
                    if (!isset($args['id_repair']))
                        die("Не указан номер акта выполненных работ");
                    echo $req->GetActInfo($args['id_repair']);
                }
}