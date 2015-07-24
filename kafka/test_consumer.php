<?php

$kafka = new Kafka(
    '117.27.249.10:9092,117.27.249.11:9092'/*,
    array(
        Kafka::LOGLEVEL         => Kafka::LOG_ON,//while in dev, default is Kafka::LOG_ON
        Kafka::CONFIRM_DELIVERY => Kafka::CONFIRM_OFF,//default is Kafka::CONFIRM_BASIC
        Kafka::RETRY_COUNT      => 1,//default is 3
        Kafka::RETRY_INTERVAL   => 25,//default is 100
    )*/
);

$topic = 'dnslog';

$partitions = $kafka->getPartitionsForTopic($topic);

print_r($partitions);
//use it to OPTIONALLY specify a partition to consume from
//if not, consuming IS slower. To set the partition:
$kafka->setPartition($partitions[1]);//set to first partition
//then consume, for example, starting with the first offset, consume 20 messages
$msg = $kafka->consume($topic, 9016, 100);
//msg key is offset
var_dump($msg);//dumps array of messages
