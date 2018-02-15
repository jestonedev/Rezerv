//Есть привелегия на просмотр транспорта
function hasManageCarsPrivilege()
{
    var hasCRP = false;
    $.ajax({
        type: "POST",
        url: "inc/can_manage_cars.php",
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

var carsDatePickerSettings = {
    format: "dd.mm.yyyy",
    weekStart: 1,
    maxViewMode: 2,
    todayBtn: "linked",
    language: "ru",
    orientation: "bottom auto",
    autoclose: true,
    todayHighlight: true,
    startDate: "01/01/1753"
};

$(document).ready(
    function() {
        $("body").on("click", ".select-date-button",
            function() {
                $(this).closest(".date.input-group").find("input").datepicker("show");
            });

        $(".cars__change-norm-button").on("click", function() {
            $("#car-fuel-consumption-change").modal('show');
        });

        if ($.fn.datepicker != undefined) {
            $("#fuelConsumptionDate, #fuelMonthLimitDate").datepicker(carsDatePickerSettings);
        }

        $("#save-fuel-consumption").on("click", function() {
            var errorBlock = $(".fuel-consumption-data-error");
            errorBlock.hide();
            var fuelConsumption = $("#fuelConsumption").prop("value");
            var fuelConsumptionDate = $("#fuelConsumptionDate").prop("value");
            var carId = $("#fuelConsumptionIdCar").prop("value");
            var error = "";
            if (!floatCorrect(fuelConsumption))
            {
                error += "<div>Некорректно задана норма расхода</div>";
            }
            if (!dateCorrect(fuelConsumptionDate))
            {
                error += "<div>Некорректно задана дата</div>";
            }
            if (error != "")
            {
                errorBlock.html(error).show();
            } else
            {
                $.ajax({
                    type: "POST",
                    url: "inc/change_fuel_consumption.php",
                    data: {
                        "fuel_consumption": fuelConsumption,
                        "fuel_consumption_date": fuelConsumptionDate,
                        "car_id": carId
                    },
                    success: function(currentFuelConsumption) {
                        $("#current-fuel-consumption").text(currentFuelConsumption);
                        $("#car-fuel-consumption-change").modal('hide');
                    },
                    error: function(msg)
                    {
                        $(".fuel-consumption-data-error").html(msg.responseText).show();
                    }
                });

            }
        });

        $(".cars__change-month-fuel-limit-button").on("click", function() {
            $("#car-fuel-month-limit-change").modal('show');
        });

        $("#save-fuel-month-limit").on("click", function() {
            var errorBlock = $(".fuel-month-limit-data-error");
            errorBlock.hide();
            var fuelMonthLimit = $("#fuelMonthLimit").prop("value");
            var fuelMonthLimitDate = $("#fuelMonthLimitDate").prop("value");
            var carId = $("#fuelMonthLimitIdCar").prop("value");
            var error = "";
            if (!floatCorrect(fuelMonthLimit))
            {
                error += "<div>Некорректно задана лимит топлива</div>";
            }
            if (!dateCorrect(fuelMonthLimitDate))
            {
                error += "<div>Некорректно задана дата</div>";
            }
            if (error != "")
            {
                errorBlock.html(error).show();
            } else
            {
                $.ajax({
                    type: "POST",
                    url: "inc/change_fuel_month_limit.php",
                    data: {
                        "fuel_month_limit": fuelMonthLimit,
                        "fuel_month_limit_date": fuelMonthLimitDate,
                        "car_id": carId
                    },
                    success: function(currentFuelConsumption) {
                        $("#current-fuel-month-limit").text(currentFuelConsumption);
                        $("#car-fuel-month-limit-change").modal('hide');
                    },
                    error: function(msg)
                    {
                        $(".fuel-month-limit-data-error").html(msg.responseText).show();
                    }
                });

            }
        });
    });