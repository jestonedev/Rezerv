<?php

include_once 'WaybillsClass.php';

if (isset($_GET["id_car"]))
{
    $waybill = new WaybillsClass();
    echo json_encode($waybill->AutoCompleteDetails(intval($_GET["id_car"])));
}