<?php
//echo "aa";die();
$lib_dir = __DIR__ . '/lib';
set_include_path(get_include_path() . PATH_SEPARATOR . $lib_dir . '/PEAR');
//require_once 'MDB2.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';



$dbh = NULL;

try {
    // DB接続
    //$dbh = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8;', DB_USER, DB_PASSWORD);

       $unchi = file_get_contents('php://input');
    
        $obj = array();
        $obj = json_decode($unchi,TRUE);

        //受け取ったパラメーター格納
        $type = $obj["type"];
        $text = $obj["text"];
        $uid = $obj["to"];
    
    
        //入力値をキャストする
        $int_text = intval($text);
    
       
    
       //パラメータに応じてテキスト変更
       if($int_text===1){
          $text = "最高に盛り上がってる！みんな最高♪( ´θ｀)ノ";
       } else if($int_text===2){
          $text = "まだまだ盛り上がりが足りないぞ！！もっとみんなで盛り上げよう！";
       } else if($int_text===3){
          $text = "公害レベルの盛り上がりだ！";    
       } else if($int_text===4){
          $text = "ここはお葬式会場なのかな？盛り上がりが足りないぞ？さぁみんなで盛り上げよう！";
       }      


    
	    // LINE SDK　利用版
	    $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(TOKEN);
	    $bot = new \LINE\LINEBot($httpClient, ['channelSecret' => SECRET]);
        // メッセージ作製
	    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($text);
	    $response = $bot->pushMessage($uid, $textMessageBuilder);

		exit();
    

} catch (Exception $e) {
    ob_start();
    var_dump($e->getMessage());
    $raw = ob_get_clean();
    file_put_contents('dump.txt', $raw . "\n=====================================\n", FILE_APPEND);
}
