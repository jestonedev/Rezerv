<?php

if ($_SERVER["REQUEST_METHOD"]==="POST") {
    include_once "request.php";

    $req = new Request();

    if (!isset($_POST['id_request_number']))
        die('Не указан номер запроса для модификации');

    if (isset($_POST['action']) && ($_POST['action'] == 'reject'))
    {
        $req->RejectRequest(addslashes($_POST['id_request_number']));
    } else
    if (isset($_POST['action']) && ($_POST['action'] == 'accept'))
    {
        $req->AcceptRequest(addslashes($_POST['id_request_number']));
    } else
    if (isset($_POST['action']) && ($_POST['action'] == 'cancel'))
    {
        $req->CancelRequest(addslashes($_POST['id_request_number']));
    } else
    if (isset($_POST['action']) && ($_POST['action'] == 'complete'))
    {
        $req->CompleteRequest(addslashes($_POST['id_request_number']));
    } else
    if (isset($_POST['action']) && ($_POST['action'] == 'uncomplete'))
    {
        $req->UnCompleteRequest(addslashes($_POST['id_request_number']));
    }
}