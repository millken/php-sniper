<?php

$dbbody = file_get_contents('db.dat');

$unpack = $pack = array();
$pack['head'] = substr($dbbody, 0, 10);

$unpack['head'] = unpack("Nver/Nrec_len/ndc_len", $pack['head']);

$pack['datacenter'] = substr($dbbody, 10 + $unpack['head']['rec_len'] * 7, $unpack['head']['dc_len']);

for($i = 0; $i < $unpack['head']['dc_len']; $i++) {
	$dc_pack = substr($dbbody, 10 + ($unpack['head']['rec_len'] * 7 ) + $i * 34, 34);
	$dc = unpack("nid/a32name", $dc_pack);
	$unpack['datacenter'][$dc['id']] = $dc['name'];

}

$find = "212.4.76.2";
$find_longip = ip2long($find);


$f = 0;
$l = $unpack['head']['rec_len'] - 1;
$n = 0;
//二分查找
while($f <= $l)
{
	$m = intval(($f + $l) / 2);
	if(($f +1) == $m) break;
	//$m = 0;
	++ $n;
	$rec_pack = substr($dbbody, 10 + $m * 7, 7);
	$unpack['record'] = unpack("Nip/Cmask/nid", $rec_pack);
	$ril_start =  $unpack['record']['ip'];
	$ril_end = $ril_start + pow(2 , ( 32 - $unpack['record']['mask'])) - 1;

	if ( $find_longip > $ril_end)$f = $m + 1; //右移
	if ( $find_longip < $ril_start)$l = $m - 1; //左移
	if ($find_longip >= $ril_start && $find_longip <= $ril_end) {
			echo "find $n times ,result[$find] =>" . long2ip($ril_start) . "-" . long2ip($ril_end) . "\t {$unpack['datacenter'][$unpack['record']['id']]}\n";
		break;
	}
}
exit();


