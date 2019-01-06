<?php

//区域
$regionname = [
    610001 => '陕西省本级',
    6101 => '西安市',
    6102 => '铜川市',
    6103 => '宝鸡市',
    6104 => '咸阳市',
    6105 => '渭南市',
    6106 => '延安市',
    6107 => '汉中市',
    6108 => '榆林市',
    6109 => '安康市',
    6110 => '商洛市',
    6111 => '杨凌示范区',
    6169 => '西咸新区',
];

//采购目录
$purcatalogname = [
    2 => '服务类',
    3 => '工程类',
    1 => '货物类',
];

//采购方式
$purmethodname = [
    1 => '公开招标',
    2 => '邀请招标',
    3 => '竞争性谈判',
    4 => '询价',
    6 => '竞争性磋商',
    5 => '单一来源',
];

//获取startStr和endStr中间的内容
function getStringBetween($string, $start, $end)
{
    $string = ' '.$string;
    $ini = strrpos($string, $start);
    if ($ini == 0) {
        return false;
    }
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;

    return substr($string, $ini, $len);
}

function getList($purcatalogguid, $regionguid, $purmethod, $page = 1)
{
    $postUrl = 'http://www.ccgp-shaanxi.gov.cn/notice/noticeaframe.do?noticetype=3';

    $postData = http_build_query(
    array(
        'page.pageNum' => $page,
        "parameters['purcatalogguid']" => $purcatalogguid,
        "parameters['title']" => '',
        "parameters['startdate']" => '2018-10-10',
        "parameters['enddate']" => '2019-01-10',
        "parameters['regionguid']" => $regionguid,
        "parameters['projectcode']" => '',
        'province' => '',
        "parameters['purmethod']" => $purmethod,
    )
);

    $opts = array('http' => array(
        'method' => 'POST',
        'header' => 'Content-type: application/x-www-form-urlencoded',
        'content' => $postData,
    ),
);

    $context = stream_context_create($opts);

    $result = file_get_contents($postUrl, false, $context);

    return $result;
}

function getUrlFromListContent($content)
{
    preg_match_all('/href=["\']?([^"\'>]+)["\']?/', $content, $matches);

    return array_filter($matches[1], function ($v) {return substr($v, 0, 4) == 'http'; });
}

//file_put_contents('listempty.tmp', getList(1, 63001, 1, 1));
//step 1 获取总页数

// $recordTotal = intval(getStringBetween($content, '<a href="javascript:toPage(\'\',', ');"><span class="glyphicon glyphicon-fast-forward">'));

//  preg_match_all('/href=["\']?([^"\'>]+)["\']?/', $content, $matches); //匹配href URL
//  print_r($matches[1]);
//  $arr = array_filter($matches[1], function ($v) {return substr($v, 0, 4) == 'http'; });
//  print_r($arr);
//  exit;

$result = '';
foreach ($regionname as $regionnameKey => $regionnameValue) {
    foreach ($purcatalogname as $purcatalognameKey => $purcatalognameValue) {
        foreach ($purmethodname as $purmethodnameKey => $purmethodnameValue) {
            $currentPage = 1;
            $listContent = getList($purcatalognameKey, $regionnameKey, $purmethodnameKey, $currentPage);

            $recordTotal = intval(getStringBetween($listContent, '<a href="javascript:toPage(\'\',', ');"><span class="glyphicon glyphicon-fast-forward">'));
            if ($recordTotal) {
                $pageUrls = getUrlFromListContent($listContent);
                foreach ($pageUrls as $url) {
                    if (strpos(file_get_contents('result.csv'), $url)) {
                        continue;
                    }
                    $ctx = stream_context_create(array('http' => array(
                            'timeout' => 5,
                        ),
                    ));
                    $pageContent = file_get_contents($url, false, $ctx);
                    if (empty($pageContent)) {
                        echo "timeout: $url\n";
                        continue;
                    }
                    $projectTitle = getStringBetween($pageContent, '<h1 class="content-tit">', '</h1>');
                    $publishDate = getStringBetween($pageContent, '<em class="red">', '</em>');
                    $projectCode = getStringBetween($pageContent, '采购项目编号：', '</p>');
                    $result = sprintf("%s\t%s\t%s\t%s\t%s\t%s\t%s\n", $projectCode, $projectTitle, $regionnameValue, $purcatalognameValue, $purmethodnameValue, $publishDate, $url);
                    echo $result;
                    file_put_contents('result.csv', $result, FILE_APPEND);
                }
            }
        }
    }
}
//file_put_contents('page.tmp', file_get_contents('http://www.ccgp-shaanxi.gov.cn:80/notice/noticeDetail.do?noticeguid=8a85be3368220b89016826f83ff226d0'));
