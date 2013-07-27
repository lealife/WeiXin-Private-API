<?php
require($G_ROOT. "/include/Snoopy.php");

/**
 * 微信公共平台的私有接口
 * 思路: 模拟登录, 再去调用私有web api
 *
 * 功能: 发送信息, 批量发送(未测试), 得到用户信息, 得到最近信息, 解析用户信息(fakeId)
 *
 * @author life lifephp@gmail.com https://github.com/lealife/WeiXin-Private-API
 * 
 * 参考了gitHub微信的api: https://github.com/zscorpio/weChat, 在此基础上作了修改和完善
 * 	(该接口经测试不能用, 因为webToken的问题, 还有cookie生成的问题, 本接口已修复这些问题)
 */
class WeiXin
{
	private $token; // 公共平台申请时填写的token
	private $account;
	private $password;

	// 每次登录后将cookie, webToken缓存起来, 调用其它api时直接使用
	// 注: webToken与token不一样, webToken是指每次登录后动态生成的token, 供难证用户是否登录而用
	private $cookiePath; // 保存cookie的文件路径
	private $webTokenPath; // 保存webToken的文路径

	// 缓存的值
	private $webToken; // 登录后每个链接后都要加token
	private $cookie;

	// 构造函数
	public function __construct($config) {
		if(!$config) {
			exit("error");
		}

		// 配置初始化
		$this->account = $config['account'];
		$this->password = $config['password'];
		$this->cookiePath = $config['cookiePath'];
		$this->webTokenPath = $config['webTokenPath'];

		// 读取cookie, webToken
		$this->getCookieAndWebToken();
	}

	// 登录, 并获取cookie, webToken

	/**
	 * 模拟登录获取cookie和webToken
	 */
	public function login() {
		$snoopy = new Snoopy; 
		$submit = "http://mp.weixin.qq.com/cgi-bin/login?lang=zh_CN";
		$post["username"] = $this->account;
		$post["pwd"] = md5($this->password);
		$post["f"] = "json";
		$snoopy->submit($submit, $post);

		// 得到cookie 
		$cookie = '';
		foreach($snoopy->headers as $key => $value) {
			$value = trim($value);
			if(strpos($value,'Set-Cookie: ') || strpos($value,'Set-Cookie: ') === 0) {
				$tmp = str_replace("Set-Cookie: ", "", $value);
				$tmp = str_replace("Path=/", "", $tmp);
				$cookie .= $tmp. ";"; // 加";" httpOnly
			}
		}

		// 保存cookie
		$this->cookie = $cookie;
		file_put_contents($this->cookiePath, $cookie);

		// 得到token
		$this->getWebToken($snoopy->results);

		return true;
	}

	/**
	 * 登录后从结果中解析出webToken
	 * @param  [String] $logonRet
	 * @return [Boolen]
	 */
	private function getWebToken($logonRet) {
		$logonRet = json_decode($logonRet, true);
		$msg = $logonRet["ErrMsg"]; // /cgi-bin/indexpage?t=wxm-index&lang=zh_CN&token=1455899896
		$msgArr = explode("&token=", $msg);
		if(count($msgArr) != 2) {
			return false;
		} else {
			$this->webToken = $msgArr[1];
			file_put_contents($this->webTokenPath, $this->webToken);
			return true;
		}
	}

	/**
	 * 从缓存中得到cookie和webToken
	 * @return [type]
	 */
	public function getCookieAndWebToken() {
		$this->cookie = file_get_contents($this->cookiePath);
		$this->webToken = file_get_contents($this->webTokenPath);

		// 如果有缓存信息, 则验证下有没有过时, 此时只需要访问一个api即可判断
		if($this->cookie && $this->webToken) {
			$send_snoopy = new Snoopy;
			$send_snoopy->rawheaders['Cookie'] = $this->cookie;
			$submit = "http://mp.weixin.qq.com/cgi-bin/getcontactinfo?t=ajax-getcontactinfo&lang=zh_CN&token={$this->webToken}&fakeid=";
			$send_snoopy->submit($submit, array());
			$result = json_decode($send_snoopy->results, 1);

			if(!$result) {
				return $this->login();
			} else {
				return true;
			}
		} else {
			return $this->login();
		}
	}

	// 其它API, 发送, 获取用户信息

	/**
	 * 主动发消息
	 * @param  string $id      用户的fakeid
	 * @param  string $content 发送的内容
	 * @return [type]          [description]
	 */
	public function send($id, $content)
	{
		$send_snoopy = new Snoopy; 
		$post = array();
		$post['tofakeid'] = $id;
		$post['type'] = 1;
		$post['content'] = $content;
		$post['ajax'] = 1;
        $send_snoopy->referer = "http://mp.weixin.qq.com/cgi-bin/singlemsgpage?fromfakeid={$id}&msgid=&source=&count=20&t=wxm-singlechat&lang=zh_CN&token={$this->webToken}";
		$send_snoopy->rawheaders['Cookie']= $this->cookie;
		$submit = "http://mp.weixin.qq.com/cgi-bin/singlesend?t=ajax-response&token={$this->webToken}";
		$send_snoopy->submit($submit, $post);
		// {"ret":"0", "msg":"ok"}
		return json_decode($send_snoopy->results);
	}

