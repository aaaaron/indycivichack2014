<!DOCTYPE html>
<?php
require_once('gmaps_key.php');
//error_reporting(0);
ini_set('display_errors', 'On');
ini_set('display_startup_errors', 'On');
//phpinfo();
//exit;

$bounds = array( array(39.937782, -86.344867), array(39.632271, -85.939060));
$bounding = true;

$search_token = 'key-'; //.implode('|',$bounds[0]);
//$search_token .= implode('|',$bounds[1]);

//echo $search_token; exit;

// Check for this search in memcached
$memcacheObj = new Memcached;
$memcacheObj->addServer('127.0.0.1','11211');
//$cache = $memcacheObj->get($search_token);

if( !isset($cache) or !is_array($cache) or !count($cache))
{
	$final = array();
	$dataset_string = file_get_contents('police_tickets.json');
	$dataset = json_decode($dataset_string, true);
$i = 0;
	foreach( $dataset['data'] as $row )
	{
		if( ! isset($row[15])) continue;
		if( $row[15][1] == 0 ) continue;
		if( $row[15][2] == 0 ) continue;
		$geo_y = $row[15][1];
		$geo_x = $row[15][2];
		if( $bounding ) {
			// Make sure this is in our area
			if( ! ($geo_x > $bounds[0][1])) {
				continue;
			}
			if( ! ($geo_x < $bounds[1][1])) {
				continue;
			}
			if( ! ($geo_y > $bounds[1][0])) {
				continue;
			}
			if( ! ($geo_y < $bounds[0][0])) {
				continue;
			}
		}
		$data = array( 'coord'=>array((float)$geo_y,(float)$geo_x)); //, 'offense'=>ucwords($row[11]));
		$final[] = $data;
	}
	unset($dataset);
	$data = $final;
	$memcacheObj->set($search_token, $data, 30000);
	$used_cache = false;
} else {
	$used_cache = true;
	$data = $cache;
}

$count = count($data);
if($used_cache) echo "Use Cache\n";
if(! $used_cache) echo "Didn't Cache\n";

?>
<html> 
<head> 
  <meta http-equiv="content-type" content="text/html; charset=UTF-8" /> 
  <title>Google Maps Multiple Markers</title> 
  <script src="https://maps.googleapis.com/maps/api/js?v=3&key=<?=$gmaps_key?>&sensor=true&libraries=visualization" type="text/javascript"></script>
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
</head> 
<body>
    <style type="text/css">
      html { height: 100% }
      body { height: 100%; margin: 0; padding: 0 }
      #map { height: 90% }
    </style>
<form action="/map.php" method=GET>
<!-- 
Search: <input name="search_token" value="<?=urldecode($search_token)?>">
-->
</form>
<?php if($used_cache) echo " (from cache)"; ?>
<br>
  <div id="map" xstyle="width: 500px; height: 400px;"></div>

  <script type="text/javascript">
var locations = <?=json_encode($data)?>;
//console.log(locations);

    var map = new google.maps.Map(document.getElementById('map'), {
      zoom: 11,
      center: new google.maps.LatLng(39.740986,-86.145447), //-33.92, 151.25),
      mapTypeId: google.maps.MapTypeId.ROADMAP
    });

    var infowindow = new google.maps.InfoWindow();
    var marker, i;

    var heatData = [];

    for (i = 0; i < locations.length; i++) {  
//console.log(locations[i]);
	heatData.push(new google.maps.LatLng(locations[i].coord[0], locations[i].coord[1]));

/*
      marker = new google.maps.Marker({
        position: new google.maps.LatLng(locations[i].coord[0], locations[i].coord[1]),
        map: map
      });

      google.maps.event.addListener(marker, 'click', (function(marker, i) {
        return function() {
          infowindow.setContent(locations[i].offense);
          infowindow.open(map, marker);
        }
      })(marker, i));
*/
    }

    var pointArray = new google.maps.MVCArray(heatData);

    heatmap = new google.maps.visualization.HeatmapLayer({
        data: pointArray,
	radius: 30
    });

    heatmap.setMap(map);

    var bounds = new google.maps.LatLngBounds(
        new google.maps.LatLng(39.937782, -86.344867),
        new google.maps.LatLng(39.632271, -85.939060)
    );

    // Define a rectangle and set its editable property to true.
    var rectangle = new google.maps.Rectangle({
        bounds: bounds,
        editable: true,
	draggable: true,
	fillColor: '#AAAAFF',
	fillOpacity: 0.3,
    });

    rectangle.setMap(map);


//google.maps.event.addDomListener(window, 'load', initialize);
google.maps.event.addListener(rectangle, 'bounds_changed', rebound);

function rebound()
{
	heatmap.setMap(null);
	makeHeatMap();
}

function makeHeatMap()
{
	var ne = rectangle.getBounds().getNorthEast();
	var sw = rectangle.getBounds().getSouthWest();

	jQuery.ajax('http://www.socialcrunch.co/ajax.php?ne='+ne+'&sw='+sw).done(function(data) {
		alert("Got data, see log");
		console.log(data);
	});
// here's where i bailed
}

  </script>
</body>
</html>

