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
        <li class="active">Изменение путевого листа №<?=$waybillInfo["waybill_number"]?> автомобиля <?=$waybillInfo["car_number"]?></li>
    </ol>
    <div class="cars__page-header page-header text-center">
        <h1><small>Изменение путевого листа №<?=$waybillInfo["waybill_number"]?> автомобиля <?=$waybillInfo["car_number"]?></small></h1>
    </div>
    <div class="alert alert-danger text-center col-sm-10 col-sm-offset-1" id="waybill-error" style="display: none">Ошибка</div>
    <div class="col-sm-10 col-sm-offset-1">
        <form class="form-horizontal waybill-edit-form" role="form">
            <div class="form-group">
                <label for="waybillNumber" class="control-label">Номер</label>
                <input type="text" class="form-control" value="<?=$waybillInfo["waybill_number"]?>" id="waybillNumber" placeholder="Номер путевого листа">
            </div>
            <div class="row">
                <div class="col-xs-12 col-md-6 waybills-form-md-right-padding">
                    <div class="form-group">
                        <label for="waybillStartDate" class="control-label">Начало действия<span class="waybill-require-mark">*</span></label>
                        <div class="input-group date">
                            <input type="text" class="form-control date" value="<?=$waybillInfo["start_date"]?>" id="waybillStartDate" placeholder="Начало действия путевого листа">
                            <div class="input-group-addon waybill-select-date-button select-date-button">
                                <span class="glyphicon glyphicon-th"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xs-12 col-md-6 waybills-form-md-left-padding">
                    <div class="form-group">
                        <label for="waybillEndDate" class="control-label">Окончание действия<span class="waybill-require-mark">*</span></label>
                        <div class="input-group date">
                            <input type="text" class="form-control date" value="<?=$waybillInfo["end_date"]?>" id="waybillEndDate" placeholder="Окончание действия путевого листа">
                            <div class="input-group-addon waybill-select-date-button select-date-button">
                                <span class="glyphicon glyphicon-th"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <input type="hidden" id="waybillCar" value="<?=$waybillInfo["id_car"]?>">
            <input type="hidden" id="waybillId" value="<?=$waybillInfo["id_waybill"]?>">
            <div class="form-group">
                <label for="waybillDriver" class="control-label">Водитель<span class="waybill-require-mark">*</span></label>
                <select id="waybillDriver" class="form-control" >
                    <option value="">Выберите водителя</option>
                    <?php
                    $drivers = $carClass->GetDrivers();
                    foreach($drivers as $driver)
                    {
                        $selected = "";
                        if ($driver["id_driver"] == $waybillInfo["id_driver"])
                        {
                            $selected = "selected";
                        }
                        echo "<option $selected value=\"".$driver["id_driver"]."\">".$driver["name"]."</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="waybillDepartment" class="control-label">В распоряжение<span class="waybill-require-mark">*</span></label>
                <select id="waybillDepartment" class="form-control" >
                    <option value="">В распоряжение кого поступает автомобиль</option>
                    <option <?=$waybillInfo["department"] == "Диспетчер" ? "selected" : ""?> value="Диспетчер">Диспетчер</option>
                    <?php
                    include_once "inc/ldap.php";
                    $ldap = new LDAP();
                    $departments = $ldap->getDepartments();
                    foreach($departments as $department => $value)
                    {
                        $selected = "";
                        if ($department == $waybillInfo["department"])
                        {
                            $selected = "selected";
                        }
                        echo '<option '.$selected.' value="'.htmlspecialchars($department).'">'.$department.'</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="row">
                <div class="col-xs-12 col-md-6 waybills-form-md-right-padding">
                    <div class="form-group">
                        <label for="waybillMileagesBefore" class="control-label">Показание спидометра (до выезда)</label>
                        <input type="text" class="form-control" value="<?=$waybillInfo["mileage_before"]?>" id="waybillMileagesBefore" placeholder="Показание спидометра (до выезда)">
                    </div>
                </div>
                <div class="col-xs-12 col-md-6 waybills-form-md-left-padding">
                    <div class="form-group">
                        <label for="waybillMileages" class="control-label">Пробег</label>
                        <input type="text" class="form-control" value="<?=($waybillInfo["mileage_after"] - $waybillInfo["mileage_before"])?>"
                               id="waybillMileages" placeholder="Пробег за время действия путевого листа">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 col-md-6 waybills-form-md-right-padding">
                    <div class="form-group">
                        <label for="waybillFuelBefore" class="control-label">Остаток горючего при выезде</label>
                        <input type="text" class="form-control" value="<?=$waybillInfo["fuel_before"]?>" id="waybillFuelBefore" placeholder="Остаток горючего при выезде">
                    </div>
                </div>
                <div class="col-xs-12 col-md-6 waybills-form-md-left-padding">
                    <div class="form-group">
                        <label for="waybillGivenFuel" class="control-label">Выдано горючего</label>
                        <input type="text" class="form-control" value="<?=$waybillInfo["given_fuel"]?>" id="waybillGivenFuel" placeholder="Выдано горючего">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="waybillFuelType" class="control-label">Марка горючего</label>
                <select id="waybillFuelType" class="form-control" >
                    <option value="">Выберите марку горючего</option>
                    <?php
                    $fuelTypes = $carClass->GetFuelTypes();
                    foreach($fuelTypes as $fuelType)
                    {
                        $selected = "";
                        if ($fuelType["id_fuel_type"] == $waybillInfo["id_fuel_type"])
                        {
                            $selected = "selected";
                        }
                        echo "<option $selected value=\"".$fuelType["id_fuel_type"]."\">".$fuelType["fuel_type"]."</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="panel panel-default waybill-ways">
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
                        foreach($waybillInfo["ways"] as $way)
                        {
                            $wayFrom = explode(" - ", $way["way"])[0];
                            $wayTo = explode(" - ", $way["way"])[1];
                            $wayTimeFrom = substr($way["start_time"], 0, 5);
                            $wayTimeTo = substr($way["end_time"], 0, 5);
                            $wayDistance = $way["distance"];
                            include 'inc/waybills_way_template.php';
                        }
                    ?>
                    </tbody>
                </table>
            </div>
            <div class="text-center waybill-control-buttons">
                <button class="btn btn-success" id="waybill-edit-button">Сохранить</button>
                <a href="car_waybills.php?id_car=<?=$waybillInfo["id_car"]?>" class="btn btn-danger">Отмена</a>
            </div>
        </form>
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
