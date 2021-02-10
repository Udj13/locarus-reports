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

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
     </head>
    <title>Работа с абоненткой</title>
  </head>
  <body>

    <section class="section-button-delete float-right">
      <a href="delhalf.php"  class="badge badge-danger"><i class="fas fa-trash"></i> Удалялка данных </a>
      <br>
    </section>


    <section class="selection-form">
      <form action="report3.php" method="post">
        <div class="dropdown bootstrap-select form-control client-selector" style="width: 100%;">
        <select name="client" class="selectpicker custom-select custom-select-lg mb-3 form-control client-selector" data-width="100%" data-show-subtext="true" data-live-search="true">
          <option value="">---ВСЕ---</option>
          <?php
            $hRes = $db->query("SELECT id_client, name, lastname, inn from main.clients");
            foreach($hRes->fetchAll() as $row) {
          	   $selected = (@$_POST['client']==$row['id_client']) ? 'selected' : '';
               if ($row['inn'] != "") {
                  echo "<option $selected value=\"" . $row['id_client'] . "\" data-subtext=". $row['inn'] ." >" . $row['lastname'];
               }
            }
          ?>
        </select>
      </div>

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
        <br>

        <div class="div-with-buttons">
          <div class="custom-control custom-radio custom-control-inline">
            <input type="radio" id="customRadioInline1" name="report" value="2" class="custom-control-input" checked>
            <label class="custom-control-label" for="customRadioInline1">Показать активные дни</label>
          </div>
          <div class="custom-control custom-radio custom-control-inline">
            <input type="radio" id="customRadioInline2" name="report" value="1" class="custom-control-input">
            <label class="custom-control-label" for="customRadioInline2">Данные по дням</label>
          </div>

          <button class="btn btn-primary" type="submit">Показать <i class="fas fa-chevron-circle-right"></i></button>
        </div>
      </form>
    </section>



<?
if (@$_POST['report']>0 && !@$_POST['client'] && !@$_POST['date']) die ("Ненене, хоть 1 фильтр надо выбрать, а то сцыкотно");

