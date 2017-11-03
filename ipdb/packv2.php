<?php
/*

blc.csv 
net,continent,country,area,province,city,isp
1.0.1.0/24,13
1.0.2.0/23,13
..
loc.csv
1,beijingdx
2,tianjindx
..

uint_8  ----  0-255

HEAD {
    uint32_t version; // 存储版本号
    int32_t rec_size; // 存储记录数
    int16_t  dc_size; //datacenter记录数
}

RECORD {
	uint32_t  ip; //存储ip
	uint8_t   mask; //掩码
}

continent {
	uint8_t id;
	unsigned char name[2]; //只存储2位code https://www.countrycallingcodes.com/iso-country-codes/
}

country {
	uint8_t id;
	unsigned char name[2]; //https://www.iso.org/obp/ui/#search
}

area {
	uint8_t id;
	unsigned char name[64];
}

province {
	uint8_t id;
	unsigned char name[64];
}

city {
	uint8_t id;
	unsigned char name[64];
}

isp {
	uint16_t id;
	unsigned char name[64];
}
 */

$fp = fopen(__DIR__ . '/db.dat', 'wb');

$ids = array();

$rec_total = 0;
$dc_total = 0;

$pack = array();

$blc = file_get_contents('blc.csv');
foreach(explode("\n", $blc) as $b) {
	if(empty($b)) break;
	list($cidr, $id) = explode("\t", $b);
	list($ip, $mask) = explode("/", $cidr);
	//echo "$ip  --- $mask\n";
	++$rec_total;
	$longip = ip2long($ip);
	$pack['record'] .= pack("NCn", $longip, $mask, $id);
	
}

$loc = file_get_contents('loc.csv');

foreach(explode("\n", $loc) as $l) {
	if(empty($l)) break;
	list($id, $dcn) = explode("\t", $l, 2);
	++$dc_total;
	$pack['datacenter'] .= pack("na32", $id, $dcn);
	
}

$pack['head'] = pack("NNn", 20150130, $rec_total, $dc_total);

fwrite($fp, $pack['head']. $pack['record'] . $pack['datacenter']);
fclose($fp);


