# IoT MQTT to InfluxDB forwarder #

This tool forwards sensor data from an MQTT broker (Domoticz, OpenTherm) to an InfluxDB instance.

### Raw weather data

As produced by my own script scrape_weather.php

```
Sensor: weather
Values:
inTemp
inHumi
outTemp
outHumi
RelPress
rainofdaily
winddir
avgwind
gustspeed
windchill
UV
solarrad
```

```
domoticz/in {"idx":3,"nvalue":0,"svalue":"18.8"}
Indoor temperature bedroom in C
domoticz/in {"idx":4,"nvalue":0,"svalue":"6.7"}
Outdoor temperature in C
domoticz/in {"idx":5,"nvalue":58,"svalue":"1"}
Indoor humidity bedroom in %
domoticz/in {"idx":6,"nvalue":53,"svalue":"1"}
Outdoor humidity in %
domoticz/in {"idx":7,"nvalue":0,"svalue":"1033.60;5"}
Outdoor pressure in hPa
domoticz/in {"idx":8,"nvalue":0,"svalue":"0.00;165.60"}
Rain daily, Rain yearly

domoticz/in {"idx":9,"nvalue":0,"svalue":"323;NW;23.8888908;56.1111156;6.7;4.9895776195896"}
Wind direction in compass degrees
Wind direction in named
Average windspeed * 10
Gustspeed * 10
Outdoor temperature
Windchill temperature

domoticz/in {"idx":10,"nvalue":0,"svalue":"0;0"}
UV value

domoticz/in {"idx":11,"svalue":"26997.40"}
Light in Lux
```

### Weather data as processed in Domoticz

```
domoticz/out {
	"Battery" : 255,
	"RSSI" : 12,
	"description" : "",
	"dtype" : "Temp",
	"hwid" : "5",
	"id" : "82002",
	"idx" : 3,
	"name" : "Indoor",
	"nvalue" : 0,
	"stype" : "THR128/138, THC138",
	"svalue1" : "18.80",
	"unit" : 1
}

domoticz/out {
	"Battery" : 255,
	"RSSI" : 12,
	"description" : "",
	"dtype" : "Temp",
	"hwid" : "6",
	"id" : "82003",
	"idx" : 4,
	"name" : "Outdoor",
	"nvalue" : 0,
	"stype" : "THR128/138, THC138",
	"svalue1" : "6.70",
	"unit" : 1
}

domoticz/out {
	"Battery" : 255,
	"RSSI" : 12,
	"description" : "",
	"dtype" : "Humidity",
	"hwid" : "7",
	"id" : "82004",
	"idx" : 5,
	"name" : "Indoor Humidity",
	"nvalue" : 58,
	"stype" : "LaCrosse TX3",
	"svalue1" : "1",
	"unit" : 1
}

domoticz/out {
	"Battery" : 255,
	"RSSI" : 12,
	"description" : "",
	"dtype" : "Humidity",
	"hwid" : "8",
	"id" : "82005",
	"idx" : 6,
	"name" : "Outdoor Humidity",
	"nvalue" : 53,
	"stype" : "LaCrosse TX3",
	"svalue1" : "1",
	"unit" : 1
}

domoticz/out {
	"Battery" : 255,
	"RSSI" : 12,
	"description" : "",
	"dtype" : "General",
	"hwid" : "9",
	"id" : "00082006",
	"idx" : 7,
	"name" : "Outdoor",
	"nvalue" : 0,
	"stype" : "Pressure",
	"svalue1" : "1033.60",
	"svalue2" : "5",
	"unit" : 1
}

domoticz/out {
	"Battery" : 255,
	"RSSI" : 12,
	"description" : "",
	"dtype" : "Rain",
	"hwid" : "10",
	"id" : "82007",
	"idx" : 8,
	"name" : "Regen",
	"nvalue" : 0,
	"stype" : "TFA",
	"svalue1" : "0.00",
	"svalue2" : "165.60",
	"unit" : 1
}

domoticz/out {
	"Battery" : 255,
	"RSSI" : 12,
	"description" : "",
	"dtype" : "Wind",
	"hwid" : "11",
	"id" : "82008",
	"idx" : 9,
	"name" : "Wind",
	"nvalue" : 0,
	"stype" : "TFA",
	"svalue1" : "323",
	"svalue2" : "NW",
	"svalue3" : "23.8888908",
	"svalue4" : "56.1111156",
	"svalue5" : "6.7",
	"svalue6" : "4.9895776195896",
	"unit" : 1
}

domoticz/out {
	"Battery" : 255,
	"RSSI" : 12,
	"description" : "",
	"dtype" : "UV",
	"hwid" : "12",
	"id" : "82009",
	"idx" : 10,
	"name" : "UV",
	"nvalue" : 0,
	"stype" : "UVN128,UV138",
	"svalue1" : "0",
	"svalue2" : "0",
	"unit" : 1
}

domoticz/out {
	"Battery" : 255,
	"RSSI" : 12,
	"description" : "",
	"dtype" : "Lux",
	"hwid" : "12",
	"id" : "82010",
	"idx" : 11,
	"name" : "Outdoor Lux",
	"nvalue" : 0,
	"stype" : "Lux",
	"svalue1" : "26997.40",
	"unit" : 1
}
```

