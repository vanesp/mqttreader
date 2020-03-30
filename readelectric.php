<?php

// Read the domoticz SQLite database and store time series values as points in
// in the Influx Database

// Electricity first

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

// eerst de dagteller
$counter = 0;
$result = $db->query('select Counter1, Counter3, datetime(Date) from MultiMeter_Calendar where DeviceRowId = 1 order by date');
while ($row = $result->fetchArray()) {
	// var_dump($row);
	$timestamp = strtotime ($row[2], 0); // vertaal datum naar tiemstamp t.o.v. tijd 0
	$dagverbruik = $row[0] + $row[1];			// totaal stand electriciteitstellers
	$verbruik = $dagverbruik - $counter;	// wat er t.o.v. gisteren verbruikt is
	$counter = $dagverbruik;							// sla op wat er gisteren stond
	echo "electricity_dag " . $row[0] . " nacht " . $row[1] ." - " . $verbruik ." : ". $timestamp . "\n";

	// create an array of points
	$points = array(
		new Point(
			'electricity_dag', // name of the measurement
			(double) $row[0], // the measurement value
			[],
			[],
			$timestamp // Time precision has to be set to seconds!
		),
		new Point(
			'electricity_nacht', // name of the measurement
			(double) $row[1], // the measurement value
			[],
			[],
			$timestamp // Time precision has to be set to seconds!
		),
		new Point(
			'verbruik', // name of the measurement
			(double) $verbruik, // the measurement value
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
