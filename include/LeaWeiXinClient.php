<?php
/**
 * 发送curl请求
 */
class LeaWeiXinClient {
    function submit($url, $data, $cookie = false) {
        return $this->exec($url, $data, $cookie);
    }

    function get($url, $cookie) {
        return $this->exec($url, false, $cookie, false);
    }

    /**
     * 返回array(cookie=>, body=>)
     * @param  [type] $url    [description]
     * @param  [array] $data   [description]
     * @param  [type] $cookie [description]
     * @return [type]         [description]
     */
    private function exec($url, $data, $cookie = false, $isPost = true) {
        $dataStr = "";
        if($data && is_array($data)) {
            foreach($data as $key => $value) {
                $dataStr .= "$key=$value&";
            }
        }

        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        // curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        $referer = "https://mp.weixin.qq.com/cgi-bin/singlemsgpage";
        $oldReferer = "https://mp.weixin.qq.com/";
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Referer:$referer"));

        if($isPost) {
            curl_setopt($curl, CURLOPT_POST, 0); // 发送一个常规的Post请求
            curl_setopt($curl, CURLOPT_POSTFIELDS, $dataStr); // Post提交的数据包
        }

        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 1); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        // curl_setopt($curl, CURLOPT_COOKIEFILE, 'cookie.txt');
        // curl_setopt($curl, CURLOPT_COOKIEJAR, 'cookie.txt');
        // curl_setopt($curl, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt'); 
        // curl_setopt($curl, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt'); 

        if($cookie) {
            curl_setopt($curl, CURLOPT_COOKIE, $cookie);
        }

        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
           echo 'Errno'. curl_error($curl);//捕抓异常

           return;
        }

        curl_close($curl); // 关闭CURL会话

        // 解析HTTP数据流
        list($header, $body) = explode("\r\n\r\n", $tmpInfo);

        if(!$cookie) {
            // 解析COOKIE
            $cookie = "";
            preg_match_all("/set\-cookie: (.*)/i", $header, $matches);
            if(count($matches == 2)) {
                foreach($matches[1] as $each) {
                    $cookie .= trim($each). ";";
                }
            }
        }

        return array("cookie" => $cookie, "body" => trim($body));
    }
}

/*
$lea = new LeaWeiXinClient();

// 先登录
$url = "https://mp.weixin.qq.com/cgi-bin/login?lang=zh_CN";
$post = array("f" => "json", "username" => "xx", "pwd" => md5("xx"));
$result = $lea->submit($url, $post);

// 获取联系人
http://mp.weixin.qq.com/cgi-bin/getcontactinfo?t=ajax-getcontactinfo&lang=zh_CN&token=&fakeid=";

print_r($result);
*/