### OpenTherm MQTT Messages

```
events/central_heating/otmonitor/boilerwatertemperature {"name": "temp", "type": "float", "value": 31.00, "timestamp": 1585473884443}
events/central_heating/otmonitor/controlsetpoint {"name": "temp", "type": "float", "value": 10.00, "timestamp": 1585468255942}
events/central_heating/otmonitor/chenable {"name": "on", "type": "boolean", "value": false, "timestamp": 1585468255946}
events/central_heating/otmonitor/dhwenable {"name": "on", "type": "boolean", "value": true, "timestamp": 1584976461571}
events/central_heating/otmonitor/fault {"name": "on", "type": "boolean", "value": false, "timestamp": 1584976457492}
events/central_heating/otmonitor/centralheating {"name": "on", "type": "boolean", "value": false, "timestamp": 1585468255998}
events/central_heating/otmonitor/hotwater {"name": "on", "type": "boolean", "value": false, "timestamp": 1585404950050}
events/central_heating/otmonitor/flame {"name": "on", "type": "boolean", "value": false, "timestamp": 1585468256000}
events/central_heating/otmonitor/roomtemperature {"name": "temp", "type": "float", "value": 17.03, "timestamp": 1585474530622}
events/central_heating/otmonitor/setpoint {"name": "temp", "type": "float", "value": 15.00, "timestamp": 1585468260376}
events/central_heating/otmonitor/chsetpoint {"name": "temp", "type": "float", "value": 72.00, "timestamp": 1584976462638}
events/central_heating/otmonitor/thermostat {"name": "connected", "type": "boolean", "value": true, "timestamp": 1544392123320}
```

### Domoticz data structure P1 Meter

```
domoticz/out {
	"Battery" : 255,
	"RSSI" : 12,
	"description" : "",
	"dtype" : "P1 Smart Meter",
	"hwid" : "2",
	"id" : "1",
	"idx" : 1,
	"name" : "Electricity",
	"nvalue" : 0,
	"stype" : "Energy",
	"svalue1" : "14369532",
	"svalue2" : "13779430",
	"svalue3" : "0",
	"svalue4" : "0",
	"svalue5" : "722",
	"svalue6" : "0",
	"unit" : 1
}

svalue1 = day cumulative current kwh
svalue2 = night cumulative current kwh
svalue5 = instantaneous power used
```

### Gas

```
domoticz/out {
	"Battery" : 255,
	"RSSI" : 12,
	"description" : "",
	"dtype" : "P1 Smart Meter",
	"hwid" : "2",
	"id" : "1",
	"idx" : 2,
	"name" : "Gas",
	"nvalue" : 0,
	"stype" : "Gas",
	"svalue1" : "11376913",
	"unit" : 2
}

svalue1 = Gas volume x 1000 m3 (i.e. 11376.913 m3)
```

## Translation to InfluxDB data structure ##

The MQTT topic structure and measurement values are mapped as follows:

- the measurement name becomes the InfluxDB measurement name
- the measurement value is stored as a field named 'value'.

### Example InfluxDB query ###

    show measurements
    select value from windchill;
    select * from electricity_dag limit 10;
    select * from verbruik limit 20;

The data stored in InfluxDB via this forwarder are easily visualized with [Grafana](http://grafana.org/)

## Prerequisites ##

Installation can be done with composer:

    $ composer require influxdb/influxdb-php

## Versioning ##

## Installation ##

Copy file ```rcvinflux.service``` to directory ```/etc/systemd/system/rcvinflux.service```.

To start the server used
    sudo systemctl start rcvinflux

To enable it at boot time
    sudo systemctl enable rcvinflux
