<?php

include_once "request.php";

if (!($_SERVER["REQUEST_METHOD"]==="POST"))
    die('Некорректный метод обращения');
if (!isset($_POST['action']))
    die('Не указано выполняемое действие');
if ($_POST['action'] == 'request')
{
    if (isset($_POST['id_request_number']))
        $id_request_number = addslashes($_POST['id_request_number']);
    else
        die('Отсутствует переменная id_request_number');
    $req = new Request();
    echo $req->GetRequestDetails($id_request_number);
} else
if ($_POST['action'] == 'act')
{
    if (isset($_POST['id_repair']))
        $id_repair = addslashes($_POST['id_repair']);
    else
        die('Отсутствует переменная id_repair');
    $req = new Request();
    echo $req->GetActDetails($id_repair);
} else
if ($_POST['action'] == 'waybill')
{
    if (isset($_POST['id_waybill']))
        $id_waybill = addslashes($_POST['id_waybill']);
    else
        die('Отсутствует переменная id_waybill');
    $req = new Request();
    echo $req->GetWaybillDetails($id_waybill);
}