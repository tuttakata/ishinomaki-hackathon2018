<?php
$lib_dir = __DIR__ . '/lib';
set_include_path(get_include_path() . PATH_SEPARATOR . $lib_dir . '/PEAR');
//require_once 'MDB2.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

$select_sql = <<< SELECT_SQL
select count(*)
from user
where uid = ?
SELECT_SQL;

$select_message_sql = <<< SELECT_SQL
select *
from messages
where message = ?
SELECT_SQL;

$insert_sql = <<< INSERT_SQL
INSERT INTO
user(uid,group_id) VALUES (?,?);
INSERT_SQL;

$update_sql = <<< UPDATE_SQL
UPDATE messages
SET now_issue = ?
WHERE message = ?;
UPDATE_SQL;

$error = NULL;
$dbh = NULL;

try {
    // DB接続
    $dbh = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8;', DB_USER, DB_PASSWORD);

    //callback確認
    $obj = json_decode(file_get_contents('php://input'));

    //textとreplyToken取得
    $event = $obj->{"events"}[0];
    $type = $event->{"message"}->{"type"};
    $text = $event->{"message"}->{"text"};
    $replyToken = $event->{"replyToken"};
    $uid = $event->{"source"}->{"userId"};
    $message = "";
    
    //メッセージ以外のときは何も返さず終了
    if ($type != "text") {
        exit;
    }
    //キーワードとマッチするかのフラグ
    $matchFlag = FALSE;
    
    //入力値をキャストする
    $int_text = intval($text);
    
    
    if ($int_text === 000){
         $group_id = 0;
        $matchFlag = TRUE;
         
    //静かすぎる
    } else if(($int_text>=10) && ($int_text<=20)){
        $text = "ここはお葬式会場なのかな？盛り上がりが足りないぞ？さぁみんなで盛り上げよう！".$replyToken;
    //静かな場合
    } else if(($int_text>=40) && ($int_text<=50)){
        $text = "まだまだ盛り上がりが足りないぞ！！もっとみんなで盛り上げよう！";
    //盛り上がった場合
    } else if(($int_text>=69) && ($int_text<=80)){
        $text = "最高に盛り上がってる！";
    //煩すぎる場合    
    } else if($int_text>=100){
        $text = "公害レベルの盛り上がりだ！";
    }        
 
       
    //userテーブルにインサート処理
    if($matchFlag){
        $select_stmt = $dbh->prepare($select_sql);
        $select_stmt->execute(array($uid));
        $res = $select_stmt->fetch();
        $count = $res[0];
        //未登録の場合のみ抽選受付する
        if ($count == '0' || $count == null) {
            $dbh->beginTransaction();
            $insert_stmt = $dbh->prepare($insert_sql);
            $res = $insert_stmt->execute(array($uid,$group_id));
            if (!$res) {
                $dbh->rollBack();
                $text = "エラーが発生しました。\nもう一度、キーワードを入力してください。";
            } else {
                $dbh->commit();
                $text = "一緒に盛り上げよう！。";
            }
        }
    }
    

    // LINE SDK　利用版
    $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(TOKEN);

    $bot = new \LINE\LINEBot($httpClient, ['channelSecret' => SECRET]);
    
    //ここでユーザーに対してメッセージの送信を行ってる。

    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($text);
    $response = $bot->replyMessage($replyToken, $textMessageBuilder);

    echo $response->getHTTPStatus() . ' ' . $response->getRawBody();
} catch (Exception $e) {
    ob_start();
    var_dump($e->getMessage());
    $raw = ob_get_clean();
    file_put_contents('dump.txt', $raw . "\n=====================================\n", FILE_APPEND);
}
