<?php
header("Cache-Control: no-cache, must-revalidate");
?>

<html>
<head>
    <meta charset="utf-8">
    <title>Транспорт</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <link rel="shortcut icon" href="../favicon.png" type="image/png">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="../css/bootstrap-datepicker3.min.css">
    <link rel="stylesheet" href="../css/cars.css">
    <script src="../scripts/jquery-1.11.2.min.js"></script>
    <script src="../scripts/bootstrap.min.js"></script>
    <script src="../scripts/bootstrap-datepicker.min.js"></script>
    <script src="../scripts/bootstrap-datepicker.ru.min.js"></script>
    <script src="../scripts/cars.js"></script>
    <script src="../scripts/cars_reports.js"></script>
</head>
<body>
<?php
include_once '../inc/auth.php';

if (Auth::hasPrivilege(AUTH_MANAGE_TRANSPORT))
{
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
                <li><a href="../cars.php">Автомобили</a></li>
                <li><a href="../tires.php">Автошины</a></li>
                <li><a href="../accumulator.php">Аккумуляторы</a></li>
                <li><a href="../index.php">Заявки</a></li>
                <li class="active dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Отчеты <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li><a href="fuel_by_month_report.php">Расход топлива за месяц</a></li>
                        <li class="active"><a href="fuel_by_quarter_report.php">Расход топлива за квартал</a></li>
                        <li><a href="fuel_by_period_report.php">Расход топлива за период</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
    <div class="cars__page-header page-header text-center">
        <h1><small>Отчет по расходу топлива за квартал</small></h1>
    </div>
    <form class="form-horizontal" role="form" action="fuel_by_quarter_report.php">
        <div class="form-group">
            <label for="reportFuelByQuarterYear" class="control-label col-md-1 col-md-offset-2 col-sm-1 col-sm-offset-1">Год</label>
            <div class="input-group date col-md-2 col-sm-3">
                <input type="text" name="reportFuelByQuarterYear" class="form-control date" value="<?=
                isset($_GET['reportFuelByQuarterYear']) ? $_GET['reportFuelByQuarterYear'] : date('Y')?>" id="reportFuelByQuarterYear" placeholder="Год формирования отчета">
                <div class="input-group-addon select-date-button">
                    <span class="glyphicon glyphicon-th"></span>
                </div>
            </div>
            <label for="reportFuelByQuarterQuarter" class="control-label col-md-1 col-sm-1">Квартал</label>
            <div class="input-group date col-md-2 col-sm-3">
                <select name="reportFuelByQuarterQuarter" class="form-control">
                    <option <?=isset($_GET['reportFuelByQuarterQuarter']) && $_GET['reportFuelByQuarterQuarter'] == 1 ? "selected" : ""?> value="1">I</option>
                    <option <?=isset($_GET['reportFuelByQuarterQuarter']) && $_GET['reportFuelByQuarterQuarter'] == 2 ? "selected" : ""?> value="2">II</option>
                    <option <?=isset($_GET['reportFuelByQuarterQuarter']) && $_GET['reportFuelByQuarterQuarter'] == 3 ? "selected" : ""?> value="3">III</option>
                    <option <?=isset($_GET['reportFuelByQuarterQuarter']) && $_GET['reportFuelByQuarterQuarter'] == 4 ? "selected" : ""?> value="4">IV</option>
                </select>
            </div>
            <div class="col-md-2 col-sm-2 text-left">
                <button class="btn btn-success" id="report-fuel-by-month__generate">Сформировать</button>
            </div>
        </div>
    </form>
    <?
    if (isset($_GET['reportFuelByQuarterYear']) && isset($_GET['reportFuelByQuarterQuarter']) )
    {
        include_once '../inc/CarReportClass.php';
        $reportClass = new CarReportClass();
        $fuelDataArray = $reportClass->GetFuelByQuarterReportData($_GET['reportFuelByQuarterYear'], $_GET['reportFuelByQuarterQuarter']);
        ?>
        <div class="col-sm-12">
            <table class="table table-bordered table-striped car__table">
                <thead>
                <tr>
                    <th rowspan="3">Автомобиль</th>
                    <th rowspan="3">Норма расхода</th>
                    <th rowspan="3">Остаток на начало квартала</th>
                    <th rowspan="3">Лимит расхода</th>
                    <th colspan="8">Фактический расход за квартал</th>
                    <th rowspan="3">Остаток на конец квартала</th>
                    <th rowspan="3">Отклонение от лимита</th>
                </tr>
                <tr>
                    <th colspan="2">
                        январь
                    </th>
                    <th colspan="2">
                        февраль
                    </th>
                    <th colspan="2">
                        март
                    </th>
                    <th colspan="2">
                        квартал
                    </th>
                </tr>
                <tr>
                    <th>
                        л.
                    </th>
                    <th>
                        км.
                    </th>
                    <th>
                    л.
                    </th>
                    <th>
                        км.
                    </th>
                    <th>
                    л.
                    </th>
                    <th>
                        км.
                    </th>
                    <th>
                    л.
                    </th>
                    <th>
                        км.
                    </th>
                </tr>
                </thead>
                <tbody>
                <?
                if (count($fuelDataArray) == 0)
                {
                    ?>
                    <tr><td colspan="9"><i>Информация остутствует</i></td></tr>
                    <?
                } else
                {
                    foreach($fuelDataArray as $fuelDataItem)
                    {
                        ?>
                        <tr class="<?=$fuelDataItem['order'] == 1 ? "success" : ""?>">
                            <td><?=$fuelDataItem['car']?></td>
                            <td><?=$fuelDataItem['fuel_consumption'] == 'н/а' ?
                                    '<span class="label label-danger">н/а</span>' :
                                    number_format($fuelDataItem['fuel_consumption'], 2, '.', '&nbsp;')?></td>
                            <td><?=$fuelDataItem['fuel_start_quartal'] == 'н/а' ?
                                    '<span class="label label-danger">н/а</span>' :
                                    number_format($fuelDataItem['fuel_start_quartal'], 3, '.', '&nbsp;')?></td>
                            <td><?=$fuelDataItem['quartal_limit'] == 'н/а' ?
                                    '<span class="label label-danger">н/а</span>' :
                                    number_format($fuelDataItem['quartal_limit'], 0, '.', '&nbsp;')?></td>
                            <td><?=$fuelDataItem['factical_first_month_fuel'] == 'н/а' ?
                                    '<span class="label label-danger">н/а</span>' :
                                    number_format($fuelDataItem['factical_first_month_fuel'], 3, '.', '&nbsp;')?></td>
                            <td><?=$fuelDataItem['factical_first_month_mileages'] == 'н/а' ?
                                    '<span class="label label-danger">н/а</span>' :
                                    number_format($fuelDataItem['factical_first_month_mileages'], 0, '.', '&nbsp;')?></td>
                            <td><?=$fuelDataItem['factical_second_month_fuel'] == 'н/а' ?
                                    '<span class="label label-danger">н/а</span>' :
                                    number_format($fuelDataItem['factical_second_month_fuel'], 3, '.', '&nbsp;')?></td>
                            <td><?=$fuelDataItem['factical_second_month_mileages'] == 'н/а' ?
                                    '<span class="label label-danger">н/а</span>' :
                                    number_format($fuelDataItem['factical_second_month_mileages'], 0, '.', '&nbsp;')?></td>
                            <td><?=$fuelDataItem['factical_third_month_fuel'] == 'н/а' ?
                                    '<span class="label label-danger">н/а</span>' :
                                    number_format($fuelDataItem['factical_third_month_fuel'], 3, '.', '&nbsp;')?></td>
                            <td><?=$fuelDataItem['factical_third_month_mileages'] == 'н/а' ?
                                    '<span class="label label-danger">н/а</span>' :
                                    number_format($fuelDataItem['factical_third_month_mileages'], 0, '.', '&nbsp;')?></td>
                            <td><?=$fuelDataItem['factical_quartal_fuel'] == 'н/а' ?
                                    '<span class="label label-danger">н/а</span>' :
                                    number_format($fuelDataItem['factical_quartal_fuel'], 3, '.', '&nbsp;')?></td>
                            <td><?=$fuelDataItem['factical_quartal_mileages'] == 'н/а' ?
                                    '<span class="label label-danger">н/а</span>' :
                                    number_format($fuelDataItem['factical_quartal_mileages'], 0, '.', '&nbsp;')?></td>
                            <td><?=$fuelDataItem['fuel_end_quartal'] == 'н/а' ?
                                    '<span class="label label-danger">н/а</span>' :
                                    number_format($fuelDataItem['fuel_end_quartal'], 3, '.', '&nbsp;')?></td>
                            <td><?=$fuelDataItem['deviation_fuel'] == 'н/а' ?
                                    '<span class="label label-danger">н/а</span>' :
                                    number_format($fuelDataItem['deviation_fuel'], 3, '.', '&nbsp;')?></td>
                        </tr>
                        <?
                    }
                }
                ?>
                </tbody>
            </table>
        </div>
        <?
    } else {
        ?>
        <div class="alert alert-info text-center col-md-8 col-md-offset-2 col-sm-10 col-sm-offset-1 car-report-info">Выберите год и квартал и нажмите
            кнопку "Сформировать"
        </div>
        <?
    }
} else {
    ?>
    <div class="alert alert-danger text-center cars__permissions_denied">У вас нет прав на просмотр и редактирование информации в этом разделе</div>
    <?php
}
?>
</body>
</html>