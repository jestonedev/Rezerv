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
                    <li class="active"><a href="#">Автомобили</a></li>
                    <li><a href="tires.php">Автошины</a></li>
                    <li><a href="accumulator.php">Аккумуляторы</a></li>
                    <li><a href="index.php">Заявки</a></li>
                </ul>
            </div>
        </nav>
        <?php
        include_once 'inc/CarsClass.php';
        $carsClass = new CarsClass();
        $cars = $carsClass->GetCars();
        ?>
        <div class="col-md-10 col-md-offset-1">
        <table class="table table-striped table-hover car__table">
            <thead>
            <tr>
                <th>№</th>
                <th>Номер</th>
                <th>Модель</th>
                <th>Тип</th>
                <th>Принадлежность</th>
                <th>Статус</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($cars as $car) {
                echo "<tr>";
                echo "<td>".$car["id"]."</td>";
                echo "<td>".$car["number"]."</td>";
                echo "<td>".$car["model"]."</td>";
                echo "<td>".$car["type"]."</td>";
                echo "<td>".$car["department_default"]."</td>";
                echo "<td>".$car["state"]."</td>";
                echo "<td><a title=\"Эксплуатационно-ремонтная карта\" href=\"car_repair_card.php?id_car=".$car["id"]."\" class=\"btn btn-default car__use-repair-card\">";
                echo "<span class=\"glyphicon glyphicon-wrench\"></span>";
                echo "</a></td>";
                echo "</tr>";
            }
            ?>
            </tbody>
        </table>
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