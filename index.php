<?php
header("Cache-Control: no-cache, must-revalidate");
?>
<html>
    <head>
        <meta charset="utf-8">
        <title>Заявки</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
        <link href="css/redmond/jquery-ui-1.11.2.min.css" rel="stylesheet">
        <link href="css/redmond/jquery-ui-1.11.2.structure.min.css" rel="stylesheet">
        <link href="css/redmond/jquery-ui-1.11.2.theme.min.css" rel="stylesheet">
        <link href="css/jquery.dataTables_1.10.4_themeroller.css" rel="stylesheet">
        <link href='css/jquery-ui-timepicker-addon.css' rel='stylesheet' type='text/css' />
        <link href="css/TableTools_JUI.css" rel="stylesheet">
        <link href="css/index.css" rel="stylesheet">
        <link href='css/fullcalendar.css' rel='stylesheet' type='text/css'  />
        <link href='css/fullcalendar.print.css' rel='stylesheet' type='text/css'  media='print' />
		<link rel="shortcut icon" href="favicon.png" type="image/png">

        <script src="scripts/jquery-1.11.2.min.js" type='text/javascript'></script>
        <script src="scripts/jquery-ui-1.11.2.min.js" type='text/javascript'></script>
        <script src="scripts/jquery.inputmask-3.1.0.js" type="text/javascript"></script>
        <script src="scripts/jquery.dataTables_1.10.4.min.js" type="text/javascript"></script>
        <script src='scripts/jquery-ui-timepicker-addon.js' type='text/javascript' ></script>
        <script src="scripts/ZeroClipboard.js" type="text/javascript"></script>
        <script src="scripts/TableTools.js" type="text/javascript"></script>
		<script src="scripts/json2.js" type='text/javascript'></script>
        <script src="scripts/jquery.fileDownload.js" type='text/javascript'></script>
        <script src="scripts/moment.min.js" type="text/javascript"></script>
        <script src='scripts/fullcalendar.min.js' type='text/javascript' ></script>
        <script src='scripts/highcharts.js' type='text/javascript' ></script>
        <script src='scripts/xrange.js' type='text/javascript' ></script>
    </head>
    <body>
	<?php
    if(!in_array(mb_strtoupper($_SERVER['REMOTE_USER']),array('PWR\KONAS','PWR\IGNVV'))){
		define("DEVELOP_MODE","ON");
	}
	
	if(defined("DEVELOP_MODE")){
		//die("Сервис временно недоступен!!!");
	}
	?>
    <?php

    include_once "inc/auth.php";
    if (Auth::hasPrivilege(AUTH_READ_DATA)) { ?>
        <script src="scripts/helper.js" type="text/javascript"></script>
        <script src="scripts/index.js" type="text/javascript"></script>
        <script src="scripts/requests.js" type="text/javascript"></script>
        <script src="scripts/mileages.js" type="text/javascript"></script>
        <script src="scripts/reports.js" type="text/javascript"></script>
        <script src="scripts/gantt.js" type="text/javascript"></script>
        <script src="scripts/calendar.js" type="text/javascript"></script>
        <script src="scripts/cars.js" type="text/javascript"></script>

    <table id="struct_table">
    <tr>
        <td colspan=2 id="header">Заявки</td>
    </tr>
     <tr>
    <td id="left_menu">
        <div id="requests_group">
            <input type="radio" name="rq" id="btnReports"><label id="rplb" for="btnReports">Отчеты</label>
            <div id="left_menu_requests_title">Главное меню:</div>
            <input type="radio" name="rq" id="btnTransportRequests"><label id="rqlb1" for="btnTransportRequests">Заявки на транспорт</label>
            <input type="radio" name="rq" id="btnGreatHallRequests"><label id="rqlb2" for="btnGreatHallRequests">Заявки на конф.-зал</label>
            <input type="radio" name="rq" id="btnSmallHallRequests"><label id="rqlb3" for="btnSmallHallRequests">Заявки на зал думы</label>

            <input type="radio" name="rq" id="btnCarsInfo"><label id="rqlb4" for="btnCarsInfo">Пробег транспорта</label>
        </div>
        <button id="btnCreateRequest">Подать заявку</button>
        <button id="btnShowGantt">Диаграмма Ганта</button>
        <button id="btnShowCalendar">Календарь заявок</button>
        <a id="cars" href="cars.php">Транспорт</a>
        <a class="user-doc_cell" href="/doc/user_manual.odt">Руководство пользователя</a>
    </td>
    <td id="body">
            <table cellpadding="0" cellspacing="0" class="display" id="example">
            <thead>
                <tr>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="2" class="dataTables_empty"></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                </tr>
            </tfoot>
            </table>
    </td>
    </tr>
    <tr>
        <td></td>
        <td id="footer">
            <?php
                include_once('inc/ldap.php');
                $ldap = new LDAP();
                echo $ldap->Macro("Пользователь: @user@ <br>Департамент: @department@");
            ?>
        </td>
        </tr>
    </table>
    <div id='calendar'></div>                                       <!--Форма календаря-->
    <div id='gantt'><div id="gantt-diagram"></div></div>            <!--Форма диаграммы Ганта-->
    <div id='calendar_details'></div>                               <!--Подробности о заявке на календаре-->
    <div id='select_car_form'></div>                                <!--Форма диалога выбора автомобиля-->
    <div id='reportSettings'>                                       <!--Форма настройки отчета-->
        <table>
            <tr>
                <td class="col1">Тип отчета: </td>
                <td class="col2">
                    <select id="report_id" name="report_id">
                    </select>
                </td>
            </tr>
            <tr id="department_row">
                <td>Подразделение: </td>
                <td id="departments"></td>
            </tr>
            <tr><td colspan="2"><hr style="color: gray; height: 1pt"" ></td></tr>
            <tr id="date_row">
                <td>Поиск: </td>
                <td id="filter_criteria">
                    <select id="date_id" name="date_id">
                        <option value="1">по дате поездки/начала мероприятия</option>
                        <option value="2">по дате подачи заявки</option>
                    </select>
                </td>
            </tr>
            <tr id="car_row"><td>Транспорт: </td>
                <td id="car">
                </td>
            </tr>
            <tr id="fuel_row"><td>Марка ГСМ: </td>
                <td id="fuel">
                </td>
            </tr>
            <tr>
                <td>Начальная дата:</td><td><input id="start_date" type="text"></td>
            </tr>
            <tr>
                <td>Конечная дата:</td><td><input id="end_date" type="text"></td>
            </tr>
        </table>
        <div id="error_reportSettings"></div>
    </div>
    <div id='calendarSettings'></div>                               <!--Форма настройки календаря-->
    <div id='ganttSettings'></div>                                  <!--Форма настройки диаграммы Ганта-->
    <div id='mileagesEditor'>                                       <!--Форма изменения информации о пробеге-->
        <table>
            <tr>
                <td class="col1">Пробег:</td><td class="col2"><input id="mileage_value" type="text"></td>
            </tr>
            <tr>
                <td>Дата пробега:</td><td><input id="mileage_date" type="text"></td>
            </tr>
            <tr>
                <td>Вид пробега:</td><td>
                <select id="mileage_type" name="mileage_type">
                    <option value="0">Фактический пробег</option>
                    <option value="2">Фактический пробег (командировка)</option>
                    <option value="1">Лимит по пробегу</option>
                </select>
                </td>
            </tr>
            <tr>
                <td>Руководитель:</td>
                <td id="car_chief_wrapper">
                    <select id="car_chief" name="car_chief">
                    </select>
                </td>
            </tr>
        </table>
        <div id="error_mileagesEditor"></div>
    </div>
    <?php } else { ?>
        <h1 id="permission_denied">У вас нет прав на просмотр содержимого этого сайта</h1>
        <div>Чтобы получить необходимые права, оформите заявку в Центр информационного и материально-технического обеспечения города Братск</div>
        <hr>
    <?php
        include_once('inc/ldap.php');
        $ldap = new LDAP();
        echo $ldap->Macro("Пользователь: @user@ <br>Департамент: @department@");
    } ?>

    </body>
</html>

