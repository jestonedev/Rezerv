var  id_request;

$(document).ready(function() {
    "use strict";

    initCarSelectForm();

    //Обработчики событий нажатия на кнопки выбора вида заявки
    $('#btnTransportRequests').button().click(function()
    {
        setCookie("id_request",1);
        id_request = 1;
        if (menu_code > 0)
        {
            setCookie("menu_code",0);
            menu_code = 0;
            initDataTable();
        } else {
            $('#example').dataTable().api().ajax.reload(); }
        initButtonsState();
        allowRefreshCounter = 0;
        forceRefreshCounter = 0;
    });

    $('#btnGreatHallRequests').button().click(function()
    {
        setCookie("id_request",2);
        id_request = 2;
        if (menu_code > 0)
        {
            setCookie("menu_code",0);
            menu_code = 0;
            initDataTable();
        } else {
            $('#example').dataTable().api().ajax.reload(); }
        initButtonsState();
        allowRefreshCounter = 0;
        forceRefreshCounter = 0;
    });

    $('#btnSmallHallRequests').button().click(function()
    {
        setCookie("id_request",3);
        id_request = 3;
        if (menu_code > 0)
        {
            setCookie("menu_code",0);
            menu_code = 0;
            initDataTable();
        } else {
            $('#example').dataTable().api().ajax.reload(); }
        initButtonsState();
        allowRefreshCounter = 0;
        forceRefreshCounter = 0;
    });

    //Обработчик события нажатия на кнопку создания новой заявки
    $('#btnCreateRequest').button().click(function() {
        $('#frmRequest').remove();
        $.ajax({
            type: "POST",
            url: "processor.php",
            data: "action=display_request_form",
            success: function(msg)
            {
                $('body').append(msg);
                $(".time_field").inputmask("99:99");
                $(".date_field").inputmask("99.99.9999");
                var Now = new Date();
                //календарь
                $(".date_field").datepicker({
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

                $('#frmRequest').dialog({
                    autoOpen: true,
                    modal:true,
                    width: $(window).width() / 1.3,
                    height: "auto",
                    resizable: false,
                    buttons: [
                        {
                            text: "Подать заявку",
                            click: function() {
                                SendRequest(0);
                                $(this).dialog("option","height","auto");
                            }
                        },
                        {
                            text: "Выход",
                            click: function() {
                                $( this ).dialog( "close" );
                            }
                        }
                    ]
                });
            },
            error: function(msg) {
            }
        });
    });


    //Функция инициализации формы выбора автомобиля
    function initCarSelectForm()
    {
        $.ajax( {
                type: "POST",
                url: "inc/cars_list.php",
                success: function(msg)
                {
                    $('#select_car_form').append(msg);
                },
                error: function(msg)
                {
                }
            }
        );
    }
});

////////////////////////////////////////////
//Функции отображения детальной информации//
////////////////////////////////////////////

//Функция инициализации формы детальной информации о заявке
function fnFormatReqDetails ( oTable, nTr )
{
    var msga = "";
    var aData = oTable.fnGetData( nTr );
    var id_request_number = aData[1];
    $.ajax({
        type: "POST",
        url: "inc/details_by_id.php",
        data: 'action=request&id_request_number='+id_request_number+'&id_request='+id_request,
        async: false,
        success: function(msg)
        {
            msga = msg;
        },
        error: function(msg)
        {
        }
    });
    return msga;
}

function show_request_details(oTable, nTr)
{
    oTable.fnOpen( nTr, fnFormatReqDetails(oTable, nTr), 'details' );
    allowRefreshCounter += 1;
    $(".btnAcceptRequest, .btnRejectRequest, .btnCancelRequest, .btnCompleteRequest, .btnUnCompleteRequest, .btnChangeRequest").button();
    $(".btnAcceptRequest, .btnRejectRequest, .btnCancelRequest, .btnCompleteRequest, .btnUnCompleteRequest, .btnChangeRequest").button().unbind('click');
    $(".btnAcceptRequest").button().click(
        function()
        {
            var id_request_number = $(this).prop("value");
            if (id_request == 1)
            {
                $('#select_car_form').dialog({
                    autoOpen: true,
                    modal: true,
                    title: "Выбор автомобиля",
                    width: $(window).width()/2,
                    resizable: false,
                    buttons: [
                        {text: "Выбрать",
                            click: function() {
                                var id_car = $("#select_car_form select[name='car_id']").prop("value");
                                $.ajax( {
                                        type: "POST",
                                        url: "inc/moderation_request.php",
                                        data: "action=accept&id_request_number="+id_request_number+"&id_car="+id_car+
                                        "&id_request="+id_request,
                                        success: function(msg)
                                        {
                                            $('#example').dataTable().api().ajax.reload();
                                            $('#select_car_form').dialog("close");
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
            } else
            {
                if (confirm("Вы действительно хотите подтвердить заявку?"))
                {
                    $.ajax( {
                            type: "POST",
                            url: "inc/moderation_request.php",
                            data: "action=accept&id_request_number="+id_request_number+"&id_request="+id_request,
                            success: function(msg)
                            {
                                $('#example').dataTable().api().ajax.reload();
                            },
                            error: function(msg)
                            {
                            }
                        }
                    );
                }
            }
        }
    );
    $(".btnRejectRequest").button().click(
        function()
        {
            if (confirm("Вы действительно хотите отклонить заявку?"))
            {
                var id_request_number = $(this).prop("value");
                $.ajax( {
                        type: "POST",
                        url: "inc/moderation_request.php",
                        data: "action=reject&id_request_number="+id_request_number+"&id_request="+id_request,
                        success: function(msg)
                        {
                            $('#example').dataTable().api().ajax.reload();
                        },
                        error: function(msg)
                        {
                        }
                    }
                );
            }
        }
    );
    $(".btnCancelRequest").button().click(
        function()
        {
            if (confirm("Вы действительно хотите отменить свою заявку?"))
            {
                var id_request_number = $(this).prop("value");
                $.ajax( {
                        type: "POST",
                        url: "inc/moderation_request.php",
                        data: "action=cancel&id_request_number="+id_request_number+"&id_request="+id_request,
                        success: function(msg)
                        {
                            $('#example').dataTable().api().ajax.reload();
                        },
                        error: function(msg)
                        {
                        }
                    }
                );
            }
        }
    );
    $(".btnCompleteRequest").button().click(
        function()
        {
            if (confirm("Вы действительно хотите отметить заявку, как выполненную?"))
            {
                var id_request_number = $(this).prop("value");
                $.ajax( {
                        type: "POST",
                        url: "inc/moderation_request.php",
                        data: "action=complete&id_request_number="+id_request_number+"&id_request="+id_request,
                        success: function(msg)
                        {
                            $('#example').dataTable().api().ajax.reload();
                        },
                        error: function(msg)
                        {
                        }
                    }
                );
            }
        }
    );
    $(".btnUnCompleteRequest").button().click(
        function()
        {
            if (confirm("Вы действительно хотите отметить заявку как невыполненную?"))
            {
                var id_request_number = $(this).prop("value");
                $.ajax( {
                        type: "POST",
                        url: "inc/moderation_request.php",
                        data: "action=uncomplete&id_request_number="+id_request_number+"&id_request="+id_request,
                        success: function(msg)
                        {
                            $('#example').dataTable().api().ajax.reload();
                        },
                        error: function(msg)
                        {
                        }
                    }
                );
            }
        }
    );
    $(".btnChangeRequest").button().click(
        function()
        {
            var id_request_number = $(this).prop("value");

            $('#frmRequest').remove();
            $.ajax({
                type: "POST",
                url: "processor.php",
                data: "action=modify_request_form&id_request_number="+id_request_number,
                success: function(msg)
                {
                    $('body').append(msg);
                    $(".time_field").inputmask("99:99");
                    $(".date_field").inputmask("99.99.9999");
                    var Now = new Date();
                    //календарь
                    $(".date_field").datepicker({
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

                    $('#frmRequest').dialog({
                        autoOpen: true,
                        modal:true,
                        width: $(window).width() / 1.3,
                        height: "auto",
                        resizable: false,
                        buttons: [
                            {
                                text: "Изменить заявку",
                                click: function() {
                                    SendRequest(id_request_number);
                                    $(this).dialog("option","height","auto");
                                }
                            },
                            {
                                text: "Выход",
                                click: function() {
                                    $( this ).dialog( "close" );
                                }
                            }
                        ]
                    });
                },
                error: function(msg) {
                }
            });
        }
    );
}

//Добавление (если номер заявки 0) и изменение (если номер заявки > 0) заявки
function SendRequest(id_request_number){
    var frm = document.getElementById('ParamTable');
    var elems = frm.getElementsByTagName('*');
    var params='';
    var i = 0;
    for(i=0;i<elems.length;i++) {
        if ((elems[i].name!=null)&&((elems[i].name.indexOf("param")==0) || (elems[i].name == "department")))
        {
            if (elems[i].type == "checkbox")
            {
                if (elems[i].checked) {
                    params=params+elems[i].name+'='+elems[i].value+'&'; }
            }
            else
            if ((elems[i].value!=null) && (elems[i].value!="")) {
                params=params+elems[i].name+'='+elems[i].value+'&'; }
        }
    }
    var data = params+'action=process_request&id_request_number='+id_request_number+"&id_request="+id_request;
    if (id_request == 1)
    {
        var id_car = $("#ParamTable select[name='car_id']").prop("value");
        if (!isNaN(id_car)) {
            data = data + '&id_car='+id_car; }
    }
    $.ajax({
        type: "POST",
        url: "processor.php",
        async:false,
        data: data,
        success: function(msg)
        {
            var div = document.getElementById("div_result");
            div.innerHTML = msg;
            $('#example').dataTable().api().ajax.reload();
            if ((msg.indexOf('Заявка изменена') > -1) ||
                (msg.indexOf('Заявка добавлена') > -1))
            {
                //Если заявка успешно подана, закрываем диалог через 2000 мс
                setTimeout(closeRequestDialog, 2000);
            }
        },
        error: function(msg)
        {
            var div = document.getElementById("div_result");
            div.innerHTML = msg.responseText;
        }
    });
}

function closeRequestDialog()
{
    $( '#frmRequest' ).dialog( "close" );
}


//Есть привелегия на создание заявок
function hasCreateRequestPrivilege()
{
    var hasCRP = false;
    $.ajax({
        type: "POST",
        url: "inc/can_create_request.php",
        async: false,
        success: function(msg)
        {
            if (msg == '1') {
                hasCRP = true; }
        },
        error: function(msg)
        {
        }
    });
    return hasCRP;
}