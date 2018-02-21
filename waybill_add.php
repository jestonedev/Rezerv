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
    if (!isset($_GET['id_car']) || intval($_GET['id_car']) == 0) {
        ?>
        <div class="alert alert-danger text-center cars__permissions_denied">Неверно указан идентификатор автомобиля
        </div>
        <?php
        die();
    }
    include_once "inc/CarsClass.php";
    include_once "inc/request.php";
    include_once "inc/WaybillsClass.php";
    $car_id = intval($_GET['id_car']);
    $carClass = new CarsClass();
    $carInfo = $carClass->GetCarInfo($car_id);
    $waybillClass = new WaybillsClass();
    $waybillAutocompleteInfo = $waybillClass->AutoCompleteDetails($car_id);
    ?>
    <ol class="breadcrumb cars-breadcrumb">
        <li><a href="car_repair_card.php?id_car=<?=$carInfo["id_car"]?>">
                Эксплуатационно-ремонтная карта автомобиля <?=$carInfo["number"]?></a>
        </li>
        <li><a href="car_waybills.php?id_car=<?=$carInfo["id_car"]?>">Путевые листы автомобиля <?=$carInfo["number"]?></a></li>
        <li class="active">Добавление путевого листа автомобиля <?=$carInfo["number"]?></li>
    </ol>
    <div class="cars__page-header page-header text-center">
        <h1><small>Добавление путевого листа автомобиля <?=$carInfo["number"]?></small></h1>
    </div>
    <div class="alert alert-danger text-center col-sm-10 col-sm-offset-1" id="waybill-error" style="display: none">Ошибка</div>
    <div class="col-sm-10 col-sm-offset-1">
        <form class="form-horizontal waybill-add-form" role="form">
            <div class="form-group">
                <label for="waybillNumber" class="control-label">Номер</label>
                <input type="text" class="form-control" value="<?=$waybillAutocompleteInfo["waybill_number"]?>" id="waybillNumber" placeholder="Номер путевого листа">
            </div>
            <div class="row">
                <div class="col-xs-12 col-md-6 waybills-form-md-right-padding">
                    <div class="form-group">
                        <label for="waybillStartDate" class="control-label">Начало действия<span class="waybill-require-mark">*</span></label>
                        <div class="input-group date">
                            <input type="text" class="form-control date" value="<?=date('d.m.Y')?>" id="waybillStartDate" placeholder="Начало действия путевого листа">
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
                            <input type="text" class="form-control date" value="<?=date('d.m.Y')?>" id="waybillEndDate" placeholder="Окончание действия путевого листа">
                            <div class="input-group-addon waybill-select-date-button select-date-button">
                                <span class="glyphicon glyphicon-th"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <input type="hidden" id="waybillCar" value="<?=$carInfo["id_car"]?>">
            <div class="form-group">
                <label for="waybillDriver" class="control-label">Водитель<span class="waybill-require-mark">*</span></label>
                <select id="waybillDriver" class="form-control" >
                    <option value="">Выберите водителя</option>
                    <?php
                    $drivers = $carClass->GetDrivers();
                    foreach($drivers as $driver)
                    {
                        $selected = "";
                        if ($driver["id_driver"] == $waybillAutocompleteInfo["id_driver_default"])
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
                    <option <?=$waybillAutocompleteInfo["department_default"] == "Диспетчер" ? "selected" : ""?> value="Диспетчер">Диспетчер</option>
                    <?php
                    include_once "inc/ldap.php";
                    $ldap = new LDAP();
                    $departments = $ldap->getDepartments();
                    foreach($departments as $department => $value)
                    {
                        $selected = "";
                        if ($department == $waybillAutocompleteInfo["department_default"])
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
                        <label for="waybillMileagesBefore" class="control-label">Показание одометра (до выезда)</label>
                        <input type="text" class="form-control" value="<?=$waybillAutocompleteInfo["mileage_after"]?>" id="waybillMileagesBefore" placeholder="Показание одометра (до выезда)">
                    </div>
                </div>
                <div class="col-xs-12 col-md-6 waybills-form-md-left-padding">
                    <div class="form-group">
                        <label for="waybillMileages" class="control-label">Пробег</label>
                        <input type="text" class="form-control" id="waybillMileages" placeholder="Пробег за время действия путевого листа">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 col-md-6 waybills-form-md-right-padding">
                    <div class="form-group">
                        <label for="waybillFuelBefore" class="control-label">Остаток горючего при выезде</label>
                        <input type="text" class="form-control" value="<?=$waybillAutocompleteInfo["fuel_after"]?>" id="waybillFuelBefore" placeholder="Остаток горючего при выезде">
                    </div>
                </div>
                <div class="col-xs-12 col-md-6 waybills-form-md-left-padding">
                    <div class="form-group">
                        <label for="waybillGivenFuel" class="control-label">Выдано горючего</label>
                        <input type="text" class="form-control" id="waybillGivenFuel" placeholder="Выдано горючего">
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
                        if ($fuelType["id_fuel_type"] == $waybillAutocompleteInfo["id_fuel_default"])
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
                    </tbody>
                </table>
            </div>
            <div class="text-center waybill-control-buttons">
                <button class="btn btn-success" id="waybill-add-button">Сохранить</button>
                <a href="car_waybills.php?id_car=<?=$carInfo["id_car"]?>" class="btn btn-danger">Отмена</a>
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
