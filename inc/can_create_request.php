<?php
include_once "auth.php";

if ($_SERVER["REQUEST_METHOD"]==="POST") {
    if (!isset($_COOKIE['id_request']))
        echo 0;
    if (Auth::hasCreateRequestPrivileges($_COOKIE['id_request']))
        echo 1;
    else
        echo 0;
}