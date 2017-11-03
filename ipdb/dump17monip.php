<?php
$fp = fopen(__DIR__ . '/17monipdb-201707.dat', 'rb');
$offset = unpack('Nlen', fread($fp, 4));
$index = fread($fp, $offset['len'] - 4);
print_r($offset);
$max_comp_len = $offset['len'] - 1028;
echo $max_comp_len . "\n";

$fpw = fopen(__DIR__ . '/17mon-201707.txt', 'wb');

$s2 = 0;

for ($start =  1024; $start < $max_comp_len; $start += 8)
{
	$s1 = unpack('Nlen', $index{$start} . $index{$start + 1} . $index{$start + 2} . $index{$start + 3});
	fwrite($fpw, $s2. "\t". $s1['len'] . "\t");
        $index_offset = unpack('Vlen', $index{$start + 4} . $index{$start + 5} . $index{$start + 6} . "\x0");
        $index_length = unpack('Clen', $index{$start + 7});
 

        fseek($fp, $offset['len'] + $index_offset['len'] - 1024);
        fwrite($fpw, fread($fp, $index_length['len']) . "\n");
        $s2 = $s1['len'] + 1;
        
	//break;
}

        


//fseek($fp, $offset['len']);
//$addrs = fread($fp, $max_comp_len);
//file_put_contents('addrs.txt', $addrs);
//print_r($index);

