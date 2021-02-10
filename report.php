<?php

function mysql_escape_string($value)
{
    $search = array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
    $replace = array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");

    return str_replace($search, $replace, $value);
}

header('Content-Type:text/html; charset=UTF-8');


ini_set('display_errors', 1);
$sql_host = "localhost";
$sql_username = "locarus";
$sql_passwd = "";
$sql_basename = "locarus";

try {
$db = new PDO("pgsql:dbname=$sql_basename;host=$sql_host", $sql_username, $sql_passwd );
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e)
{
echo $e->getMessage();
}
?>

<a href="delhalf.php">Удалялка данных тут</a><br>
<br>

<FORM action="report.php" method="post">
Клиент:
<SELECT name="client">
<OPTION value="">---ВСЕ---
<?php
$hRes = $db->query("SELECT id_client, name, lastname from main.clients");
foreach($hRes->fetchAll() as $row) {
	$selected = (@$_POST['client']==$row['id_client']) ? 'selected' : '';
	echo "<option $selected value=\"" . $row['id_client'] . "\">" . $row['id_client'] . ". " . $row['name'] . " " . $row['lastname'];
}
?>
</SELECT><br>

Дата:
<SELECT name="date">
<OPTION value="">---ВСЕ---
    <?php
$min_m = round((1230757200-time())/3600/24/30);
for ($m=date("m"); $m>$min_m; $m--) {
	$d = date("Y-m", mktime(0, 0, 0, $m, 15, date("Y")));
	$d_text = date("F Y", mktime(0, 0, 0, $m, 15, date("Y")));
	$selected = (@$_POST['date']==$d) ? 'selected' : '';
	echo "<option $selected value=\"$d\">$d_text";
}
?>
</SELECT><br>

Отчет:
<SELECT name="report">
<OPTION value="1">Дни/данные
<OPTION value="2">Активные дни
</SELECT><br>

<input type="Submit" value="Генерировать">
</FORM>

<?
if (@$_POST['report']>0 && !@$_POST['client'] && !@$_POST['date']) die ("Ненене, хоть 1 фильтр надо выбрать, а то сцыкотно");
if (@$_POST['report']==1) {
	$where = (@$_POST['client']) ? "WHERE id_client='" . intval($_POST['client']) . "'" : '';
    $hRes = $db->query("SELECT id_device, imei_name from main.devices $where");
    foreach($hRes->fetchAll() as $row) {
        $id = $row['id_device'];
        echo "<h2>#" . $id . " " . $row['imei_name'] . "</h2>\n";
        $where2 = (@$_POST['date']) ? "AND CAST(\"date\" as text) LIKE '" . mysql_escape_string($_POST['date']) . "%'" : '';
        $hRes2 = $db->query("SELECT CAST(\"date\" as date) as mydate, SUM(data_size) as mytraffic from main.data_$id WHERE TRUE $where2 GROUP by mydate ORDER by mydate");
        $rows = $hRes2->fetchAll();
        if (count($rows)>0) {
            echo "//таблица data_$id";
			echo "<TABLE border cellpadding=5 cellspacing=0>\n";
			echo "<TR><TH>Дата</TH><TH>Количество данных (МБ)</TH></TR>\n";

			foreach($rows as $row2) {
				echo "<TR><TD>" . $row2['mydate'] . "</TD><TD>" . round($row2['mytraffic']/1024/1024,2) . "</TD></TR>\n";
			}
			echo "</TABLE>\n";
		}
        $where2 = (@$_POST['date']) ? "AND CAST(\"record_date\" as text) LIKE '" . mysql_escape_string($_POST['date']) . "%'" : '';
        $hRes2 = $db->query("SELECT CAST(\"record_date\" as date) as mydate, SUM(data_size) as mytraffic from main.navi_$id WHERE TRUE $where2 GROUP by mydate ORDER by mydate");
        $rows = $hRes2->fetchAll();
        if (count($rows)>0) {
            echo "//таблица navi_$id";
            echo "<TABLE border cellpadding=5 cellspacing=0>\n";
            echo "<TR><TH>Дата</TH><TH>Количество данных (МБ)</TH></TR>\n";

            foreach($rows as $row2) {
                echo "<TR><TD>" . $row2['mydate'] . "</TD><TD>" . round($row2['mytraffic']/1024/1024,2) . "</TD></TR>\n";
            }
            echo "</TABLE>\n";
		}
	}

} elseif (@$_POST['report']==2) {
	$where = (@$_POST['client']) ? "WHERE id_client='" . intval($_POST['client']) . "'" : '';

    $hRes = $db->query("SELECT id_device, imei_name from main.devices $where");
    foreach($hRes->fetchAll() as $row) {
        $id = $row['id_device'];
		echo "<h2>#" . $id . " " . $row['imei_name'] . "</h2>\n";
        $where2 = (@$_POST['date']) ? "AND CAST(\"date\" as text) LIKE '" . mysql_escape_string($_POST['date']) . "%'" : '';
        $hRes2 = $db->query("SELECT substring(CAST(\"date\" as text) for 7) as mydate, count(distinct CAST(\"date\" as date)) as active_days from main.data_$id WHERE TRUE  $where2 GROUP by mydate ORDER by mydate");
        $rows = $hRes2->fetchAll();
        if (count($rows)>0) {
			echo "<TABLE border cellpadding=5 cellspacing=0>\n";
			echo "<TR><TH>Месяц</TH><TH>Количество активных дней</TH></TR>\n";

            foreach($rows as $row2) {
				echo "<TR><TD>" . $row2['mydate'] . "</TD><TD>" . $row2['active_days'] . "</TD></TR>\n";
			}
			echo "</TABLE>\n";
		}
        $where2 = (@$_POST['date']) ? "AND CAST(\"record_date\" as text) LIKE '" . mysql_escape_string($_POST['date']) . "%'" : '';
        $hRes2 = $db->query("SELECT substring(CAST(\"record_date\" as text) for 7) as mydate, count(distinct CAST(\"record_date\" as date)) as active_days from main.navi_$id WHERE TRUE  $where2 GROUP by mydate ORDER by mydate");
        $rows = $hRes2->fetchAll();
        if (count($rows)>0) {
            echo "<TABLE border cellpadding=5 cellspacing=0>\n";
            echo "<TR><TH>Месяц</TH><TH>Количество активных дней</TH></TR>\n";

            foreach($rows as $row2) {
                echo "<TR><TD>" . $row2['mydate'] . "</TD><TD>" . $row2['active_days'] . "</TD></TR>\n";
            }
            echo "</TABLE>\n";
        }
	}

}

?>
