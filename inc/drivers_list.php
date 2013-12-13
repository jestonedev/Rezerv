<?php

include_once "request.php";

if ($_SERVER["REQUEST_METHOD"]==="POST") {
    $req = new Request();
    echo $req->CreateDriversComboBox();
}