<?php

// here we receive and process MQTT
// https://www.cloudmqtt.com/docs-php.html
// https://github.com/bluerhinos/phpMQTT

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

// connect to mqtt
require("phpMQTT.php");
$mqtt = new phpMQTT("rpi1.local", 1883, "rcvinflux"); //Change client name to something unique
// rpi1.local is 192.168.2.100

define ("PRODUCTION", "True");
// comment out to go to test mode

// array with sensor values for Domoticz.
// 0 means: do not send
$idxlookup = array (
	array (('des') => 'inTemp', ('val') => 3),
	array (('des') => 'inHumi', ('val') => 5),
	array (('des') => 'RelPress', ('val') => 7),
	array (('des') => 'outTemp', ('val') => 4),
	array (('des') => 'outHumi', ('val') => 6),
	array (('des') => 'avgwind', ('val') => 9),
	array (('des') => 'uv', ('val') => 10),
	array (('des') => 'solarrad', ('val') => 11),
	array (('des') => 'rainofhourly', ('val') => 8)
);


if (!$mqtt->connect()) {
	exit(1);
}

  $topics['#'] = array("qos" => 0, "function" => "procmsg");
//	$topics['domoticz/in'] = array("qos" => 0, "function" => "procmsg");

	// i.e. subscribe to everything
	$mqtt->subscribe($topics, 0);

	while($mqtt->proc()){
  }

  $mqtt->close();


function procmsg($topic, $msg){
		// echo "Msg Recieved: " . date("r") . "\n";
		// echo "Topic: {$topic}\n\n";
		// echo "\t$msg\n\n";

    // depending on topic, send to correct decoder
		$pos = strpos($topic, "otmonitor/");
		if ($pos === false) {
			// check for domoticz/in
			$pos = strpos($topic, "domoticz/in");
			if ($pos === false) {
				// check for domoticz out
				$pos = strpos($topic, "domoticz/out");
				if ($pos === false) {
					// we don't handle this yet

				} else {
					handle_power($msg);
				}

			} else {
				handle_weather($msg);
			}
		} else {
			// length of string otmonitor/ is 10
			handle_otmonitor(substr($topic,$pos+10),$msg);
		}
}

function handle_power($msg) {
	// echo "Handle power: {$msg}\n\n";
  global $database;

	$arr = json_decode ($msg,true);
	if (strpos($arr["name"],"Electricity") !== false) {
		// var_dump($arr);
		sscanf ($arr["svalue1"], "%d", $value1);
		sscanf ($arr["svalue2"], "%d", $value2);
		sscanf ($arr["svalue5"], "%d", $value5);
		$timestamp = time();

		// create an array of points
		$points = array(
			new Point(
				'electricity_dag', // name of the measurement
				(double) $value1, // the measurement value
				[],
				[],
				$timestamp // Time precision has to be set to seconds!
			),
			new Point(
				'electricity_nacht', // name of the measurement
				(double) $value2, // the measurement value
				[],
				[],
				$timestamp // Time precision has to be set to seconds!
			),
			new Point(
				'verbruik', // name of the measurement
				(double) $value5, // the measurement value
				[],
				[],
				$timestamp // Time precision has to be set to seconds!
			)
		);

		// var_dump ($points);
		// we are writing unix timestamps, which have a second precision
		$result = $database->writePoints($points, Database::PRECISION_SECONDS);
	} elseif (strpos($arr["name"],"Gas") !== false) {
		// var_dump($arr);
		sscanf ($arr["svalue1"], "%d", $value1);
		$timestamp = time();

		// create an array of points
		$points = array(
			new Point(
				'gas', // name of the measurement
				(double) $value1/1000, // the measurement value
				[],
				[],
				$timestamp // Time precision has to be set to seconds!
			)
		);

		// var_dump ($points);
		// we are writing unix timestamps, which have a second precision
		$result = $database->writePoints($points, Database::PRECISION_SECONDS);
	}
}

