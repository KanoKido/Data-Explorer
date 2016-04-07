<?php
/**
  * 作者：邓曦
    联系邮箱：kanokido049@gmail.com
    联系电话：18920348672
    日期：2016.01
    version：0.1
    功能：有道翻译，机器人接口，自定义菜单栏，关注事件
  */

//define your token
define("TOKEN", "jiekou");//定义识别码
$wechatObj = new wechatCallbackapiTest();
$wechatObj->responseMsg();
//$wechatObj->valid();

class wechatCallbackapiTest
{
    public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
            echo $echoStr;
            exit;
        }
    }

    public function responseMsg()
    {
        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

        //extract post data
        if (!empty($postStr)){
                
                $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $RX_TYPE = trim($postObj->MsgType);

                switch($RX_TYPE)
                {
                    case "text":
                        $resultStr = $this->handleText($postObj);
                        break;
                    case "event":
                        $resultStr = $this->handleEvent($postObj);
                        break;
                    default:
                        $resultStr = "Unknow msg type: ".$RX_TYPE;
                        break;
                }
                echo $resultStr;
        }else {
            echo "";
            exit;
        }
    }

    public function handleText($postObj)
    {
        $fromUsername = $postObj->FromUserName;
        $toUsername = $postObj->ToUserName;
        $keyword = trim($postObj->Content);
        $time = time();
        $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    <FuncFlag>0</FuncFlag>
                    </xml>";             
        if(!empty( $keyword ))
        {
            $msgType = "text";
            $str = mb_substr($keyword,0,2,"UTF-8");
            $str_valid = mb_substr($keyword,0,-2,"UTF-8");
            if($str == "翻译" && !empty($str_valid)){
                $word = mb_substr($keyword,2,202,"UTF-8");
                //调用有道词典
                $contentStr = $this->youdaoDic($word);
            }elseif($str != "翻译" && $keyword=="1"){
                $contentStr = "公司简介";
            }elseif($str != "翻译" && $keyword=="2"){
                $contentStr = "最新优惠";
            }else{
                $contentStr = $this->tuling($keyword);
            }
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
            echo $resultStr;
        }else{
            echo "Input something...";
        }
    }

    public function handleEvent($object)
    {
        $contentStr = "";
        switch ($object->Event)
        {
            case "subscribe":
                $contentStr = "感谢您关注"."\n"."微信号：zhuzhu365"."\n"."回复："."\n"."【1】公司简介"."\n"."【2】最新优惠"."\n"."【翻译+内容】即可翻译文本信息"."\n"."回复任意非以上内容，将接入机器人小猪猪～"."\n"."更多内容，敬请期待...";
                break;
            case "CLICK":
                switch ($object->EventKey) {
                    case "company":
                        $contentStr[] = array("Title" =>"公司简介", 
                                            "Description" =>"相关的产品及服务", 
                                            "PicUrl" =>"https://ss0.bdstatic.com/5aV1bjqh_Q23odCf/static/superman/img/logo/logo_white.png", 
                                            "Url" =>"http://baidu.com");
                        break;
                    default:
                        $contentStr[] = array("Title" =>"默认菜单回复", 
                                            "Description" =>"自定义菜单测试接口", 
                                            "PicUrl" =>"http://3gimg.qq.com/qq_product_operations/im/2015/logo_w.png", 
                                            "Url" =>"http://www.qq.com");
                        break;
                }
                break;
            default :
                $contentStr = "Unknow Event: ".$object->Event;
                break;
        }
        if (is_array($contentStr)){
            $resultStr = $this->transmitNews($object, $contentStr);
        }else{
            $resultStr = $this->transmitText($object, $contentStr);
        }
        return $resultStr;
    }
    private function transmitText($object, $content, $funcFlag = 0)
    {
        $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    <FuncFlag>%d</FuncFlag>
                    </xml>";
        $resultStr = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content, $funcFlag);
        return $resultStr;
    }

    private function transmitNews($object, $arr_item, $funcFlag = 0)
    {
        //首条标题28字，其他标题39字
        if(!is_array($arr_item))
            return;

        $itemTpl = "<item>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <PicUrl><![CDATA[%s]]></PicUrl>
        <Url><![CDATA[%s]]></Url>
        </item>";
        $item_str = "";
        foreach ($arr_item as $item)
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);

            $newsTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[news]]></MsgType>
                        <Content><![CDATA[]]></Content>
                        <ArticleCount>%s</ArticleCount>
                        <Articles>
            $item_str</Articles>
                        <FuncFlag>%s</FuncFlag>
                        </xml>";

        $resultStr = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, time(), count($arr_item), $funcFlag);
        return $resultStr;
    }
    
    public function responseText($object, $content, $flag=0)
    {
        $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    <FuncFlag>%d</FuncFlag>
                    </xml>";
        $resultStr = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content, $flag);
        return $resultStr;
    }
    public function youdaoDic($word){

        $keyfrom = "notfound";    //申请APIKEY 时所填表的网站名称的内容
        $apikey = "1582872719";  //从有道申请的APIKEY
        
        //有道翻译-xml格式
        $url_youdao = 'http://fanyi.youdao.com/fanyiapi.do?keyfrom='.$keyfrom.'&key='.$apikey.'&type=data&doctype=xml&version=1.1&q='.$word;
        
        $xmlStyle = simplexml_load_file($url_youdao);
        
        $errorCode = $xmlStyle->errorCode;

        $paras = $xmlStyle->translation->paragraph;

        if($errorCode == 0){
            return $paras;
        }else{
            return "无法进行有效的翻译";
        }
    }
    // 图灵机器人
    public function tuling($keyword) {
        $apiKey = "a5d303d582bbe11762756d6a9b9b775e";
        $apiURL = "http://www.tuling123.com/openapi/api?key=".$apiKey."&info=". $keyword;
        $json=file_get_contents($apiURL);

        $result=json_decode($json,true);

        //$errorCode=$result['result'];

        $response=$result['text'];

        if(!empty($response)){
            return $response;
        }else{
            $ran=rand(1,5);
            switch($ran){
                case 1:
                    return "小猪猪今天累了，明天再陪你聊天吧。";
                    break;
                case 2:
                    return "小猪猪睡觉喽~~";
                    break;
                case 3:
                    return "呼呼~~呼呼~~";
                    break;
                case 4:
                    return "你话好多啊，不跟你聊了";
                    break;
                case 5:
                    return "感谢您关注zhuzhu365";
                    break;
                default:
                    return "感谢您关注zhuzhu365";
                    break;
            }
        }
    }

         
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];    
                
        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        
        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
}

?>
