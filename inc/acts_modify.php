<?php

include_once('const.php');
include_once('RepairActsClass.php');
include_once('filter.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $repairActClass = new RepairActsClass();
    $args = Helper::ClearArray($_POST);
    if ($_POST["action"] == 'insert_act') {
        echo $repairActClass->InsertAct($args);
    } else
        if ($_POST["action"] == 'delete_act') {
            if (!isset($_POST['id_repair']))
                die("Не указан номер акта выполненных работ");
            $repairActClass->DeleteAct($_POST['id_repair']);
        } else
            if ($_POST["action"] == 'update_act') {
                echo $repairActClass->UpdateAct($args);
            }
}