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
}