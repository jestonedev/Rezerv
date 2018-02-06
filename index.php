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
        <script src="scripts/index.js" type="text/javascript"></script>
        <script src="scripts/requests.js" type="text/javascript"></script>
        <script src="scripts/waybills.js" type="text/javascript"></script>
        <script src="scripts/mileages.js" type="text/javascript"></script>
        <script src="scripts/repair_acts.js" type="text/javascript"></script>
        <script src="scripts/reports.js" type="text/javascript"></script>
        <script src="scripts/gantt.js" type="text/javascript"></script>
        <script src="scripts/calendar.js" type="text/javascript"></script>

    <table id="struct_table">
    <tr>
        <td colspan=2 id="header">Заявки</td>
    </tr>
     <tr>
    <td id="left_menu">
        <div id="requests_group">
            <input type="radio" name="rq" id="btnReports"><label id="rplb" for="btnReports">Отчеты</label>
            <div id="left_menu_requests_title">Главное меню:</div>
            <input type="radio" name="rq" id="btnTransportRequests"><label id="rqlb1" for="btnTransportRequests">Транспорт</label>
            <input type="radio" name="rq" id="btnGreatHallRequests"><label id="rqlb2" for="btnGreatHallRequests">Конференц-зал</label>
            <input type="radio" name="rq" id="btnSmallHallRequests"><label id="rqlb3" for="btnSmallHallRequests">Зал заседания думы</label>
            <input type="radio" name="rq" id="btnCarsInfo"><label id="rqlb4" for="btnCarsInfo">Пробег транспорта</label>
            <input type="radio" name="rq" id="btnRepairActs"><label id="rqlb5" for="btnRepairActs">Акты обслуживания</label>
            <input type="radio" name="rq" id="btnWaybills"><label id="rqlb6" for="btnWaybills">Путевые листы</label>
        </div>
        <button id="btnCreateRequest">Подать заявку</button>
        <button id="btnCreateAct">Создать акт</button>
        <button id="btnCreateWaybill">Создать путевой лист</button>
        <button id="btnShowGantt">Диаграмма Ганта</button>
        <button id="btnShowCalendar">Календарь заявок</button>
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
    <div id='ganttSettings'></div>                               <!--Форма настройки диаграммы Ганта-->
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
    <div id='act_create_form'>                                       <!--Форма подачи акта выполненных работ-->
        <table>
            <tr>
                <td>Номер акта</td>
                <td colspan="2">
                    <input id="act_number" type="text">
                </td>
            </tr>
            <tr>
                <td>Дата формирования акта<span class="required_field_mark">*</span></td>
                <td colspan="2"><input id="act_date" type="text" colspan="2"></td>
            </tr>
            <tr>
                <td class="col1">Ответственный<span class="required_field_mark">*</span></td>
                <td class="col2" id="act_respondent" name="act_respondent" colspan="2">
                </td>
            </tr>
            <tr>
                <td>Транспортное средство<span class="required_field_mark">*</span></td>
                <td id="act_car" name="act_car" colspan="2">
                </td>
            </tr>
            <tr>
                <td>Водитель<span class="required_field_mark">*</span></td>
                <td id="act_driver" name="act_driver" colspan="2">
                </td>
            </tr>
            <tr>
                <td>Механик<span class="required_field_mark">*</span></td>
                <td id="act_mechanic" name="act_mechanic" colspan="2">
                </td>
            </tr>
            <tr>
                <td>Причина ремонта</td>
                <td colspan="2">
                    <input id="reason_for_repair" type="text">
                </td>
            </tr>
            <tr>
                <td>Выполненные работы</td>
                <td colspan="2">
                    <textarea id="work_performed" cols="50" rows="2" type="text"></textarea>
                </td>
            </tr>
            <tr>
                <td>Показание одометра</td>
                <td colspan="2">
                    <input id="act_odometer" type="text">
                </td>
            </tr>
            <tr>
                <td class = "col1">Период ожидания (с-по)</td>
                <td class = "col2"><input id="act_wait_start_date" type="text"></td>
                <td class = "col4"><input id="act_wait_end_date" type="text"></td>
            </tr>
            <tr>
                <td class = "col1">Период факт. ремонта (с-по)</td>
                <td class = "col2"><input id="act_repair_start_date" type="text"></td>
                <td class = "col4"><input id="act_repair_end_date" type="text"></td>
            </tr>
            <tr>
                <td>Израсходованные материалы</td>
                <td id="act_expended" colspan="2">
                    <select size="3" id="act_expended_list" multiple name="act_expended_list"></select>
                </td>
            </tr>
            <tr>
                <td></td>
                <td id="act_expended_buttons" colspan="2">
                    <input type="button" id="insert_expended" name="insert_expended" value="Добавить материал">
                    <input type="button" id="delete_expended" name="delete_expended" value="Удалить материал">
                </td>
            </tr>
            <tr>
                <td>Собственный ремонт</td>
                <td colspan="2">
                    <input type="checkbox" id="self_repair" name="self_repair" checked>
                </td>
            </tr>
        </table>
        <div id="error_act_create"></div>
    </div>
    <div id="add_expended">             <!-- Форма добавления расходного материала -->
        <table>
            <tr>
                <td class="col1">Наименование</td>
                <td class="col2">
                    <input type="text" id="act_expended_edit_name" name="act_expended_edit_name">
                </td>
            </tr>
            <tr>
                <td>Количество</td>
                <td>
                    <input type="text" id="act_expended_edit_count" name="act_expended_edit_count">
                </td>
            </tr>
        </table>
        <div id="error_add_expended"></div>
    </div>
    <div id='waybill_create_form'>                                       <!--Форма подачи путевого листа-->
        <table>
            <tr>
                <td>Номер</td>
                <td colspan="3">
                    <input id="waybill_number" type="text">
                </td>
            </tr>
            <tr>
                <td class = "col1">Период действия (с-по)<span class="required_field_mark">*</span></td>
                <td class = "col2"><input id="waybill_start_date" type="text"></td>
                <td class = "col4"><input id="waybill_end_date" type="text"></td>
            </tr>
            <tr>
                <td>Транспортное средство<span class="required_field_mark">*</span></td>
                <td id="waybill_car" name="waybill_car" colspan="2">
                </td>
            </tr>
            <tr>
                <td>Водитель<span class="required_field_mark">*</span></td>
                <td id="waybill_driver" name="waybill_driver"  colspan="2">
                </td>
            </tr>
            <tr>
                <td>В распоряжение<span class="required_field_mark">*</span></td>
                <td id="waybill_department" name="waybill_department"  colspan="2">
                </td>
            </tr>
            <tr>
                <td>Показание спидометра (до выезда)</td>
                <td colspan="2">
                    <input id="waybill_mileage_before" type="text">
                </td>
            </tr>
            <tr>
                <td>Пробег</td>
                <td colspan="2">
                    <input id="waybill_mileages" type="text">
                </td>
            </tr>
            <tr>
                <td>Остаток горючего при выезде</td>
                <td colspan="2">
                    <input id="waybill_fuel_before" type="text">
                </td>
            </tr>
            <tr>
                <td>Выдано горючего</td>
                <td colspan="2">
                    <input id="waybill_given_fuel" type="text">
                </td>
            </tr>
            <tr>
                <td>Марка горючего</td>
                <td id="waybill_fuel_type" name="waybill_fuel_type"  colspan="2">
                </td>
            </tr>
            <tr>
                <td>Маршрут</td>
                <td id="ways" name="ways" colspan="2">
                    <select size="4" id="ways_list" multiple name="ways_list"></select>
                </td>
            </tr>
            <tr>
                <td></td>
                <td id="ways_buttons" colspan="2">
                    <input type="button" id="insert_way" name="insert_way" value="Добавить маршрут">
                    <input type="button" id="delete_way" name="delete_way" value="Удалить маршрут">
                </td>
            </tr>
        </table>
        <div id="error_waybill_create"></div>
    </div>
    <div id="add_way">             <!-- Форма добавления маршрута -->
        <table>
            <tr>
                <td class="col1">Маршрут (из)</td>
                <td class="col2">
                    <select id="way_value_from" name="way_value_from">
                        <option value="Бикей">Бикей</option>
                        <option value="Гидростроитель">Гидростроитель</option>
                        <option value="Осиновка">Осиновка</option>
                        <option value="Падун">Падун</option>
                        <option value="Порожский">Порожский</option>
                        <option value="Сосновый">Сосновый</option>
                        <option value="Стениха">Стениха</option>
                        <option value="Сухой">Сухой</option>
                        <option value="Центральный">Центральный</option>
                        <option value="Чекановский">Чекановский</option>
                        <option value="Энергетик">Энергетик</option>
                        <option value="Южный Падун">Южный Падун</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="col1">Маршрут (в)</td>
                <td class="col2">
                    <select id="way_value_to" name="way_value_to">
                        <option value="Бикей">Бикей</option>
                        <option value="Гидростроитель">Гидростроитель</option>
                        <option value="Осиновка">Осиновка</option>
                        <option value="Падун">Падун</option>
                        <option value="Порожский">Порожский</option>
                        <option value="Сосновый">Сосновый</option>
                        <option value="Стениха">Стениха</option>
                        <option value="Сухой">Сухой</option>
                        <option value="Центральный">Центральный</option>
                        <option value="Чекановский">Чекановский</option>
                        <option value="Энергетик">Энергетик</option>
                        <option value="Южный Падун">Южный Падун</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Время выезда</td>
                <td>
                    <input type="text" id="way_out_time" name="way_out_time">
                </td>
            </tr>
            <tr>
                <td>Время приезда</td>
                <td>
                    <input type="text" id="way_return_time" name="way_return_time">
                </td>
            </tr>
            <tr>
                <td>Пройдено (км)</td>
                <td>
                    <input type="text" id="way_distance" name="way_distance">
                </td>
            </tr>
        </table>
        <div id="error_add_way"></div>
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

