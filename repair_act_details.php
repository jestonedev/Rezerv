<?php
header("Cache-Control: no-cache, must-revalidate");
?>

<html>
<head>
    <meta charset="utf-8">
    <title>Транспорт</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <link rel="shortcut icon" href="favicon.png" type="image/png">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="css/bootstrap-datepicker3.min.css">
    <link rel="stylesheet" href="css/cars.css">
    <script src="scripts/jquery-1.11.2.min.js"></script>
    <script src="scripts/jquery.mask.min.js"></script>
    <script src="scripts/bootstrap.min.js"></script>
    <script src="scripts/bootstrap-datepicker.min.js"></script>
    <script src="scripts/bootstrap-datepicker.ru.min.js"></script>
    <script src="scripts/cars.js"></script>
    <script src="scripts/repair.js"></script>
    <script src="scripts/helper.js"></script>
</head>
<body>
<?php
include_once 'inc/auth.php';

if (Auth::hasPrivilege(AUTH_MANAGE_TRANSPORT)) {
    ?>
    <nav class="navbar navbar-default" role="navigation">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
                <span class="sr-only">Переключить навигацию</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#">Транспорт</a>
        </div>

        <div class="collapse navbar-collapse navbar-ex1-collapse">
            <ul class="nav navbar-nav">
                <li><a href="cars.php">Автомобили</a></li>
                <li><a href="tires.php">Автошины</a></li>
                <li><a href="accumulator.php">Аккумуляторы</a></li>
                <li><a href="index.php">Заявки</a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Отчеты <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li><a href="car_reports/fuel_by_month_report.php">Расход топлива за месяц</a></li>
                        <li><a href="car_reports/fuel_by_quarter_report.php">Расход топлива за квартал</a></li>
                        <li><a href="car_reports/fuel_by_period_report.php">Расход топлива за период</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
    <?php
    if (!isset($_GET['id_repair']) || intval($_GET['id_repair']) == 0) {
        ?>
        <div class="alert alert-danger text-center cars__permissions_denied">Неверно указан идентификатор акта выполненных работ
        </div>
        <?php
        die();
    }
    include_once "inc/CarsClass.php";
    include_once "inc/request.php";
    include_once "inc/RepairActsClass.php";
    $repair_id = intval($_GET['id_repair']);
    $carClass = new CarsClass();
    $repairActsClass = new RepairActsClass();
    $repairActInfo = $repairActsClass->GetRepairActInfo($repair_id);
    ?>
    <ol class="breadcrumb cars-breadcrumb">
        <li><a href="car_repair_card.php?id_car=<?=$repairActInfo["id_car"]?>">
                Эксплуатационно-ремонтная карта автомобиля <?=$repairActInfo["number"]?></a>
        </li>
        <li><a href="car_repair.php?id_car=<?=$repairActInfo["id_car"]?>">Проведение ремонта автомобиля <?=$repairActInfo["number"]?></a></li>
        <li class="active">Акт выполненных работ автомобиля <?=$repairActInfo["number"]?></li>
    </ol>
    <div class="cars__page-header page-header text-center">
        <h1><small>Акт выполненных работ автомобиля <?=$repairActInfo["number"]?></small></h1>
    </div>
    <div class="alert alert-danger text-center col-sm-10 col-sm-offset-1" id="repair-act-error" style="display: none">Ошибка</div>




    <ul class="list-group col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2">
        <li class="list-group-item">
            <dl class="dl-horizontal waybill-detail-dl">
                <dt>Номер</dt>
                <dd><?=$repairActInfo["repair_act_number"]?></dd>
                <dt>Дата формирования</dt>
                <dd><?=$repairActInfo["act_date"]?></dd>
            </dl>
        </li>
        <? if (!empty($repairActInfo["repair_start_date"]) || !empty($repairActInfo["repair_end_date"]) ||
               !empty($repairActInfo["wait_start_date"]) || !empty($repairActInfo["wait_end_date"])) { ?>
        <li class="list-group-item">
            <dl class="dl-horizontal waybill-detail-dl">
                <? if (!empty($repairActInfo["repair_start_date"]) || !empty($repairActInfo["repair_end_date"])) { ?>
                <dt>Ремонт</dt>
                <dd><?=
                    !empty($repairActInfo["repair_start_date"]) ?
                        $repairActInfo["repair_start_date"]." ".$repairActInfo["repair_start_time"] : ""
                    ?><?=!empty($repairActInfo["repair_start_date"]) && !empty($repairActInfo["repair_end_date"]) ?
                        ($repairActInfo["repair_start_date"] == $repairActInfo["repair_end_date"] ? "-" : " - ") : "" ?><?=
                    !empty($repairActInfo["repair_end_date"]) ?
                        ($repairActInfo["repair_start_date"] == $repairActInfo["repair_end_date"] ?
                            $repairActInfo["repair_end_time"] :
                            $repairActInfo["repair_end_date"]." ".$repairActInfo["repair_end_time"]) : ""
                    ?>
                </dd>
                <? } ?>
                <? if (!empty($repairActInfo["wait_start_date"]) || !empty($repairActInfo["wait_end_date"])) { ?>
                <dt>Ожиданияе</dt>
                <dd><?=
                    !empty($repairActInfo["wait_start_date"]) ? $repairActInfo["wait_start_date"]." ".$repairActInfo["wait_start_time"] : ""
                    ?><?=!empty($repairActInfo["wait_start_date"]) && !empty($repairActInfo["wait_end_date"]) ?
                        ($repairActInfo["wait_start_date"] == $repairActInfo["wait_end_date"] ? "-" : " - "): "" ?><?=
                    !empty($repairActInfo["wait_end_date"]) ?
                        ($repairActInfo["wait_start_date"] == $repairActInfo["wait_end_date"] ?
                            $repairActInfo["wait_end_time"] :
                            $repairActInfo["wait_end_date"]." ".$repairActInfo["wait_end_time"]) : ""
                    ?>
                </dd>
                <? } ?>
            </dl>
        </li>
        <? } ?>
        <li class="list-group-item">
            <dl class="dl-horizontal waybill-detail-dl">
                <dt>Ответственный</dt>
                <dd><?
                    $respondents = $carClass->GetRespondents();
                    foreach($respondents as $respondent)
                    {
                        if ($respondent["id_respondent"] == $repairActInfo["id_respondent"])
                        {
                            echo $respondent["name"];
                            break;
                        }
                    }
                    ?>
                </dd>
            </dl>
            <dl class="dl-horizontal waybill-detail-dl">
                <dt>Водитель</dt>
                <dd><?
                    $drivers = $carClass->GetDrivers();
                    foreach($drivers as $driver)
                    {
                        if ($driver["id_driver"] == $repairActInfo["id_driver"])
                        {
                            echo $driver["name"];
                            break;
                        }
                    }
                    ?>
                </dd>
            </dl>
            <dl class="dl-horizontal waybill-detail-dl">
                <dt>Механик/исполнитель</dt>
                <dd><?
                    $mechanics = $carClass->GetMechanics();
                    foreach($mechanics as $mechanic)
                    {
                        if ($mechanic["id_mechanic"] == $repairActInfo["id_mechanic"])
                        {
                            echo $mechanic["name"];
                            break;
                        }
                    }
                    ?>
                </dd>
            </dl>
        </li>
        <? if (!empty($repairActInfo["odometer"])) {?>
        <li class="list-group-item">
            <dl class="dl-horizontal waybill-detail-dl">
                <dt>Показания одометра</dt>
                <dd><?=number_format($repairActInfo["odometer"], 0, ',', ' ')?> км.</dd>
            </dl>
        </li>
        <? } ?>
        <? if (!empty($repairActInfo["reason_for_repairs"]) || !empty($repairActInfo["work_performed"])) { ?>
            <li class="list-group-item">
                <dl class="dl-horizontal waybill-detail-dl">
                    <? if (!empty($repairActInfo["reason_for_repairs"])) { ?>
                    <dt>Причина ремонта</dt>
                    <dd><?=$repairActInfo["reason_for_repairs"]?></dd>
                    <? } ?>
                    <? if (!empty($repairActInfo["work_performed"])) { ?>
                    <dt>Выполненные работы</dt>
                    <dd><?=$repairActInfo["work_performed"]?></dd>
                    <? } ?>
                </dl>
            </li>
        <? } ?>
    </ul>
    <div class=" col-md-8 col-md-offset-2">
        <div class="panel panel-default repair-act-materials-readonly">
            <div class="panel-heading">Материалы</div>
            <table class="table car__table">
                <thead>
                <tr>
                    <th>Наименование</th>
                    <th>Количество</th>
                    <th>Примечание</th>
                </tr>
                </thead>
                <tbody>
                <?
                if (count($repairActInfo["materials"]) == 0)
                {
                    ?><tr><td colspan="3" class="text-center"><i>Материалы отсутствуют</i></td></tr><?
                }
                foreach($repairActInfo["materials"] as $material)
                {
                    $materialName = $material["material_name"];
                    $materialCount = $material["material_count"];
                    $materialDescription = $material["material_description"];
                    ?>
                    <tr>
                        <td><?=$materialName?></td>
                        <td><?=$materialCount?></td>
                        <td><?=$materialDescription?></td>
                    </tr>
                    <?
                }
                ?>
                </tbody>
            </table>
        </div>
        <div class="text-center repair-act-control-buttons clearfix">
            <a title="Вернуться к списку актов выполненных работ" href="car_repair.php?id_car=<?=$repairActInfo["id_car"]?>" class="btn btn-danger">Назад</a>
            <a title="Редактировать акт выполненных работ" href="repair_act_edit.php?id_repair=<?=$repairActInfo["id_repair"]?>" class="btn btn-default">Редактировать</a>
        </div>
    </div>
    <?php
} else {
    ?>
    <div class="alert alert-danger text-center cars__permissions_denied">У вас нет прав на просмотр и редактирование
        информации в этом разделе
    </div>
    <?php
}
?>
</body>
</html>
