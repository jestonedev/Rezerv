<?php
header("Cache-Control: no-cache, must-revalidate");
?>

<html>
<head>
    <meta charset="utf-8">
    <title>Транспорт</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="css/cars.css">
    <script src="scripts/jquery-1.11.2.min.js"></script>
    <script src="scripts/bootstrap.min.js"></script>
    <script src="scripts/cars.js"></script>
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
                <dt>Текущий пробег</dt>
                <dd><?=$carInfo["current_mileages"]?></dd>
            </dl>
        </div>
        <div class="col-md-6 col-xs-12">
            <dl>
                <dt>Текущая норма расхода</dt>
                <dd><?=$carInfo["current_fuel_consumption"]?>
                    <button class="btn btn-info btn-xs cars__change-norm-button">Изменить</button></dd>
            </dl>
        </div>
    </div>
    <div class="row cars-row text-center">
        <div class="col-md-6 col-xs-12">
            <dl>
                <dt>Установленный шинокомплект</dt>
                <dd>Nokian 195/65/R15<button class="btn btn-info btn-xs cars__change-norm-button">Смена</button></dd>
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
                <dd>AkTEX 6CT-70<button class="btn btn-info btn-xs cars__change-norm-button">Обслуживание</button></dd>
            </dl>
        </div>
        <div class="col-md-6 col-xs-12">
            <dl>
                <dt>Пробег с момента установки</dt>
                <dd>9873</dd>
            </dl>
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
</body>
</html>