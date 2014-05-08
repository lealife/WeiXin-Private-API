<?php
require "config.php";
require "include/WeiXin.php";

$weiXin = new WeiXin($G_CONFIG['weiXin']);

$testFakeId = "160628835";
$testFakeId2 = "2158620204";

echo "<pre>";
echo '发送消息</br>';
print_r($weiXin->send($testFakeId, "test"));

echo '发送图片, 图片必须要先在公共平台中上传, 得到图片Id</br>';
print_r($weiXin->sendImage($testFakeId, "10000001"));

echo '批量发送</br>';
print_r($weiXin->batSend(array($testFakeId, $testFakeId2), "test batSend"));

echo '得到用户信息</br>';
print_r($weiXin->getUserInfo($testFakeId));

echo '得到最新消息</br>';
$latestMsgs = $weiXin->getLatestMsgs();
print_r($latestMsgs);
if ($latestMsgs) {
	echo $latestMsgs[0]['fakeid'],'</br>';
	echo $latestMsgs[0]['date_time'],'</br>';
	echo $latestMsgs[0]['content'],'</br>';
}

// 这个是可以得到的 只是 getLatestMsgs 这个抓取不太稳定，不能频繁去抓取
// echo '得到用户Fakeid</br>';
// $userFakeId = $weiXin->getLatestMsgByCreateTimeAndContent($createTime, $content);
// print_r($userFakeId);

