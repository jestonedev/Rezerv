<?php
include_once "auth.php";

if ($_SERVER["REQUEST_METHOD"]==="POST") {
    if (Auth::hasPrivilege(AUTH_MODIFY_REPAIR_ACTS))
        echo 1;
    else
        echo 0;
}