WeiXin-Private-API
==================

微信公用平台私有接口, 发送信息, 得到用户信息, 解析用户fakeId

本接口参考https://github.com/zscorpio/weChat, 在此基础上作了修改和完善.

用法参考test.php:

先在conifg.php中配置公共账号信息:
<pre>
	$G_CONFIG["weiXin"] = array(
		'account' => '公共平台账号',
		'password' => '密码',
		'cookiePath' => $G_ROOT. '/cache/cookie',
		'webTokenPath' => $G_ROOT. '/cache/webToken',
	);	
</pre>

<pre>
	require "config.php";
	require "include/WeiXin.php";

	$weiXin = new WeiXin($G_CONFIG['weiXin']);

	// 发送消息
	$testFakeId = "1477341522";
	print_r($weiXin->send($testFakeId, "test"));

	// 得到用户信息
	print_r($weiXin->getUserInfo($testFakeId));

	// 得到最新消息
	print_r($weiXin->getLatestMsgs());	
</pre>