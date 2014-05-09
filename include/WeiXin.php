<?php
require($G_ROOT. "/include/LeaWeiXinClient.php");

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

	private $lea;

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

		$this->lea = new LeaWeiXinClient();

		// 读取cookie, webToken
		$this->getCookieAndWebToken();
	}

	// 登录, 并获取cookie, webToken

	/**
	 * 模拟登录获取cookie和webToken
	 */
	public function login() {
		$url = "https://mp.weixin.qq.com/cgi-bin/login?lang=zh_CN";
		$post["username"] = $this->account;
		$post["pwd"] = md5($this->password);
		$post["f"] = "json";
		$re = $this->lea->submit($url, $post);

		// 保存cookie
		$this->cookie = $re['cookie'];
		file_put_contents($this->cookiePath, $this->cookie);

		// 得到token
		$this->getWebToken($re['body']);

		return true;
	}

	/**
	 * 登录后从结果中解析出webToken
	 * @param  [String] $logonRet
	 * @return [Boolen]
	 */
	private function getWebToken($logonRet) {
		$logonRet = json_decode($logonRet, true);
		$msg = $logonRet["redirect_url"]; // /cgi-bin/indexpage?t=wxm-index&lang=zh_CN&token=1455899896
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
			$re = $this->getUserInfo(1);
			if(is_array($re)) {
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
		$post = array();
		$post['tofakeid'] = $id;
		$post['type'] = 1;
		$post['content'] = $content;
		$post['ajax'] = 1;
		$url = "https://mp.weixin.qq.com/cgi-bin/singlesend?t=ajax-response&token={$this->webToken}";
		$re = $this->lea->submit($url, $post, $this->cookie);
		return json_decode($re['body']);
	}

	/**
	 * 批量发送
	 * @param  [array] $ids     用户的fakeid集合
	 * @param  [type] $content [description]
	 * @return [type]          [description]
	 */
	public function batSend($ids, $content)
	{
		$result = array();
		foreach($ids as $id) {
			$result[$id] = $this->send($id, $content);
		}
		return $result;
	}

	/**
	 * 发送图片
	 * @param  int $fakeId [description]
	 * @param  int $fileId 图片ID
	 * @return [type]         [description]
	 */
	public function sendImage($fakeId, $fileId) {
		$post = array();
		$post['tofakeid'] = $fakeId;
		$post['type'] = 2;
		$post['fid'] = $post['fileId'] = $fileId; // 图片ID
		$post['error'] = false;
		$post['ajax'] = 1;
		$post['token'] = $this->webToken;

		$url = "https://mp.weixin.qq.com/cgi-bin/singlesend?t=ajax-response&lang=zh_CN";
		$re = $this->lea->submit($url, $post, $this->cookie);

		return json_decode($re['body']);
	}

	/**
	 * 获取用户的信息
	 * @param  string $fakeId 用户的fakeId
	 * @return [type]     [description]
	 */
	public function getUserInfo($fakeId)
	{
		$url = "https://mp.weixin.qq.com/cgi-bin/getcontactinfo?t=ajax-getcontactinfo&lang=zh_CN&token={$this->webToken}&fakeid=$fakeId";
		$re = $this->lea->submit($url, array(), $this->cookie);
		$result = json_decode($re['body'], 1);
		if(!$result) {
			$this->login();
		}
		return $result;
	}

	/*
	 得到最近发来的信息 有时候有用 有时候无法获取
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
		// frommsgid是最新一条的msgid
		$frommsgid = 100000;
		$offset = 50 * $page;
		// $url = "https://mp.weixin.qq.com/cgi-bin/getmessage?t=ajax-message&lang=zh_CN&count=50&timeline=&day=&star=&frommsgid=$frommsgid&cgi=getmessage&offset=$offset";
		// $url = "https://mp.weixin.qq.com/cgi-bin/message?t=message/list&count=999999&day=7&offset={$offset}&token={$this->webToken}&lang=zh_CN";
		$url = "https://mp.weixin.qq.com/cgi-bin/message?t=message/list&count=20&day=7&token={$this->webToken}&lang=zh_CN";
		$re = $this->lea->get($url, $this->cookie);
		// print_r($re['body']);

		// 解析得到数据
		//list : ({"msg_item":[{"id":}, {}]})
		$match = array();
		preg_match('/["\' ]msg_item["\' ]:\[{(.+?)}\]/', $re['body'], $match);
		if(count($match) != 2) {
			return "";
		}

		$match[1] = "[{". $match[1]. "}]";

		return json_decode($match[1], true);
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
			if($msg['date_time'] == $createTime) {
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


	// 一键智能绑定第三方平台 先开启高级模式 再开始配置URL和token

	/**
	 * 开启高级模式
	 * @param  [flag] 1是开启 0是关闭
	 * @param  [type] $type=2
	 * @return array
	 * @author forecho [caizhenghai@gmail.com]
	 */
	public function openAdvancedSwitch($flag, $type=2){
		$url = "https://mp.weixin.qq.com/misc/skeyform?form=advancedswitchform&lang=zh_CN";
		$post["flag"] = $flag;
		$post["token"] = $this->webToken;
		$post["type"] = $type;
		$post["f"] = "json";
		$re = $this->lea->submit($url, $post, $this->cookie);

		// print_r($re);
		return json_decode($re['body'], true);
	}


	/**
	 * 设置接管高级模式的地址
	 * @param  [backurl] 开发模式里面的URL
	 * @param  [token] 开发模式Token
	 * @return array
	 * @author forecho [caizhenghai@gmail.com]
	 */
	public function setCallbackProfile($backurl, $token){
		$url = "https://mp.weixin.qq.com/advanced/callbackprofile?t=ajax-response&token={$this->webToken}&lang=zh_CN";
		$post["callback_token"] = $token;
		$post["url"] = $backurl;
		$re = $this->lea->submit($url, $post, $this->cookie);

		// print_r($re);
		return json_decode($re['body'], true);
	}


	/**
	 * OAuth2.0网页授权, 此功能只限服务号使用
	 * @param  [domain] 授权回调页面域名'，注意是不带 'http://' 的
	 * @return array
	 * @author forecho [caizhenghai@gmail.com]
	 */
	public function changeOAuthDomain($domain){
		$url = "https://mp.weixin.qq.com/merchant/myservice";
		$post["domain"] = $domain;
		$post["token"] = $this->webToken;
		$post["lang"] = 'zh_CN';
		$post["random"] = '0.'.time().mt_rand(1111111, 9999999);
		$post["f"] = "json";
		$post["ajax"] = 1;
		$post["action"] = 'set_oauth_domain';
		$re = $this->lea->submit($url, $post, $this->cookie);

		// print_r($re);
		return json_decode($re['body'], true);
	}


	/**
	 * 获取微信基本资料和认证信息appid，appkey 等
	 * @return array
	 * @author forecho [caizhenghai@gmail.com]
	 */
	public function getWxInfo(){
		$url = "https://mp.weixin.qq.com/advanced/advanced?action=dev&t=advanced/dev&token={$this->webToken}&lang=zh_CN&f=json";
		$re = $this->lea->get($url, $this->cookie);

		// print_r($re);
		return json_decode($re['body'], true);
	}
}
