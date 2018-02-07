<?php
/**
 * Created by PhpStorm.
 * User: Ignatov
 * Date: 07.02.2018
 * Time: 11:59
 */

include_once "GanttClass.php";
include_once "filter.php";

$args=Helper::ClearArray($_GET);
if (!isset($args['date_from']) ||
    !isset($args['date_to']) ||
    !isset($args['id_car']) ||
    !isset($args['request_states']) ||
    !isset($args['request_type_id']))
{
    die('Не переданы обязательные параметры');
}

$gantt = new GanttClass();
echo $gantt->GetRangeInfo($args['date_from'], $args['date_to'], $args['request_states'], $args['request_type_id'], $args['id_car']);