if (@$_POST['report']==1) {

  echo "<section class='section-report'>";

	$where = (@$_POST['client']) ? "WHERE id_client='" . intval($_POST['client']) . "'" : '';
    $hRes = $db->query("SELECT id_device, imei_name, black_date, license_time, number, model, state from main.devices $where");

    foreach($hRes->fetchAll() as $row) {
        $id = $row['id_device'];

        $imei = $row['imei_name'];
        $number = $row['number'];
        $model = $row['model'];
        $black_date = $row['black_date'];
        $license_time = $row['license_time'];
        $state = $row['state'];

        echo "<div class='row row-cols-1 row-cols-sm-2 row-cols-md-3'>";
        echo "<div class='col mb-4'>";

          $license_time = explode(" ", $license_time)[0];
          $black_date = explode(" ", $black_date)[0];

          if ($state & 0x0400) {
              echo "<div class='card text-white bg-warning mb-3' style='max-width: 18rem;'>";
          } else {
            if (( $license_time < date("Y-m-d") )or( $black_date < date("Y-m-d") )) {
                echo "<div class='card text-white bg-dark mb-3' style='max-width: 18rem;'>";
            } else {
                  echo "<div class='card text-white bg-primary mb-3' style='max-width: 18rem;'>";
              }
          }

            echo "<div class='card-header'>". $imei ." </div>";
            echo "<div class='card-body'>";
              echo "<h5 class='card-title'>".$model." ".$number."</h5>";
              echo "<p class='card-text'>Лицензия: ".$license_time."</p>";
              echo "<p class='card-text'>Черная дата: ".$black_date."</p>";
            echo "</div>";
          echo "</div>";
        echo "</div>";

        $where2 = (@$_POST['date']) ? "AND CAST(\"date\" as text) LIKE '" . mysql_escape_string($_POST['date']) . "%'" : '';
        $hRes2 = $db->query("SELECT CAST(\"date\" as date) as mydate, SUM(data_size) as mytraffic from main.data_$id WHERE TRUE $where2 GROUP by mydate ORDER by mydate");
        $rows = $hRes2->fetchAll();
        if (count($rows)>0) {
            echo "<div class='col mb-4'>";
            echo "<div class='card bg-light mb-3' style='max-width: 18rem;'>";
            echo "<div class='card-header'>Данные</div>";
            echo "<div class='card-body'>";
            echo "<h5 class='card-title'>Дата / МБ</h5>";

			      foreach($rows as $row2) {
                echo "<p class='card-text'>" . $row2['mydate'] . "  /  " . round($row2['mytraffic']/1024/1024,2) . "</p>";
			      }

            echo "</div>";
            echo "</div>";
            echo "</div>";
		    }
        $where2 = (@$_POST['date']) ? "AND CAST(\"record_date\" as text) LIKE '" . mysql_escape_string($_POST['date']) . "%'" : '';
        $hRes2 = $db->query("SELECT CAST(\"record_date\" as date) as mydate, SUM(data_size) as mytraffic from main.navi_$id WHERE TRUE $where2 GROUP by mydate ORDER by mydate");
        $rows = $hRes2->fetchAll();
        if (count($rows)>0) {
            echo "<div class='col mb-4'>";
            echo "<div class='card bg-light mb-3' style='max-width: 18rem;'>";
            echo "<div class='card-header'>Навигация</div>";
            echo "<div class='card-body'>";
            echo "<h5 class='card-title'>Дата / МБ</h5>";

            foreach($rows as $row2) {
                echo "<p class='card-text'>" . $row2['mydate'] . "  /  " . round($row2['mytraffic']/1024/1024,2) . "</p>";
            }
            echo "</div>";
            echo "</div>";
            echo "</div>";
		}
    echo "</div>";
	}

  echo "</section>";


} elseif (@$_POST['report']==2) {


  echo "<section class='section-report'>";
  echo "<div class='row row-cols-1 row-cols-sm-2 row-cols-md-3'>";


	$where = (@$_POST['client']) ? "WHERE id_client='" . intval($_POST['client']) . "'" : '';

    $hRes = $db->query("SELECT id_device, imei_name, black_date, license_time, number, model from main.devices $where");
    $total_cars = 0;

    foreach($hRes->fetchAll() as $row) {
        $id = $row['id_device'];

        $imei = $row['imei_name'];
        $number = $row['number'];
        $model = $row['model'];
        $black_date = $row['black_date'];
        $license_time = $row['license_time'];

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
            echo "<div class='col mb-4'>";

              $license_time = explode(" ", $license_time)[0];
              $black_date = explode(" ", $black_date)[0];

              if (( $license_time < date("Y-m-d") )or( $black_date < date("Y-m-d") )) {
                  echo "<div class='card text-white bg-dark mb-3' style='max-width: 18rem;'>";
              } else {
                if ($active_days > $min_days) {
                  echo "<div class='card text-white bg-success mb-3' style='max-width: 18rem;'>";
                } else {
                  echo "<div class='card bg-light mb-3' style='max-width: 18rem;'>";
                }
              }

                echo "<div class='card-header'>". $active_days ." дней</div>";
                echo "<div class='card-body'>";
                  echo "<h5 class='card-title'>".$model." ".$number."</h5>";
                  echo "<p class='card-text'>".$imei."</p>";
                  echo "<p class='card-text'>Лицензия: ".$license_time."</p>";
                  echo "<p class='card-text'>Черная дата: ".$black_date."</p>";
                echo "</div>";
              echo "</div>";
            echo "</div>";


            if( $active_days > $min_days ){
              $total_cars ++;
            }
        }
	  }
    echo "</div>";
    echo "</section>";

    echo "<div class='lead'>";
    echo "Активных машин " . $total_cars;
    echo "</div>";

}

?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/f0f5b99f01.js" crossorigin="anonymous"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/i18n/defaults-*.min.js"></script>

  </body>
</html>
