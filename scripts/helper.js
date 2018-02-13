////////////////////////////////////////////
//Функции проверки состояния форм запросов//
////////////////////////////////////////////

function timeCorrect(time)
{
    var reg = /^([0-1][0-9]|[2][0-3])(:([0-5][0-9])){1,2}$/i;
    return reg.test(time);
}

function dateCorrect(date)
{
    var date_params = date.split('.');
    var dateObj = new Date(date_params[2], date_params[1] - 1, date_params[0]);
    return ((date_params[2] == dateObj.getFullYear()) &&
    (date_params[1] == dateObj.getMonth()+1) &&
    (date_params[0] == dateObj.getDate()));
}

function intCorrect(int_val)
{
    var reg = /^[0-9]+$/i;
    return reg.test(int_val);
}

function floatCorrect(float_val)
{
    var reg = /^[0-9]+[.]{0,1}[0-9]{0,3}$/i;
    return reg.test(float_val);
}
