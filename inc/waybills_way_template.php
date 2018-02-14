<?
$waysArray = ["Бикей", "Гидростроитель", "Осиновка", "Падун", "Порожский",
    "Сосновый", "Стениха", "Сухой", "Центральный", "Чекановский", "Энергетик", "Южный"];
?>

<tr class="waybill-way">
    <td><select class="form-control way-source">
            <option value="">Выберите район</option>
            <? foreach($waysArray as $way) { ?>
                <option <?=($wayFrom == $way ? "selected" : "")?> value="<?=$way?>"><?=$way?></option>
            <? } ?>
            <option <?=($wayFrom == "Братск" ? "selected" : "")?> value="Братск">Братск</option>
        </select></td>
    <td><select class="form-control way-destination">
            <option value="">Выберите район</option>
            <? foreach($waysArray as $way) { ?>
                <option <?=($wayTo == $way ? "selected" : "")?> value="<?=$way?>"><?=$way?></option>
            <? } ?>
            <option <?=($wayTo == "Командировка" ? "selected" : "")?> value="Командировка">Командировка</option>
        </select></td>
    <td>
        <input type="text" class="form-control way-time-from" value="<?=$wayTimeFrom?>">
    </td>
    <td>
        <input type="text" class="form-control way-time-to" value="<?=$wayTimeTo?>">
    </td>
    <td>
        <input type="text" class="form-control way-distance" value="<?=$wayDistance?>">
    </td>
</tr>