<?php
//https://www.aikaiyuan.com/12446.html
//https://github.com/metowolf/iplist
//https://github.com/gehui01/ipdb_convert
$fp = fopen('/sdb5/php/php-sniper/ipdb/ipipfreedb/ipipfree.ipdb', 'rb');
$metaLength = unpack('N', fread($fp, 4))[1];
$index = fread($fp, $metaLength);
$meta = json_decode($index, 1);

print_r($meta);
$nodeCount = $meta['node_count'];
$nodeOffset = 4 + $metaLength;

$fileSize = 4 + $metaLength + $meta['total_size'];

define("IPv4", 0x01);
define("IPv6", 0x02);
define("IPv4_start", 96);
define("IPv6_start", 0);
define("IPv6_end", 80);
define("IPv4_size", 32);

function resolve($node)
{
    global $fp,$nodeCount;
    $resolved = $node - $nodeCount + $nodeCount * 8;

    $bytes = read($fp, $resolved, 2);
    $size = unpack('N', str_pad($bytes, 4, "\x00", STR_PAD_LEFT))[1];

    $resolved += 2;

    return read($fp, $resolved, $size);
}

function readNode($node, $index)
{
    global $fp;
    return unpack('N', read($fp, $node * 8 + $index * 4, 4))[1];
}

function read($stream, $offset, $length)
{
    global $nodeOffset;
    if ($length > 0) {
        if (fseek($stream, $offset + $nodeOffset) === 0) {
            $value = fread($stream, $length);
            if (strlen($value) === $length) {
                return $value;
            }
        }

        throw new \Exception("The Database file read bad data.");
    }

    return '';
}
$counter = 0;
function dsfip($stream, $node, $height, $ip_val) 
{
    global $counter;
    global $nodeCount;
    global $fpw;
    // $counter++;
    // if ($counter > 100) {
    //     exit;
    // }
    if ($node == 0) return 0;
    if($height < IPv4_size && $node <= $nodeCount) {
        $left_node = readNode($node, 0);
        $right_node = readNode($node, 1);
        $depth = $height + 1;
        $ip_prefix = $ip_val << 1;
        dsfip($stream, $left_node, $depth, $ip_prefix);
        dsfip($stream, $right_node, $depth, $ip_prefix + 1);
    }elseif($height < IPv4_size && $node > $nodeCount) {
        $temp_ip_val = $ip_val << (IPv4_size - $height);
        $message = resolve($node);
        $ma = explode("\t", $message);
       
        //printf("%s/%d %d / %d  %s\n", long2ip($temp_ip_val), $height, $temp_ip_val, $node, $message);
        fwrite($fpw, sprintf("%s/%d,%s,%s\n", long2ip($temp_ip_val), $height, $ma[0], $ma[1], $ma[2]));
    }elseif($height == IPv4_size && $node > $nodeCount){
      // printf("%d/%d\n", $ip_val, $node);
    }
}

$fpw = fopen(__DIR__ . '/ipdbfree.txt', 'wb');

$node = 0;
$index = 0;
for ($i = IPv6_start; $i < IPv4_start && $node < $nodeCount; $i++) {
    if ($i >= 80) {
        $idx = 1;
    } else {
        $idx = 0;
    }

    $node = readNode($node, $idx);
}

$v4offset = $node;
$height = 0;

dsfip($fp, $v4offset, $height, 0);