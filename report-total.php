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

$min_days = 9;

try {
$db = new PDO("pgsql:dbname=$sql_basename;host=$sql_host", $sql_username, $sql_passwd );
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e)
{
echo $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
    <link rel="stylesheet" href="stylesheet.css">
    <title>Работа с абоненткой</title>
  </head>
  <body>



    <section class="selection-form">
      <div class='container' id='containers'>
        <form action="report-total.php" method="post">

        <select name="date" class="custom-select custom-select-sm"  id="select-date" >
          <option value="">---ВСЕ---</option>
          <?php
            $min_m = round((1230757200-time())/3600/24/30);
            for ($m=date("m"); $m>$min_m; $m--) {
      	       $d = date("Y-m", mktime(0, 0, 0, $m, 15, date("Y")));
      	       $d_text = date("F Y", mktime(0, 0, 0, $m, 15, date("Y")));
      	       $selected = (@$_POST['date']==$d) ? 'selected' : '';
      	       echo "<option $selected value=\"$d\">$d_text";
            }
          ?>
        </select>
          <button class="btn btn-primary" type="submit">Показать <i class="fas fa-chevron-circle-right"></i></button>
      </form>
    </div>
    </section>



<?
if (@$_POST['date']) {
  $hRes = $db->query("SELECT id_client, name, lastname, inn from main.clients");
  $index = 0;
  foreach($hRes->fetchAll() as $row) {
    if ($row['inn'] != "") {
      $index++;
      $client_info = $index. ". ". $row['lastname'] . " (ИНН: " . $row['inn'] . ")";

      $total_cars = 0;
      $devices_string = "";

      $where = (@$row['id_client']) ? "WHERE id_client='" . intval($row['id_client']) . "'" : '';
      $hRes = $db->query("SELECT id_device, imei_name, number, model from main.devices $where");
      foreach($hRes->fetchAll() as $client_row) {
        $id = $client_row['id_device'];
        $active_days = 0;

        $where2 = (@$_POST['date']) ? "AND CAST(\"date\" as text) LIKE '" . mysql_escape_string($_POST['date']) . "%'" : '';
        $hRes2 = $db->query("SELECT substring(CAST(\"date\" as text) for 7) as mydate, count(distinct CAST(\"date\" as date)) as active_days from main.data_$id WHERE TRUE  $where2 GROUP by mydate ORDER by mydate");
        $rows = $hRes2->fetchAll();
        if (count($rows)>0) {
          foreach($rows as $row2) {
            if ($active_days < $row2['active_days'] ) {
              $active_days = $row2['active_days'];
            }
          }
        }

        $where2 = (@$_POST['date']) ? "AND CAST(\"record_date\" as text) LIKE '" . mysql_escape_string($_POST['date']) . "%'" : '';
        $hRes2 = $db->query("SELECT substring(CAST(\"record_date\" as text) for 7) as mydate, count(distinct CAST(\"record_date\" as date)) as active_days from main.navi_$id WHERE TRUE  $where2 GROUP by mydate ORDER by mydate");
        $rows = $hRes2->fetchAll();
        if (count($rows)>0) {
            foreach($rows as $row2) {
                if ($active_days < $row2['active_days'] ) {
                  $active_days = $row2['active_days'];
                }
            }
        }

        if( $active_days > $min_days ){
          $total_cars ++;
          $devices_string .= " " . $client_row['model'] . " " . $client_row['number'] . " (". $client_row['imei_name'] .  ") - " .$active_days. " дней, ";
        }
      }


      echo "<div class='container' id='containers'>";
      echo "<hr class='my-4'>";
      echo "<h4 class='mt-4'>" . $client_info . " <span class='badge bg-warning text-dark'>" . $total_cars . "</span></h4>";
      echo "<p>" . $devices_string . "</p>";
      echo "</div>";
    }
  }
}

?>






    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/f0f5b99f01.js" crossorigin="anonymous"></script>
  </body>
</html>
