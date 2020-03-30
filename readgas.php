<?php

// Read the domoticz SQLite database and store time series values as points in
// in the Influx Database

// Gas second

require 'vendor/autoload.php';


// access influx Database
// uses $ composer require influxdb/influxdb-php
$host = 'localhost';
$port = '8086';
$dbname = 'mydb';

use InfluxDB\Client;
use InfluxDB\Driver\Guzzle;
use InfluxDB\Exception;
use InfluxDB\Point;
use InfluxDB\Database;
use InfluxDB\ResultSet;

// Open the database
$client = new InfluxDB\Client($host, $port);
$database = $client->selectDB($dbname);

// Open the SQLite Database
$db = new SQLite3('../domoticz.db');

$result = $db->query('select Counter, datetime(Date) from Meter_Calendar where DeviceRowId = 2 order by date');
while ($row = $result->fetchArray()) {
	// var_dump($row);
	$timestamp = strtotime ($row[1], 0); // vertaal datum naar tiemstamp t.o.v. tijd 0
	echo "Gas " . $row[0]/1000 ." : ". $timestamp . "\n";

	// create an array of points
	$points = array(
		new Point(
			'gas', // name of the measurement
			(double) $row[0]/1000, // the measurement value
			[],
			[],
			$timestamp // Time precision has to be set to seconds!
		)
	);

	// var_dump ($points);
	// we are writing unix timestamps, which have a second precision
	$res = $database->writePoints($points, Database::PRECISION_SECONDS);
}

?>