function handle_weather($msg) {
	global $idxlookup;
	global $database;
	// echo "Handle weather: {$msg}\n\n";

	$arr = json_decode ($msg,true);
	// var_dump($arr);
	if ($arr["idx"]<>0) {
		$key = array_search ($arr["idx"], array_column ($idxlookup, 'val'));
		$measure = $idxlookup[$key]['des'];
		$svalue = $arr["svalue"]; // this is a string... or a float
		if ($arr["idx"] != 11) {
			$nvalue = $arr["nvalue"];
		}
		$timestamp = time();

		switch ($arr["idx"]) {
			case 3:
			case 4:
			case 7:
			case 10:
			case 11:
			sscanf ($svalue, "%f", $value);
				$points = array(
					new Point(
						$measure, // name of the measurement
						$value, // the measurement value
						[],
						[],
						$timestamp // Time precision has to be set to seconds!
					)
				);
				break;
			case 5:
			case 6:
				$value = $nvalue;
				$points = array(
					new Point(
						$measure, // name of the measurement
						$value, // the measurement value
						[],
						[],
						$timestamp // Time precision has to be set to seconds!
					)
				);
				break;
			case 8:
					// rain daily and yearly
					sscanf ($svalue, "%f;%f", $value1, $value2);
					$points = array(
						new Point(
							'raindaily', // name of the measurement
							$value1, // the measurement value
							[],
							[],
							$timestamp // Time precision has to be set to seconds!
						),
						new Point(
							'rainyearly', // name of the measurement
							$value2, // the measurement value
							[],
							[],
							$timestamp // Time precision has to be set to seconds!
						)
					);
					break;
			case 9:
						// domoticz/in {"idx":9,"nvalue":0,"svalue":"323;NW;23.8888908;56.1111156;6.7;4.9895776195896"}
						// Wind direction in compass degrees
						// Wind direction in named
						// Average windspeed * 10
						// Gustspeed * 10
						// Outdoor temperature
						// Windchill temperature
						sscanf ($svalue, "%D", $winddir);
						// Scan and delete everything up to the second ';'
						$i = strpos ($svalue, ';');
						$str = substr ($svalue, $i+1);
						$i = strpos ($str, ';');
						$svalue = substr ($str, $i+1);
						// echo "Fixed string {$svalue}\n";
						sscanf ($svalue, "%f;%f;%f;%f", $avgwind, $gustspeed, $temp, $windchill);

						$points = array(
							new Point(
								'winddir', // name of the measurement
								(float)$winddir, // the measurement value
								[],
								[],
								$timestamp // Time precision has to be set to seconds!
							),
							new Point(
								'avgwind', // name of the measurement
								$avgwind, // the measurement value
								[],
								[],
								$timestamp // Time precision has to be set to seconds!
							),
							new Point(
								'gustspeed', // name of the measurement
								$gustspeed, // the measurement value
								[],
								[],
								$timestamp // Time precision has to be set to seconds!
							),
							new Point(
								'windchill', // name of the measurement
								$windchill, // the measurement value
								[],
								[],
								$timestamp // Time precision has to be set to seconds!
							)
						);
						break;
				}
		}

		// var_dump ($points);
		// we are writing unix timestamps, which have a second precision
		$result = $database->writePoints($points, Database::PRECISION_SECONDS);
}

function handle_otmonitor($topic,$msg) {
	// echo "Handle otmonitor: {$topic}: {$msg}\n\n";
	// we are really only interested in a few values
	global $database;
	$measures = array ("controlsetpoint", "roomtemperature", "setpoint");

	if (in_array($topic,$measures)) {
		$arr = json_decode ($msg,true);
		// var_dump($arr);
		$meas_value = $arr["value"];
		$meas_ts = $arr["timestamp"];

		// create an array of points
		$points = array(
			new Point(
				$topic, // name of the measurement
				$meas_value, // the measurement value
				[],
				[],
				$meas_ts // Time precision has to be set to seconds!
			)
		);

		// var_dump ($points);
		// we are writing unix timestamps, which have a second precision
		$result = $database->writePoints($points, Database::PRECISION_MICROSECONDS);
	}
}

?>
