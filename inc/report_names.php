<?php
/**
 * Created by JetBrains PhpStorm.
 * User: IgnVV
 * Date: 18.01.13
 * Time: 11:41
 * To change this template use File | Settings | File Templates.
 */


if ($_SERVER["REQUEST_METHOD"]==="POST") {
    include_once "ReportClass.php";

    $report = new ReportClass();
    echo $report->GetReportNames();
}