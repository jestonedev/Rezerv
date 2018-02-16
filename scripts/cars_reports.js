$(document).ready(function() {
    if ($.fn.datepicker != undefined) {
        $("#reportFuelByMonthDate").datepicker({
            format: "mm.yyyy",
            weekStart: 1,
            maxViewMode: 2,
            minViewMode: 1,
            todayBtn: "linked",
            language: "ru",
            orientation: "bottom auto",
            autoclose: true,
            todayHighlight: true,
            startDate: "01.1990"
        });

        $("#reportFuelByQuarterYear").datepicker({
            format: "yyyy",
            weekStart: 1,
            maxViewMode: 2,
            minViewMode: 2,
            todayBtn: "linked",
            language: "ru",
            orientation: "bottom auto",
            autoclose: true,
            todayHighlight: true,
            startDate: "1990"
        });
    }
});
