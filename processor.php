<?php
include_once('inc/const.php');
include_once('inc/request.php');
include_once('inc/filter.php');

if ($_SERVER["REQUEST_METHOD"]==="POST") {
    if (isset($_COOKIE["id_request"]))
        $id_request=addslashes($_COOKIE["id_request"]);
    else
        die('Отсутствует идентификатор запроса');

    $req= new Request();

    switch($_POST["action"])
    {
        case 'process_request':
            echo "<pre>";
            $args=Helper::ClearArray($_POST);
            echo $req->ProcessRequest($args, $_POST["id_request_number"]);
            echo "</pre>";
            break;
        case 'display_request_form':
            echo "<pre>";
            $args=Helper::ClearArray($_POST);
            echo $req->BuildRequest($id_request, 0);
            echo "</pre>";
            break;
        case 'modify_request_form':
            echo "<pre>";
            $args=Helper::ClearArray($_POST);
            echo $req->BuildRequest($id_request, $_POST['id_request_number']);
            echo "</pre>";
            break;
        case 'display_gantt_settings_form':
            echo $req->BuildGanttSettingsForm($id_request);
            break;
        case 'display_calendar_settings_form':
            echo $req->BuildCalendarSettingsForm($id_request);
            break;
        case 'display_all_transport_combobox':
            echo $req->CreateCarsComboBox(true);
            break;
        case 'display_all_fuel_type_combobox':
            echo $req->CreateFuelTypesComboBox(true);
            break;
    }
}