<?php
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

$query=$pdo->prepare('SELECT isp FROM "public"."17mon" where country=\'中国\'  AND "province" NOT LIKE \'%香港%\' AND "province" NOT LIKE \'%台湾%\'  AND "province" NOT LIKE \'%澳门%\' and isp like \'%/%\' GROUP BY isp;');
$query->execute();
while($row = $query->fetch(PDO::FETCH_ASSOC))
{
    $isp = $row['isp'];
	$ip=$pdo->query('SELECT \'0.0.0.0\'::inet + "ipStart" FROM "17mon" where isp=\'' . $isp . '\'')->fetchColumn();
	$taobaoData = file_get_contents("http://ip.taobao.com/service/getIpInfo.php?ip={$ip}");
	$taobaoJson = json_decode($taobaoData, true);
	$taobaoIsp = $taobaoJson['data']['isp'];
	if($taobaoIsp != '') {
		$id = $pdo->query('SELECT id FROM "isp" where zh=\'' . $taobaoIsp . '\'')->fetchColumn();
		if( empty($id) ) {
			$pdo->query('INSERT INTO "isp" (zh) VALUES (\'' . $taobaoIsp . '\')');
		}
	}
	usleep(200000);
	echo $isp . $ip . "\t" . $taobaoIsp . "\n";
}



