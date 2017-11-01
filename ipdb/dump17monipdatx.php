<?php
$fp = fopen('/run/media/millken/09a7d751-1ed0-48f0-9240-c67c262700dd/millken/mydata4vipday2.datx', 'rb');
$offset = unpack('Nlen', fread($fp, 4));
$index = fread($fp, $offset['len'] - 4);
print_r($offset);
$max_comp_len = $offset['len'] - 262144 - 4;
echo $max_comp_len . "\n";

$fpw = fopen(__DIR__ . '/17mon-201707.txt', 'wb');

$s2 = 0;

$n = 1;
define("WRITE", false);
for ($start =  262144; $start < $max_comp_len; $start += 9)
{
	$s1 = unpack('Nlen', $index{$start} . $index{$start + 1} . $index{$start + 2} . $index{$start + 3});
	$content = $s2. "\t". $s1['len'] . "\t";

        $index_offset = unpack('Vlen', $index{$start + 4} . $index{$start + 5} . $index{$start + 6} . "\x0");
        $index_length = unpack('nlen', $index{$start + 7} . $index{$start + 8});
 

        fseek($fp, $offset['len'] + $index_offset['len'] - 262144);
		$content .= fread($fp, $index_length['len']);
		$s2 = $s1['len'] + 1;
		//if(strpos($content, "中国") === false) continue;
        if(WRITE)fwrite($fpw, $content . "\n");
        ++$n;
	    //if($n > 100000) 	break;
}

echo $n;


//fseek($fp, $offset['len']);
//$addrs = fread($fp, $max_comp_len);
//file_put_contents('addrs.txt', $addrs);
//print_r($index);