	/**
	 * 批量发送(可能需要设置超时)
	 * @param  [type] $ids     用户的fakeid集合,逗号分割
	 * @param  [type] $content [description]
	 * @return [type]          [description]
	 */
	public function batSend($ids,$content)
	{
		$ids_array = explode(",", $ids);
		$result = array();
		foreach ($ids_array as $key => $value) {
			$send_snoopy = new Snoopy; 
			$post = array();
			$post['type'] = 1;
			$post['content'] = $content;
			$post['ajax'] = 1;
            $send_snoopy->referer = "http://mp.weixin.qq.com/cgi-bin/singlemsgpage?fromfakeid={$value}&msgid=&source=&count=20&t=wxm-singlechat&lang=zh_CN&token={$this->webToken}";
			$send_snoopy->rawheaders['Cookie']= $this->cookie;
			$submit = "http://mp.weixin.qq.com/cgi-bin/singlesend?t=ajax-response&token={$this->webToken}";
			$post['tofakeid'] = $value;
			$send_snoopy->submit($submit,$post);
			$tmp = $send_snoopy->results;
			array_push($result, $tmp);
		}
		return $result;
	}	

	/**
	 * 获取用户的信息
	 * @param  string $fakeId 用户的fakeId
	 * @return [type]     [description]
	 */
	public function getUserInfo($fakeId)
	{
		$send_snoopy = new Snoopy; 
		$send_snoopy->rawheaders['Cookie']= $this->cookie;
		$submit = "http://mp.weixin.qq.com/cgi-bin/getcontactinfo?t=ajax-getcontactinfo&lang=zh_CN&token={$this->webToken}&fakeid=$fakeId";
		$send_snoopy->submit($submit, array());
		$result = json_decode($send_snoopy->results, 1);
		if(!$result) {
			$this->login();
		}
		return $result;
	}

	/*
	 得到最近发来的信息
    [0] => Array
        (
            [id] => 189
            [type] => 1
            [fileId] => 0
            [hasReply] => 0
            [fakeId] => 1477341521
            [nickName] => lealife
            [remarkName] => 
            [dateTime] => 1374253963
        )
        [ok]
	 */
	public function getLatestMsgs($page = 0) {
		$send_snoopy = new Snoopy; 
		// frommsgid是最新一条的msgid
		$frommsgid = 100000;
		$offset = 50 * $page;
		$submit = "http://mp.weixin.qq.com/cgi-bin/getmessage?t=ajax-message&lang=zh_CN&count=50&timeline=&day=&star=&frommsgid=$frommsgid&cgi=getmessage&offset=$offset";
		$send_snoopy->rawheaders['Cookie'] = $this->cookie;
		$send_snoopy->submit($submit, array("token" => $this->webToken, "ajax" => 1));
		$result = $send_snoopy->results;
		$result = json_decode($result, 1);

		return $result;
	}

	// 解析用户信息
	// 当有用户发送信息后, 如何得到用户的fakeId?
	// 1. 从web上得到最近发送的信息
	// 2. 将用户发送的信息与web上发送的信息进行对比, 如果内容和时间都正确, 那肯定是该用户
	// 		实践发现, 时间可能会不对, 相隔1-2s或10多秒也有可能, 此时如果内容相同就断定是该用户
	// 		如果用户在时间相隔很短的情况况下输入同样的内容很可能会出错, 此时可以这样解决: 提示用户输入一些随机数.
	
	/**
	 * 通过时间 和 内容 双重判断用户
	 * @param  [type] $createTime
	 * @param  [type] $content
	 * @return [type]
	 */
	public function getLatestMsgByCreateTimeAndContent($createTime, $content) {
		$lMsgs = $this->getLatestMsgs(0);

		// 最先的数据在前面

		$contentMatchedMsg = array();
		foreach($lMsgs as $msg) {
			// 仅仅时间符合
			if($msg['dateTime'] == $createTime) {
				// 内容+时间都符合
				if($msg['content'] == $content) { 
					return $msg;
				}

			// 仅仅是内容符合
			} else if($msg['content'] == $content) {
				$contentMatchedMsg[] = $msg;
			}
		}

		// 最后, 没有匹配到的数据, 内容符合, 而时间不符
		// 返回最新的一条
		if($contentMatchedMsg) {
			return $contentMatchedMsg[0];
		}

		return false;
	}
}
