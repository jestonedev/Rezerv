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
                        <li class="active"><a href="fuel_by_month_report.php">Расход топлива за месяц</a></li>
                        <li><a href="fuel_by_quarter_report.php">Расход топлива за квартал</a></li>
                        <li><a href="fuel_by_period_report.php">Расход топлива за период</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
    <div class="cars__page-header page-header text-center">
        <h1><small>Отчет по расходу топлива за месяц</small></h1>
    </div>
    <form class="form-horizontal" role="form" action="fuel_by_month_report.php">
        <div class="form-group">
            <label for="reportFuelByMonthDate" class="control-label col-md-1 col-md-offset-3 col-sm-2 col-sm-offset-1">Месяц</label>
            <div class="input-group date col-md-3 col-sm-5">
                <input type="text" name="reportFuelByMonthDate" class="form-control date" value="<?=
                isset($_GET['reportFuelByMonthDate']) ? $_GET['reportFuelByMonthDate'] : date('m.Y')?>" id="reportFuelByMonthDate" placeholder="Месяц формирования отчета">
                <div class="input-group-addon select-date-button">
                    <span class="glyphicon glyphicon-th"></span>
                </div>
            </div>
            <div class="col-md-2 col-sm-2 text-left">
                <button class="btn btn-success" id="report-fuel-by-month__generate">Сформировать</button>
            </div>
        </div>
    </form>
    <?
        if (isset($_GET['reportFuelByMonthDate']))
        {
            include_once '../inc/CarReportClass.php';
            $reportClass = new CarReportClass();
            $fuelDataArray = $reportClass->GetFuelByMonthReportData($_GET['reportFuelByMonthDate']);
            ?>
            <div class="col-sm-12">
                <table class="table table-striped table-bordered car__table">
                    <thead>
                        <tr>
                            <th rowspan="2">Автомобиль</th>
                            <th rowspan="2">Норма расхода</th>
                            <th rowspan="2">Остаток на начало месяца</th>
                            <th colspan="2">Лимит расхода</th>
                            <th colspan="2">Фактический расход</th>
                            <th rowspan="2">Остаток на конец месяца</th>
                            <th rowspan="2">Отклонение от лимита</th>
                        </tr>
                        <tr>
                            <th>квартал</th>
                            <th>месяц</th>
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
                                        <td><?=$fuelDataItem['fuel_start_month'] == 'н/а' ?
                                                '<span class="label label-danger">н/а</span>' :
                                                number_format($fuelDataItem['fuel_start_month'], 3, '.', '&nbsp;')?></td>
                                        <td><?=$fuelDataItem['quartal_limit'] == 'н/а' ?
                                                '<span class="label label-danger">н/а</span>' :
                                                number_format($fuelDataItem['quartal_limit'], 0, '.', '&nbsp;')?></td>
                                        <td><?=$fuelDataItem['month_limit'] == 'н/а' ?
                                                '<span class="label label-danger">н/а</span>' :
                                                number_format($fuelDataItem['month_limit'], 0, '.', '&nbsp;')?></td>
                                        <td><?=$fuelDataItem['factical_fuel'] == 'н/а' ?
                                                '<span class="label label-danger">н/а</span>' :
                                                number_format($fuelDataItem['factical_fuel'], 3, '.', '&nbsp;')?></td>
                                        <td><?=$fuelDataItem['factical_mileages'] == 'н/а' ?
                                                '<span class="label label-danger">н/а</span>' :
                                                number_format($fuelDataItem['factical_mileages'], 0, '.', '&nbsp;')?></td>
                                        <td><?=$fuelDataItem['fuel_end_month'] == 'н/а' ?
                                                '<span class="label label-danger">н/а</span>' :
                                                number_format($fuelDataItem['fuel_end_month'], 3, '.', '&nbsp;')?></td>
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
            <div class="alert alert-info text-center col-md-8 col-md-offset-2 col-sm-10 col-sm-offset-1 car-report-info">Выберите месяц и нажмите
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