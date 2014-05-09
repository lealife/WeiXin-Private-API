<?php
require "config.php";
require "include/WeiXin.php";

$weiXin = new WeiXin($G_CONFIG['weiXin']);

$testFakeId = "160628835";
$testFakeId2 = "2158620204";

echo "<pre>";
// echo '发送消息</br>';
// print_r($weiXin->send($testFakeId, "test"));

// echo '发送图片, 图片必须要先在公共平台中上传, 得到图片Id</br>';
// print_r($weiXin->sendImage($testFakeId, "10000001"));

// echo '批量发送</br>';
// print_r($weiXin->batSend(array($testFakeId, $testFakeId2), "test batSend"));

echo '得到用户信息</br>';
print_r($weiXin->getUserInfo($testFakeId));

echo '得到最新消息，默认获取第一页消息</br>';
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

// echo "开启高级模式</br>";
// $openAdvancedSwitch = $weiXin->openAdvancedSwitch('1');
// // print_r($openAdvancedSwitch);
// echo $openAdvancedSwitch['base_resp']['err_msg'] == 'ok' ? '开启成功' : '开启失败','</br>';

// echo "绑定</br>";
// $backurl = '你服务器配置的URL';
// $token = '你服务器配置的Token';
// $setCallbackProfile = $weiXin->setCallbackProfile($backurl, $token);
// // print_r($setCallbackProfile);
// echo $setCallbackProfile['ret'] === '0' ? '绑定成功' : '绑定失败','</br>';

// echo "OAuth2.0网页授权, 此功能是给服务号使用的</br>";
// $domain = '授权回调页面域名';  // 注意是不带 'http://' 的
// $changeOAuthDomain = $weiXin->changeOAuthDomain($domain);
// // print_r($changeOAuthDomain);
// echo $changeOAuthDomain['base_resp']['err_msg'] == 'ok' ? '授权回调网址修改成功' : '授权回调网址修改失败','</br>';

// echo "获取微信基本资料和认证信息appid，appkey 等</br>";
// $getWxInfo = $weiXin->getWxInfo();
// print_r($getWxInfo);