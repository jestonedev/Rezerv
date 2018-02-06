$(document).ready(function() {
    "use strict";

    initMileagesEditor();

    //Обработчик события нажатия на кнопку "Пробег автомобилей"
    $('#btnCarsInfo').button().click(function()
    {
        if (menu_code != 2)
        {
            setCookie("menu_code", 2);
            menu_code = 2;
            initDataTable();
        } else
        {
            $('#example').dataTable().api().ajax.reload();
        }
        initButtonsState();
        allowRefreshCounter = 0;
        forceRefreshCounter = 0;
    });

    //Функция инициализации формы редактирования пробега по транспорту
    function initMileagesEditor()
    {
        $("#mileage_date").inputmask("99.99.9999");
        var Now = new Date();
        //календарь
        $("#mileage_date").datepicker({
            monthNames: [ "Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь" ],
            dayNames:	["Воскресенье","Понедельник","Вторник","Среда","Четверг","Пятница","Суббота"],
            dayNamesMin:["Вс","Пн","Вт","Ср","Чт","Пт","Сб"],
            buttonImageOnly: true,
            buttonImage: "./img/SelCalendar.gif",
            buttonText: "Календарь",
            showOn: "button",
            dateFormat:"dd.mm.yy",
            firstDay: 1,
            defaultDate: Now
        });
    }
});


function show_mileage_details(sender)
{
    //Отобразить форму внесения данных о пробеге
    var id_car = $(sender).attr('value');
    var id_chief_default = $(sender).data('id-chief-default');
    $('#car_chief').prop('value', id_chief_default);
    $("#error_mileagesEditor").hide();
    $('#mileagesEditor').dialog({
        autoOpen: true,
        modal: true,
        title: "Добавить пробег",
        width: $(window).width()/2,
        resizable: false,
        buttons: [
            {text: "Добавить",
                click: function() {
                    if (!confirm('Вы действительно хотите добавить пробег?'))
                    {
                        return;
                    }
                    var milage_date = $("#mileage_date").prop("value");
                    var milage_value = $("#mileage_value").prop("value");
                    $("#error_mileagesEditor div").remove();
                    var is_error = false;
                    if (milage_date.length == 0)
                    {
                        $("#error_mileagesEditor").append("<div>Дата регистрации пробега не указана или указана неверно</div>");
                        is_error = true;
                    }
                    if (milage_value.match(/\d+/) === null) {
                        $("#error_mileagesEditor").append("<div>Некорректно указано значение пробега</div>");
                        is_error = true;
                    }
                    if (is_error)
                    {
                        $("#error_mileagesEditor").show();
                        return;
                    }
                    var mileage_type = $("#mileagesEditor select[name='mileage_type']").prop("value");
                    var car_chief = $("#mileagesEditor select[name='car_chief']").prop("value");
                    $.ajax( {
                            type: "POST",
                            url: "inc/add_mileage.php",
                            data: "id_car="+id_car+"&milage_date="+milage_date+"&milage_value="+milage_value+
                            "&mileage_type="+mileage_type+"&car_chief="+car_chief,
                            success: function(msg)
                            {
                                if (msg == '')
                                {
                                    $('#example').dataTable().api().ajax.reload();
                                    $('#mileagesEditor').dialog("close");
                                } else
                                {
                                    $("#error_mileagesEditor").append("<div>$msg</div>");
                                }
                            },
                            error: function(msg)
                            {
                            }
                        }
                    );
                }},
            {text: "Отменить",
                click: function() {
                    $( this ).dialog( "close" );
                }}
        ]
    }).height("auto");
}