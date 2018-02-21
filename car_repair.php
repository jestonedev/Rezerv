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
    <link rel="stylesheet" href="css/cars.css">
    <script src="scripts/jquery-1.11.2.min.js"></script>
    <script src="scripts/bootstrap.min.js"></script>
    <script src="scripts/cars.js"></script>
    <script src="scripts/repair.js"></script>
    <script src="scripts/jquery.fileDownload.js" type='text/javascript'></script>
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
    $car_id = intval($_GET['id_car']);
    $carClass = new CarsClass();
    $carInfo = $carClass->GetCarInfo($car_id);
    ?>
    <ol class="breadcrumb cars-breadcrumb">
        <li><a href="car_repair_card.php?id_car=<?= $carInfo["id_car"] ?>">
                Эксплуатационно-ремонтная карта автомобиля <?= $carInfo["number"] ?></a>
        </li>
        <li class="active">Проведение ремонта автомобиля <?= $carInfo["number"] ?></li>
    </ol>
    <div class="cars__page-header page-header text-center">
        <h1>
            <small>Акты выполненных работ по ремнту автомобиля <?= $carInfo["number"] ?></small>
        </h1>
    </div>
    <?php
    $page = 1;
    if (isset($_GET['page']) && intval($_GET['page']) != 0) {
        $page = intval($_GET['page']);
    }
    $acts = $carClass->GetCarActs($car_id, $page);
    $actsPageCount = $carClass->GetCarActsPageCount($car_id);
    ?>
    <div class="col-md-10 col-md-offset-1">
        <table class="table table-striped table-hover car__table">
            <thead>
            <tr>
                <th>№</th>
                <th>Период ремонта</th>
                <th>Вид работ</th>
                <th>Статус</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($acts as $act) {
                ?>
                <tr>
                    <td><?= $act["number"] ?></td>
                    <td class="repair-acts__repair-period">
                        <?= ($act["repair_date"] != null ?
                            ($act["repair_date"] != null && $act["wait_date"] != null  ? "Ремонт: " : "").
                            $act["repair_date"] : "") ?>
                        <?= ($act["repair_date"] != null && $act["wait_date"] != null ? "<br>" : "") ?>
                        <?= ($act["wait_date"] != null ? "Простой: ".$act["wait_date"] : "") ?>
                    </td>
                    <td><?= $act["work_performed"] ?></td>
                    <td><?= $act["state"] ?></td>
                    <td class="repair-act__manage-buttons">
                        <div>
                            <a title="Детальная информация"
                               href="repair_act_details.php?id_repair=<?= $act["id_repair"] ?>"
                               class="btn btn-default <?=$act["deleted"] ? "" : "button--padding" ?>">
                                <span class="glyphicon glyphicon-eye-open"></span>
                            </a>
                            <?
                            if ($act["deleted"] == 0) {
                                ?>
                                <a title="Редактировать акт"
                                   href="repair_act_edit.php?id_repair=<?= $act["id_repair"] ?>"
                                   class="btn btn-default button--padding">
                                    <span class="glyphicon glyphicon-pencil"></span>
                                </a>
                                <button title="Удалить акт"
                                        data-id-repair="<?= $act["id_repair"] ?>"
                                        data-repair-act-number="<?= $act["number"] ?>"
                                        class="btn btn-default repair-act-delete-btn">
                                    <span class="glyphicon glyphicon-trash"></span>
                                </button>
                                <?
                            }
                            ?>
                            <button title="Сформировать акт"
                                    data-id-repair="<?= $act["id_repair"] ?>"
                                    class="btn btn-default repair-act-generate-btn">
                                <span class="glyphicon glyphicon-download-alt"></span>
                            </button>
                        </div>
                    </td>
                </tr>
                <?
            }
            ?>
            </tbody>
        </table>
        <ul class="pagination">
            <li class="<?= ($page == 1 ? "disabled" : "") ?>">
                <a href="<?= ($page == 1 ? "#" : "car_repair.php?id_car=" . $car_id . "&page=" . ($page - 1) . "\"") ?>">&laquo;</a>
            </li>
            <?php
            for ($i = 1; $i <= $actsPageCount; $i++) {
                $activeClass = "";
                if ($i == $page) {
                    $activeClass = "active";
                }
                ?>
                <li class="<?= $activeClass ?>"><a
                        href="car_repair.php?id_car=<?= $car_id ?>&page=<?= $i ?>"><?= $i ?></a></li>
                <?
            }
            ?>
            <li class="<?= ($page >= $actsPageCount ? "disabled" : "") ?>">
                <a href="<?= ($page >= $actsPageCount ? "#" :
                    "car_repair.php?id_car=" . $car_id . "&page=" . ($page + 1) . "\"") ?>">&raquo;</a>
            </li>
        </ul>
        <div class="row text-center">
            <a href="repair_act_add.php?id_car=<?= $car_id ?>" class="btn btn-success repair-act__add-button">Добавить акт</a>
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
<div class="modal fade" id="delete-repair-act" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Удалить акт №<span class="repair-act-number">0</span>?</h4>
            </div>
            <div class="modal-body">
                Вы действительно хотите удалить путевой лист №<span class="repair-act-number">0</span>?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="delete-repair-act-success">Да</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Нет</button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

</body>
</html>