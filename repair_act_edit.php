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
        <li class="active">Изменение акта выполненных работ автомобиля <?=$repairActInfo["number"]?></li>
    </ol>
    <div class="cars__page-header page-header text-center">
        <h1><small>Изменение акта выполненных работ автомобиля <?=$repairActInfo["number"]?></small></h1>
    </div>
    <div class="alert alert-danger text-center col-sm-10 col-sm-offset-1" id="repair-act-error" style="display: none">Ошибка</div>
    <div class="col-sm-10 col-sm-offset-1">
        <form class="form-horizontal repair-act-edit-form" role="form">
            <div class="row">

                <div class="col-xs-12 col-md-6 repair-acts-form-md-right-padding">
                    <div class="form-group">
                        <label for="repairActNumber" class="control-label">Номер</label>
                        <input type="text" class="form-control" value="<?=$repairActInfo["repair_act_number"]?>" id="repairActNumber" placeholder="Номер акта выполненных работ">
                    </div>
                </div>

                <div class="col-xs-12 col-md-6 repair-acts-form-md-left-padding">
                    <div class="form-group">
                        <label for="actDate" class="control-label">Дата формирования<span class="repair-act-require-mark">*</span></label>
                        <div class="input-group date">
                            <input type="text" class="form-control date" value="<?=$repairActInfo["act_date"]?>" id="actDate" placeholder="Дата формирования акта">
                            <div class="input-group-addon repair-act-select-date-button select-date-button">
                                <span class="glyphicon glyphicon-th"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12 col-md-6 repair-acts-form-md-right-padding">
                    <div class="form-group">
                        <label for="repairStartDate" class="control-label">Начало ремонта</label>
                        <div class="row">
                            <div class="input-group date col-md-6" id="repair-start-date-wrapper">
                                <input type="text" class="form-control date" value="<?=$repairActInfo["repair_start_date"]?>" id="repairStartDate" placeholder="Дата начала ремонта">
                                <div class="input-group-addon repair-act-select-date-button select-date-button">
                                    <span class="glyphicon glyphicon-th"></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" value="<?=$repairActInfo["repair_start_time"]?>" id="repairStartTime" placeholder="Время начала ремонта">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xs-12 col-md-6 repair-acts-form-md-left-padding">
                    <div class="form-group">
                        <label for="repairEndDate" class="control-label">Окончание ремонта</label>
                        <div class="row">
                            <div class="input-group date col-md-6" id="repair-end-date-wrapper">
                                <input type="text" class="form-control date" value="<?=$repairActInfo["repair_end_date"]?>" id="repairEndDate" placeholder="Дата окончания ремонта">
                                <div class="input-group-addon repair-act-select-date-button select-date-button">
                                    <span class="glyphicon glyphicon-th"></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" value="<?=$repairActInfo["repair_end_time"]?>" id="repairEndTime" placeholder="Время окончания ремонта">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 col-md-6 repair-acts-form-md-right-padding">
                    <div class="form-group">
                        <label for="waitStartDate" class="control-label">Начало ожидания</label>
                        <div class="row">
                            <div class="input-group date col-md-6" id="wait-end-date-wrapper">
                                <input type="text" class="form-control date" value="<?=$repairActInfo["wait_start_date"]?>" id="waitStartDate" placeholder="Дата начала ожидания">
                                <div class="input-group-addon repair-act-select-date-button select-date-button">
                                    <span class="glyphicon glyphicon-th"></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" value="<?=$repairActInfo["wait_start_time"]?>" id="waitStartTime" placeholder="Время начала ожидания">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xs-12 col-md-6 repair-acts-form-md-left-padding">
                    <div class="form-group">
                        <label for="waitEndDate" class="control-label">Окончание ожидания</label>
                        <div class="row">
                            <div class="input-group date col-md-6" id="wait-end-date-wrapper">
                                <input type="text" class="form-control date" value="<?=$repairActInfo["wait_end_date"]?>" id="waitEndDate" placeholder="Дата окончания ожидания">
                                <div class="input-group-addon repair-act-select-date-button select-date-button">
                                    <span class="glyphicon glyphicon-th"></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" value="<?=$repairActInfo["wait_end_time"]?>" id="waitEndTime" placeholder="Время окончания ожидания">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <input type="hidden" id="repairActCar" value="<?=$repairActInfo["id_car"]?>">
            <input type="hidden" id="repairActId" value="<?=$repairActInfo["id_repair"]?>">
            <div class="row">
                <div class="col-xs-12 col-md-6 repair-acts-form-md-right-padding">
                    <div class="form-group">
                        <label for="repairActRespondent" class="control-label">Ответственный
                            <span class="repair-act-require-mark">*</span></label>
                        <select id="repairActRespondent" class="form-control" >
                            <option value="">Выберите ответственного</option>
                            <?php
                            $respondents = $carClass->GetRespondents();
                            foreach($respondents as $respondent)
                            {
                                $selected = "";
                                if ($respondent["id_respondent"] == $repairActInfo["id_respondent"])
                                {
                                    $selected = "selected";
                                }
                                echo "<option $selected value=\"".$respondent["id_respondent"]."\">".$respondent["name"]."</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-xs-12 col-md-6 repair-acts-form-md-left-padding">
                    <div class="form-group">
                        <label for="repairActDriver" class="control-label">Водитель<span class="repair-act-require-mark">*</span></label>
                        <select id="repairActDriver" class="form-control" >
                            <option value="">Выберите водителя</option>
                            <?php
                            $drivers = $carClass->GetDrivers();
                            foreach($drivers as $driver)
                            {
                                $selected = "";
                                if ($driver["id_driver"] == $repairActInfo["id_driver"])
                                {
                                    $selected = "selected";
                                }
                                echo "<option $selected value=\"".$driver["id_driver"]."\">".$driver["name"]."</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12 col-md-6 repair-acts-form-md-right-padding">
                    <div class="form-group">
                        <label for="repairActMechanic" class="control-label">Механик/исполнитель
                            <span class="repair-act-require-mark">*</span></label>
                        <select id="repairActMechanic" class="form-control" >
                            <option value="">Выберите механика/исполнителя</option>
                            <?php
                            $mechanics = $carClass->GetMechanics();
                            foreach($mechanics as $mechanic)
                            {
                                $selected = "";
                                if ($mechanic["id_mechanic"] == $repairActInfo["id_mechanic"])
                                {
                                    $selected = "selected";
                                }
                                echo "<option $selected value=\"".$mechanic["id_mechanic"]."\">".$mechanic["name"]."</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-xs-12 col-md-6 repair-acts-form-md-left-padding">
                    <div class="form-group">
                        <label for="repairActMileages" class="control-label">Показание одометра</label>
                        <input type="text" class="form-control" value="<?=$repairActInfo["odometer"]?>" id="repairActMileages" placeholder="Показание одометра">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="repairActReasonForRepairs" class="control-label">Причина ремонта</label>
                <input type="text" class="form-control" value="<?=$repairActInfo["reason_for_repairs"]?>" id="repairActReasonForRepairs" placeholder="Причина ремонта">
            </div>
            <div class="form-group">
                <label for="repairActReasonForRepairs" class="control-label">Выполненные работы</label>
                <textarea class="form-control" id="repairActWorkPerformed" placeholder="Выполненные работы" rows="4"><?=$repairActInfo["work_performed"]?></textarea>
            </div>
            <div class="panel panel-default repair-act__materials">
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
                    foreach($repairActInfo["materials"] as $material)
                    {
                        $materialName = $material["material_name"];
                        $materialCount = $material["material_count"];
                        $materialDescription = $material["material_description"];
                        include 'inc/repair_act_material_template.php';
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            <div class="text-center repair-act-control-buttons">
                <button class="btn btn-success" id="repair-act-edit-button">Сохранить</button>
                <a href="car_repair.php?id_car=<?=$repairActInfo["id_car"]?>" class="btn btn-danger">Отмена</a>
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
