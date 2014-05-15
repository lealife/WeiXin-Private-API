WeiXin-Private-API
==================

-----------------------------------------------------------------------------------------------------------------
多谢[forecho](https://github.com/forecho)维护此项目, 欢迎加入我们一起维护, 谢谢!
-----------------------------------------------------------------------------------------------------------------

微信公众平台私有接口, 发送信息, 发送图片, 得到用户信息, 解析用户fakeId

本接口参考[weChat](https://github.com/zscorpio/weChat), 在此基础上作了修改和完善.

另, 微信公众平台接口改变很频繁, 本程序的修改速度有一定的滞后性, 若有朋友得知接口已变, 烦请告知我, 若能一起维护更好, lifephp@gmail.com, 多谢!

使用:

1. 先在conifg.php中配置公众账号信息:
```php
	$G_CONFIG["weiXin"] = array(
		'account' => '公众平台账号',
		'password' => '密码',
		'cookiePath' => $G_ROOT. '/cache/cookie', // cookie缓存文件路径
		'webTokenPath' => $G_ROOT. '/cache/webToken', // webToken缓存文件路径
	);
```

2. test.php:
```php
	require "config.php";
	require "include/WeiXin.php";

	$weiXin = new WeiXin($G_CONFIG['weiXin']);
	
	$testFakeId = "1477341522";
	$testFakeId2 = "2158620204";
	
	// 发送消息
	print_r($weiXin->send($testFakeId, "test"));
	
	// 发送图片, 图片必须要先在公共平台中上传, 得到图片Id
	print_r($weiXin->sendImage($testFakeId, "10000001"));
	
	// 批量发送
	print_r($weiXin->batSend(array($testFakeId, $testFakeId2), "test batSend"));
	
	// 得到用户信息
	print_r($weiXin->getUserInfo($testFakeId));
	
	// 得到最新消息
	print_r($weiXin->getLatestMsgs());
```
