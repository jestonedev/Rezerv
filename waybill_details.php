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
    <script src="scripts/bootstrap.min.js"></script>
    <script src="scripts/bootstrap-datepicker.min.js"></script>
    <script src="scripts/bootstrap-datepicker.ru.min.js"></script>
    <script src="scripts/cars.js"></script>
    <script src="scripts/waybills.js"></script>
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
    if (!isset($_GET['id_waybill']) || intval($_GET['id_waybill']) == 0) {
        ?>
        <div class="alert alert-danger text-center cars__permissions_denied">Неверно указан идентификатор путевого листа
        </div>
        <?php
        die();
    }
    include_once "inc/CarsClass.php";
    include_once "inc/request.php";
    include_once "inc/WaybillsClass.php";
    $carClass = new CarsClass();
    $waybill_id = intval($_GET['id_waybill']);
    $waybillClass = new WaybillsClass();
    $waybillInfo = $waybillClass->GetWaybillInfo($waybill_id);
    ?>
    <ol class="breadcrumb cars-breadcrumb">
        <li><a href="car_repair_card.php?id_car=<?=$waybillInfo["id_car"]?>">
                Эксплуатационно-ремонтная карта автомобиля <?=$waybillInfo["car_number"]?></a>
        </li>
        <li><a href="car_waybills.php?id_car=<?=$waybillInfo["id_car"]?>">Путевые листы автомобиля <?=$waybillInfo["car_number"]?></a></li>
        <li class="active">Просмотр путевого листа №<?=$waybillInfo["waybill_number"]?> автомобиля <?=$waybillInfo["car_number"]?></li>
    </ol>
    <div class="cars__page-header page-header text-center">
        <h1><small>Просмотр путевого листа №<?=$waybillInfo["waybill_number"]?> автомобиля <?=$waybillInfo["car_number"]?></small></h1>
    </div>
    <div class="alert alert-danger text-center col-sm-10 col-sm-offset-1" id="waybill-error" style="display: none">Ошибка</div>
    <ul class="list-group col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2">
        <li class="list-group-item">
            <dl class="dl-horizontal waybill-detail-dl">
                <dt>Номер</dt>
                <dd><?=$waybillInfo["waybill_number"]?></dd>
            </dl>
        </li>
        <li class="list-group-item">
            <dl class="dl-horizontal waybill-detail-dl">
                <dt>Начало действия</dt>
                <dd><?=$waybillInfo["start_date"]?></dd>
                <dt>Окончание действия</dt>
                <dd><?=$waybillInfo["end_date"]?></dd>
            </dl>
        </li>
        <li class="list-group-item">
            <dl class="dl-horizontal waybill-detail-dl">
                <dt>Водитель</dt>
                <dd><?
                    $drivers = $carClass->GetDrivers();
                    foreach($drivers as $driver)
                    {
                        if ($driver["id_driver"] == $waybillInfo["id_driver"])
                        {
                            echo $driver["name"];
                            break;
                        }
                    }
                    ?>
                </dd>
            </dl>
        </li>
        <li class="list-group-item">
        <dl class="dl-horizontal waybill-detail-dl">
            <dt>В распоряжение</dt>
            <dd><?=$waybillInfo["department"]?></dd>
        </dl>
        </li>
        <li class="list-group-item">
            <dl class="dl-horizontal waybill-detail-dl">
                <dt>Показание одометра (до выезда)</dt>
                <dd><?=empty($waybillInfo["mileage_before"]) ? 0 : $waybillInfo["mileage_before"]?> км.</dd>
                <dt>Пробег</dt>
                <dd><?=($waybillInfo["mileage_after"] - $waybillInfo["mileage_before"])?> км.</dd>
                <dt>Показание одометра (возвр.)</dt>
                <dd><?=empty($waybillInfo["mileage_after"]) ? 0 : $waybillInfo["mileage_after"]?> км.</dd>
            </dl>
        </li>
        <li class="list-group-item">
            <dl class="dl-horizontal waybill-detail-dl">
                <dt>Остаток горючего (до выезда)</dt>
                <dd><?=empty($waybillInfo["fuel_before"]) ? 0 : $waybillInfo["fuel_before"]?> л.</dd>
                <dt>Выдано горючего</dt>
                <dd><?=empty($waybillInfo["given_fuel"]) ? 0 : $waybillInfo["given_fuel"]?> л.</dd>
                <dt>Остаток горючего (возвра.)</dt>
                <dd><?=$waybillInfo["fuel_after"]?> л.</dd>
                <dt>Марка горючего</dt>
                <dd><?
                    $fuelTypes = $carClass->GetFuelTypes();
                    $fuelFounded = false;
                    foreach($fuelTypes as $fuelType)
                    {
                        if ($fuelType["id_fuel_type"] == $waybillInfo["id_fuel_type"])
                        {
                            echo $fuelType["fuel_type"];
                            $fuelFounded = true;
                            break;
                        }
                    }
                    if (!$fuelFounded)
                    {
                        echo "Не указана";
                    }
                    ?>
                </dd>
            </dl>
        </li>
    </ul>
    <div class=" col-md-8 col-md-offset-2">
        <div class="panel panel-default waybill-ways-readonly">
            <div class="panel-heading">Маршрут</div>
            <table class="table car__table">
                <thead>
                <tr>
                    <th>Маршрут (из)</th>
                    <th>Маршрут (в)</th>
                    <th>Время выезда</th>
                    <th>Время приезда</th>
                    <th>Проедно (км)</th>
                </tr>
                </thead>
                <tbody>
                <?
                if (count($waybillInfo["ways"]) == 0)
                {
                    ?><tr><td colspan="5" class="text-center"><i>Маршруты отсутствуют</i></td></tr><?
                }
                foreach($waybillInfo["ways"] as $way)
                {
                    $wayFrom = explode(" - ", $way["way"])[0];
                    $wayTo = explode(" - ", $way["way"])[1];
                    $wayTimeFrom = substr($way["start_time"], 0, 5);
                    $wayTimeTo = substr($way["end_time"], 0, 5);
                    $wayDistance = $way["distance"] == "" ? 0 : $way["distance"];
                    ?>
                    <tr>
                        <td><?=$wayFrom?></td>
                        <td><?=$wayTo?></td>
                        <td><?=$wayTimeFrom?></td>
                        <td><?=$wayTimeTo?></td>
                        <td><?=$wayDistance?> км.</td>
                    </tr>
                    <?
                }
                ?>
                </tbody>
            </table>
        </div>
        <div class="text-center waybill-control-buttons clearfix">
            <a title="Вернуться к списку путевых листов" href="car_waybills.php?id_car=<?=$waybillInfo["id_car"]?>" class="btn btn-danger">Назад</a>
            <a title="Редактировать путевой лист" href="waybill_edit.php?id_waybill=<?=$waybillInfo["id_waybill"]?>" class="btn btn-default">Редактировать</a>
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
