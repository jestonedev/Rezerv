<?php
include_once "auth.php";

if ($_SERVER["REQUEST_METHOD"]==="POST") {
    if (Auth::hasPrivilege(AUTH_MANAGE_TRANSPORT))
        echo 1;
    else
        echo 0;
}