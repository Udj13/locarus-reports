<?php

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

//if ($_GET['optimize']) mysql_query("OPTIMIZE TABLE DATA");

if (@$_POST['pass']=='meebei') {
	foreach($_POST['delhalf'] as $id => $limit) {
        if ($limit<1) continue;
		$id = intval($id);
		$limit = intval($limit);
        $count = $db->exec("DELETE from main.data_$id WHERE id_data = any (array(SELECT id_data from main.data_$id ORDER by id_data LIMIT $limit))");
        $count2 = $db->exec("DELETE from main.navi_$id WHERE id_data = any (array(SELECT id_data from main.navi_$id ORDER by id_data LIMIT $limit))");

		echo "Удалили $count / $count2 (data/navi) записей прибора $id<BR>"; flush();
	}
	echo "Готово!";
	exit;
}
?>


<FORM action="delhalf.php" method="post">
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
<input type="submit" value="Генерить отчёт" />
</form>
<br><br>

<?php
    if (!isset($_POST['client'])) exit;

$where = (@$_POST['client']) ? "WHERE id_client='" . intval($_POST['client']) . "'" : '';
$hRes = $db->query("SELECT * from main.devices $where");

echo "<form method=post><TABLE border cellpadding=5 cellspacing=0>\n";
echo "<TR><TH>Девайс</TH><TH>Записей</TH><TH>Количество данных (примерно)</TH><TH>Удалить записей</TH><TH>&nbsp;</TH></TR>\n";
$total = 0;
$delhalf = array();
foreach($hRes->fetchAll() as $row) {
    $id = $row['id_device'];
    $hRes2 = $db->query("SELECT count(id_data) as cnt FROM main.data_$id");
    $hRes3 = $db->query("SELECT count(id_data) as cnt FROM main.navi_$id");
	$row2 = $hRes2->fetch();
	$row3 = $hRes3->fetch();

	$total += $size = $row2['cnt']*$row['last_size'];
	$total += $size2 = $row3['cnt']*$row['last_size'];

	$records = max($row2['cnt'], $row3['cnt']);
	echo "<TR><TD>" . $row['imei_name'] . " #" . $row['id_device'] . "</TD><TD>" . $row2['cnt'] . "+" . $row3['cnt'] . "</TD><TD>" . round($size/1024/1024, 2) . "+" . round($size2/1024/1024, 2) . " MB</TD><TD><input type=\"text\" name=\"delhalf[" . $row['id_device'] . "]\" id=\"delhalf" . $row['id_device'] . "\" value=\"\"></TD>";

	echo "<TD><input type=\"button\" onclick=\"document.getElementById('delhalf" . $row['id_device'] . "').value='" . round($records/2) . "'\" value=\"Удалить половину\"></TD>";
	echo "<TD><input type=\"button\" onclick=\"document.getElementById('delhalf" . $row['id_device'] . "').value='" . $records . "'\" value=\"Удалить все\"></TD>";
    echo "</TR>\n";
	//$delhelf[$row['ID_DEVICE']] = floor($row['cnt']/2);
}
echo "<tr><TD colspan=3>Итого: " . round($total/1024/1024) . " MB</TD></tr></TABLE>";

	echo "Удалить выбранные данные БЕЗВОЗВРАТНО: да, я не бухой и мне уже есть 18 и я знаю волшебное слово <input type=\"password\" name=\"pass\"><input type=\"Submit\" value=\"Удалить нах\"></form>";
?>
