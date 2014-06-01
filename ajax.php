<?php
//error_reporting(0);
ini_set('display_errors', 'On');
ini_set('display_startup_errors', 'On');

$bounds = array( array(39.937782, -86.344867), array(39.632271, -85.939060));
$bounding = true;

$search_token = 'police_cache_key';

// Check for this search in memcached
$memcacheObj = new Memcached;
$memcacheObj->addServer('127.0.0.1','11211');
$cache = $memcacheObj->get($search_token);

//(39.937782,%20-86.03587701660149)&sw=(39.7537933051826,%20-86.29954839648445)

if( !isset($cache) or !is_array($cache) or !count($cache))
{
	$final = array();
	$dataset_string = file_get_contents('police_tickets.json');
	$dataset = json_decode($dataset_string, true);

	foreach( $dataset['data'] as $row )
	{
		if( ! isset($row[15])) continue;
		if( $row[15][1] == 0 ) continue;
		if( $row[15][2] == 0 ) continue;
		$geo_y = $row[15][1];
		$geo_x = $row[15][2];
		$data = array( 'coord'=>array((float)$geo_y,(float)$geo_x)); //, 'offense'=>ucwords($row[11]));
		$final[] = $data;
	}
	unset($dataset);
	$data = $final;
// This is often too large for memcache, need to cut it up
	$memcacheObj->set($search_token, $data, 300);
	$used_cache = false;
} else {
	$used_cache = true;
	$data = $cache;
}

// Filter 
/*
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
*/

$count = count($data);
echo "<Pre>";
Echo "Count: {$count}\n";
if($used_cache) echo "Use Cache\n";
if(! $used_cache) echo "Didn't Cache\n";
print_r($data);
exit;


