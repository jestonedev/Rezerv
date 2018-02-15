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
            </ul>
        </div>
    </nav>

    <?php
    if (!isset($_GET['id_car']) || intval($_GET['id_car']) == 0)
    {
    ?>
        <div class="alert alert-danger text-center cars__permissions_denied">Неверно указан идентификатор автомобиля</div>
     <?php
        die();
    }
    include_once "inc/CarsClass.php";
    $car_id = intval($_GET['id_car']);
    $carClass = new CarsClass();
    $carInfo = $carClass->GetCarInfo($car_id);
    ?>

    <div class="cars__page-header page-header text-center">
        <h1><small>Эксплуатационно-ремонтная карта автомобиля <?=$carInfo["number"]?></small></h1>
    </div>
    <div class="col-md-12 clearfix">
    <div class="col-md-8 col-md-offset-2">
    <div class="row cars-row text-center">
        <div class="col-md-6 col-xs-12">
            <dl>
                <dt>Марка автомобиля</dt>
                <dd><?=$carInfo["type"]." ".$carInfo["model"]?></dd>
            </dl>
        </div>
        <div class="col-md-6 col-xs-12">
            <dl>
                <dt>Гос. номер</dt>
                <dd><?=$carInfo["number"]?></dd>
            </dl>
        </div>
    </div>
    <div class="row cars-row text-center">
        <div class="col-md-6 col-xs-12">
            <dl>
                <dt>Статус</dt>
                <?
                    if ($carInfo["is_active"] == 1) {
                        ?>
                        <dd><div class="label label-success car-repair-card__state">Активный</div></dd>
                        <?
                    } else {
                        ?>
                        <dd><div class="label label-warning car-repair-card__state">Неактивный</div></dd>
                        <?
                    }
                ?>
            </dl>
        </div>
        <div class="col-md-6 col-xs-12">
            <dl>
                <dt>Текущая норма расхода топлива на 100 км.</dt>
                <dd><span id="current-fuel-consumption"><?=number_format($carInfo["current_fuel_consumption"], 3, '.', ' ')?></span> л.
                    <button class="btn btn-info btn-xs cars__change-norm-button">Изменить</button></dd>
            </dl>
        </div>
    </div>
    <div class="row cars-row text-center">
        <div class="col-md-6 col-xs-12">
            <dl>
                <dt>Текущий пробег</dt>
                <dd><?=number_format($carInfo["current_mileages"],0, ',', ' ')?></dd>
            </dl>
        </div>
        <div class="col-md-6 col-xs-12">
            <dl>
                <dt>Текущий лимит расхода топлива в месяц</dt>
                <dd><span id="current-fuel-month-limit"><?=number_format($carInfo["current_fuel_month_limit"], 3, '.', ' ')?></span> л.
                    <button class="btn btn-info btn-xs cars__change-month-fuel-limit-button">Изменить</button></dd>
            </dl>
        </div>
    </div>
    <div class="row cars-row text-center">
        <div class="col-md-6 col-xs-12">
            <dl>
                <dt>Установленный шинокомплект</dt>
                <dd>Nokian 195/65/R15<button class="btn btn-info btn-xs cars__change-tires-button">Смена</button></dd>
            </dl>
        </div>
        <div class="col-md-6 col-xs-12">
            <dl>
                <dt>Пробег с момента установки</dt>
                <dd>9873</dd>
            </dl>
        </div>
    </div>
    <div class="row cars-row text-center">
        <div class="col-md-6 col-xs-12">
            <dl>
                <dt>Установленный аккумулятор</dt>
                <dd>AkTEX 6CT-70
                    <button class="btn btn-info btn-xs cars__change-accum-button">Смена</button>
                    <button class="btn btn-info btn-xs cars__maintain-accum-button">Обслуживание</button>
                </dd>
            </dl>
        </div>
        <div class="col-md-6 col-xs-12">
            <dl>
                <dt>Пробег с момента установки</dt>
                <dd>9873</dd>
            </dl>
        </div>
    </div>
    </div>
    </div>
    <div class="row cars-row text-center cars__manage-buttons">
        <a href="car_waybills.php?id_car=<?=$carInfo["id_car"]?>" class="btn btn-success cars__manage-button">Путевые листы</a>
        <a href="car_repair.php?id_car=<?=$carInfo["id_car"]?>" class="btn btn-success cars__manage-button">Проведение ремонта</a>
        <a href="car_maintenance_service.php?id_car=<?=$carInfo["id_car"]?>" class="btn btn-success cars__manage-button">Проведение планового ТО</a>
    </div>
    <?
} else {
    ?>
    <div class="alert alert-danger text-center cars__permissions_denied">У вас нет прав на просмотр и редактирование информации в этом разделе</div>
    <?php
}
?>
<div class="modal fade" id="car-fuel-consumption-change" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Изменение нормы расхода топлива</h4>
            </div>
            <div class="modal-body clearfix">
                <div class="alert alert-danger text-center col-sm-10 col-sm-offset-1 fuel-consumption-data-error" style="display: none">
                </div>
                <form class="fuel-consumption-form form-horizontal col-sm-10 col-sm-offset-1" role="form">
                    <input type="hidden" id="fuelConsumptionIdCar" value="<?=$carInfo["id_car"]?>">
                    <div class="form-group">
                        <label for="fuelConsumption" class="control-label">Норма</label>
                        <input type="text" class="form-control" id="fuelConsumption" placeholder="Норма расхода топлива">
                    </div>
                    <div class="form-group">
                        <label for="fuelConsumptionDate" class="control-label">На дату</label>
                        <div class="input-group date">
                            <input type="text" class="form-control date" value="<?=date('d.m.Y')?>" id="fuelConsumptionDate" placeholder="Дата начала действия нормы расхода топлива">
                            <div class="input-group-addon select-date-button">
                                <span class="glyphicon glyphicon-th"></span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="save-fuel-consumption">Сохранить</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" id="car-fuel-month-limit-change" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Изменение лимита расхода топлива в месяц</h4>
            </div>
            <div class="modal-body clearfix">
                <div class="alert alert-danger text-center col-sm-10 col-sm-offset-1 fuel-month-limit-data-error" style="display: none">
                </div>
                <form class="fuel-consumption-form form-horizontal col-sm-10 col-sm-offset-1" role="form">
                    <input type="hidden" id="fuelMonthLimitIdCar" value="<?=$carInfo["id_car"]?>">
                    <div class="form-group">
                        <label for="fuelMonthLimit" class="control-label">Лимит</label>
                        <input type="text" class="form-control" id="fuelMonthLimit" placeholder="Лимит расхода топлива">
                    </div>
                    <div class="form-group">
                        <label for="fuelMonthLimitDate" class="control-label">На дату</label>
                        <div class="input-group date">
                            <input type="text" class="form-control date" value="<?=date('d.m.Y')?>" id="fuelMonthLimitDate" placeholder="Дата начала действия лимита расхода топлива">
                            <div class="input-group-addon select-date-button">
                                <span class="glyphicon glyphicon-th"></span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="save-fuel-month-limit">Сохранить</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
</body>
</html>
