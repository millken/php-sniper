<?php
/*

net,continent,country,area,province,city,isp
1.0.1.0/24,13
1.0.2.0/23,13
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

$fp = fopen(__DIR__ . '/dbv2.dat', 'wb');


$params= ['host'=> '127.0.0.1', 'port'=>5432, 'database'=>'ip', 'user'=>'postgres', 'password'=>'admin'];

$conStr = sprintf("pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s", 
        $params['host'], 
        $params['port'], 
        $params['database'], 
        $params['user'], 
        $params['password']);

try {
	$pdo = new \PDO($conStr);
	$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    die($e->getMessage());
}

/*continent*/
$continent = ['total'=>0, 'data'=>''];
$query=$pdo->prepare('SELECT id,code FROM "public"."continent" ORDER BY "id";');
$query->execute();
while($row = $query->fetch(PDO::FETCH_ASSOC))
{
    ++$continent['total'];
	$continent['data'] .= pack("Na2", $row['id'], $row['code']);
}

/* country*/
$country = ['total'=>0, 'data'=>''];
$query=$pdo->prepare('SELECT id,code FROM "public"."country" ORDER BY "id";');
$query->execute();
while($row = $query->fetch(PDO::FETCH_ASSOC))
{
    ++$country['total'];
	$country['data'] .= pack("Na2", $row['id'], $row['code']);
}

/* area*/
$area = ['total'=>0, 'data'=>''];
$query=$pdo->prepare('SELECT id,zh FROM "public"."area" ORDER BY "id";');
$query->execute();
while($row = $query->fetch(PDO::FETCH_ASSOC))
{
    ++$area['total'];
	$area['data'] .= pack("Na64", $row['id'], $row['zh']);
}

/* region*/
$region = ['total'=>0, 'data'=>''];
$query=$pdo->prepare('SELECT id,zh FROM "public"."region" ORDER BY "id";');
$query->execute();
while($row = $query->fetch(PDO::FETCH_ASSOC))
{
    ++$region['total'];
	$region['data'] .= pack("Na64", $row['id'], $row['zh']);
}

/* city*/
$city = ['total'=>0, 'data'=>''];
$query=$pdo->prepare('SELECT id,zh FROM "public"."city" ORDER BY "id";');
$query->execute();
while($row = $query->fetch(PDO::FETCH_ASSOC))
{
    ++$city['total'];
	$city['data'] .= pack("Na64", $row['id'], $row['zh']);
}

/* isp*/
$isp = ['total'=>0, 'data'=>''];
$query=$pdo->prepare('SELECT id,zh FROM "public"."isp" ORDER BY "id";');
$query->execute();
while($row = $query->fetch(PDO::FETCH_ASSOC))
{
    ++$isp['total'];
	$isp['data'] .= pack("Na64", $row['id'], $row['zh']);
}

$net = ['total'=>0, 'data'=>''];
$query=$pdo->prepare('SELECT cidr,continent_id,country_id,area_id,region_id,city_id,isp_id FROM "public"."net" ORDER BY "id";');
$query->execute();
while($row = $query->fetch(PDO::FETCH_ASSOC))
{
    ++$net['total'];
	list($ip, $mask) = explode("/", $row['cidr']);
	$longip = ip2long($ip);
	$net['data'] .= pack("NCNNNNNN", $longip, $mask, $row['continent_id'],$row['country_id'], $row['area_id'],$row['region_id'],$row['city_id'],$row['isp_id']);
}

$head = pack("NNNNNNNN", 20171107, $continent['total'], $country['total'], $area['total'], $region['total'], $city['total'],  $isp['total'], $net['total']);

fwrite($fp, $head. $continent['data'] .$country['data']. $area['data']. $region['data']. $city['data']. $isp['data'] .$net['data'] );
fclose($fp);


