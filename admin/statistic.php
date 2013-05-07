<?php
##################################################
##												##
## @Author:       Andrey Brykin (Drunya)        ##
## @Version:      1.1                           ##
## @Project:      CMS                           ##
## @package       CMS Fapos                     ##
## @subpackege    Admin module                  ##
## @copyright     ©Andrey Brykin 2010-2012      ##
## @last mod.     2012/06/10                    ##
##################################################


##################################################
##												##
## any partial or not partial extension         ##
## CMS Fapos,without the consent of the         ##
## author, is illegal                           ##
##################################################
## Любое распространение                        ##
## CMS Fapos или ее частей,                     ##
## без согласия автора, является не законным    ##
##################################################
include_once '../sys/boot.php';
include_once ROOT . '/admin/inc/adm_boot.php';


$first_date = $FpsDB->query("SELECT `date` FROM `" . $FpsDB->getFullTableName('statistics') . "` ORDER BY `date` ASC LIMIT 1");
$first_date = ($first_date && is_array($first_date) && count($first_date) > 0 && isset($first_date[0]['date'])) ? strtotime($first_date[0]['date']) : time();

$date = (!empty($_GET['date'])) ? strtotime(date("Y-m-d", intval($_GET['date']))) : 0;
if ($date > time()) $date = strtotime(date("Y-m-d"));
elseif ($date < $first_date) $date = $first_date;


if (!empty($_POST['grfrom'])) $_POST['grfrom'] = preg_replace('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', '$3-$1-$2', $_POST['grfrom']);
if (!empty($_POST['grto'])) $_POST['grto'] = preg_replace('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', '$3-$1-$2', $_POST['grto']);
$graph_to = (!empty($_POST['grto']) && preg_match('#^\d{4}-\d{2}-\d{2}$#', $_POST['grto'])) ? $_POST['grto'] : date("Y-m-d", $date);
if (strtotime($graph_to) > time()) {
	$graph_to = date("Y-m-d");
	$date = strtotime(date("Y-m-d"));
}
$graph_from = (!empty($_POST['grfrom']) && preg_match('#^\d{4}-\d{2}-\d{2}$#', $_POST['grfrom'])) ? $_POST['grfrom'] : date("Y-m-d", $date - 2592000);
if (strtotime($graph_from) < $first_date) {
	$graph_from = date("Y-m-d", $first_date);
}


$_date = mysql_real_escape_string(date("Y-m-d", $date));
if ($_date == date("Y-m-d")) {
	if (file_exists(ROOT . '/sys/logs/counter/' . $_date . '.dat')) {
		$stats[0] = unserialize(file_get_contents(ROOT . '/sys/logs/counter/' . $_date . '.dat'));
	}
} else {
	$stats = $FpsDB->query("SELECT * FROM `" . $FpsDB->getFullTableName('statistics') . "` WHERE `date` = '" . $_date . "'");
}


$Model = $Register['ModManager']->getModelInstance('Statistics');
$all = $Model->getCollection(array(
	"date >= '{$graph_from}'",
	"date <= '{$graph_to}'",
), array(
	'order' => 'date ASC',
));
$json_data_v = array();
$json_data_h = array();
if (!empty($all) && is_array($all) && count($all) > 1) {
	foreach ($all as $item) {
		//$json_data[] = (int)$item->getViews();
		$json_data_v[] = array(
			$item->getDate(),
			(int)$item->getViews(),
		);
		$json_data_h[] = array(
			$item->getDate(),
			(int)$item->getIps(),
		);
	}
}





if ($stats && is_array($stats) && count($stats) > 0) {
	$t_hosts = isset($stats[0]['ips']) ? $stats[0]['ips'] : 0;
	$t_views = isset($stats[0]['views']) ? $stats[0]['views'] : 0;
	$t_visitors = isset($stats[0]['cookie']) ? $stats[0]['cookie'] : 0;
	$views_on_visit = ($t_visitors > 0) ? number_format(($t_views / $t_visitors), 1) : '-';
	$bot_views = $stats[0]['yandex_bot_views'] + $stats[0]['google_bot_views'] + $stats[0]['other_bot_views'];
	$json_data_v[] = array(date("Y-m-d"), (int)$t_views);
	$json_data_h[] = array(date("Y-m-d"), (int)$t_hosts);
}


$json_data = json_encode(array(
	$json_data_v,
	$json_data_h,
));

//pr($json_data); die();
$pageTitle = 'Статистика';
$pageNav = $pageTitle;
$pageNavl = '';
include_once ROOT . '/admin/template/header.php';

?>



