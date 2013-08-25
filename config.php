<?php

// 全局配置

$G_ROOT = dirname(__FILE__);

$G_CONFIG["weiXin"] = array(
	'account' => '公众平台账号',
	'password' => '密码',
	'cookiePath' => $G_ROOT. '/cache/cookie', // cookie缓存文件路径
	'webTokenPath' => $G_ROOT. '/cache/webToken', // webToken缓存文件路径
);
