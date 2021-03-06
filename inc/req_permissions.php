<?php

include_once "auth.php";

$req_mask = 0;

if (Auth::hasPrivilege(AUTH_READ_TRANSPORT_REQUEST))
    $req_mask += 1;

if (Auth::hasPrivilege(AUTH_READ_SMALL_HALL_REQUEST))
    $req_mask += 2;

if (Auth::hasPrivilege(AUTH_READ_GREAT_HALL_REQUEST))
    $req_mask += 4;

if (Auth::hasPrivilege(AUTH_READ_TRANSPORT_MILEAGE))
    $req_mask += 8;

echo $req_mask;