<div class="list">
	<div class="title">
		<table cellspacing="0" width="100%">
			<tr>
			<td width="20%">
			<?php if (($date - 172800) >= $first_date): ?>
			<a style="color:#8BB35B;" href="statistic.php?date=<?php echo $date - 172800 ?>"><?php echo '&laquo; ' . date("Y-m-d", $date - 172800) ?></a>
			<?php endif; ?>
			</td>
			<td width="20%">
			<?php if (($date - 86400) >= $first_date): ?>
			<a style="color:#8BB35B;" href="statistic.php?date=<?php echo $date - 86400 ?>"><?php echo '&laquo; ' . date("Y-m-d", $date - 86400) ?></a>
			<?php endif; ?>
			</td>
			<td width="20%" align="center"><a href="statistic.php?date=<?php echo $date ?>"><span style="color:#8BB35B;"><?php echo date("Y-m-d", $date) ?></span></a></td>
			<td width="20%" align="right"> 
			<?php if (($date + 86400) < time()): ?>
			<a style="color:#8BB35B;" href="statistic.php?date=<?php echo $date + 86400 ?>"><?php echo date("Y-m-d", $date + 86400) . ' &raquo;' ?></a>
			<?php endif; ?>
			</td>
			<td width="20%" align="right"> 
			<?php if (($date + 172800) < time()): ?>
			<a style="color:#8BB35B;" href="statistic.php?date=<?php echo $date + 172800 ?>"><?php echo date("Y-m-d", $date + 172800) . ' &raquo;' ?></a>
			<?php endif; ?>
			</td>
			</tr>
		</table>
	</div>
	<table class="grid" style="width:100%;"  cellspacing="0px">
		<?php if (!empty($stats)): ?>
		<tr>
			<td>Просмотров</td>
			<td width="150"><?php echo $t_views ?></td>
		</tr>
		<tr>
			<td>Хостов</td>
			<td><?php echo $t_hosts ?></td>
		</tr>
		<tr>
			<td>Посетителей</td>
			<td><?php echo $t_visitors ?></td>
		</tr>
		<tr>
			<td>Просмотров на посетителя</td>
			<td><?php echo $views_on_visit ?></td>
		</tr>
		<tr>
			<td>Просмотров роботами</td>
			<td><?php echo $bot_views ?></td>
		</tr>
		<tr>
			<td>Робот ПС google</td>
			<td><?php echo $stats[0]['google_bot_views'] ?></td>
		</tr>
		<tr>
			<td>Робот ПС yandex</td>
			<td><?php echo $stats[0]['yandex_bot_views'] ?></td>
		</tr>
		<tr>
			<td>Переходы с других сайтов</td>
			<td><?php echo $stats[0]['other_site_visits'] ?></td>
		</tr>
		<?php else: ?>
		
		<tr>
			<td align="center" colspan="2">Записей нет</td>
		</tr>
		
		<?php endif; ?>
		
		
		
		


		<?php if(count($json_data_v) > 1 && count($json_data_h) > 1 && !empty($json_data)): ?>
		<tr>
			<td colspan="2">
		<link type="text/css" rel="StyleSheet" href="template/css/tcal.css" />
		<script type="text/javascript" src="js/graphlib.js"></script>
		<script type="text/javascript" src="js/tcal.js"></script>
		<script type="text/javascript">
		$(document).ready(function(){
			var data = '<?php echo $json_data; ?>';
			//data = '['+data+']';
			//alert(data);
		  var plot2 = $.jqplot ('graph', eval(data), {
			  // Give the plot a title.
			  title: 'Views and hosts',
			  // You can specify options for all axes on the plot at once with
			  // the axesDefaults object.  Here, we're using a canvas renderer
			  // to draw the axis label which allows rotated text.
			  axesDefaults: {
				labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
				gridLineColor: "#ff0000",
				border: '#ff0000'
			  },
			  // An axes object holds options for all axes.
			  // Allowable axes are xaxis, x2axis, yaxis, y2axis, y3axis, ...
			  // Up to 9 y axes are supported.
			  axes: {
				// options for each axis are specified in seperate option objects.
				xaxis: {
				  min: '<?php echo $graph_from; ?>',
				  max: '<?php echo $graph_to; ?>',
				  renderer:$.jqplot.DateAxisRenderer,
				  tickOptions:{
					//formatString:'%b&nbsp;%#d'
					formatString:'%b-%d'
				  }
				}
			  },
			  highlighter: {
				show: true,
				sizeAdjust: 7.5
			  },
			  cursor: {
				show: false
			  },
			series:[
			  {
				// Change our line width and use a diamond shaped marker.
				lineWidth:1,
				fill: true,
				fillAndStroke: true,
				fillColor: '#d5eC86',
				fillAlpha: 0.5,
				label:'Views',
				//color:'#333',
				markerOptions: { style:'dimaond'}
			  },
			  {
				// Use a thicker, 5 pixel line and 10 pixel
				// filled square markers.
				lineWidth:5,
				label:'Hosts',
				markerOptions: { style:"filledSquare", size:10 }
			  }
			],
			grid: {
				background: '#282828'
			}
			});
		});
		</script>
		<div style="width:90%; height:350px; margin:0px auto;" id="graph"></div>
			</td>
		</tr>
		<?php endif; ?>


		

			

		<tr>
			<td colspan="2">
				<form method="POST" action="">
				<br />
				<table class="lines"  cellspacing="0px">
					<tr>
						<td>
							&nbsp;От&nbsp;:&nbsp;&nbsp;<input class="tcal" id="ffrom" type="text" name="grfrom" />
							&nbsp;До&nbsp;:&nbsp;&nbsp;<input class="tcal" id="fto" type="text" name="grto" />
						</td>
						<td><input type="submit" name="send" value="Отправить" /></td>
					</tr>
				</table>
				</form>
			</td>
		</tr>
	</table>
</div>




<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />

<?php 
include_once 'template/footer.php';
?>
