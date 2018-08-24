#!/usr/bin/php
<?php
#0 5 * * * root export LANG="ja_JP.UTF-8"; php path/to/scrpipt
#insert into T02_SND_MAIL (T02_To,T02_Type,T02_CustomerId,T02_Subject,T02_Body,T02_ReserveSndTime) values (	'dummyTo07@local',	'1',	'0000000001',	'TestSubject07',	'TestBody07',	'2015-01-01 00:00:00'	);
$lib_dir = dirname( __FILE__ ) . '/lib';
set_include_path( get_include_path() . PATH_SEPARATOR . $lib_dir . '/PEAR' );
require_once( dirname(__FILE__) . '/config.php' );
require_once 'MDB2.php';
require_once 'Mail.php';
require_once 'Mail/mime.php';

date_default_timezone_set('Asia/Tokyo');
mb_internal_encoding('UTF-8');
mb_language('japanese');

/*
 * メールDB格納
 * POPサーバーから実行される。
 * メールの内容を解析してデータベースにINSERTする。
 */

$dbh = NULL;
try {
	// 送信メールサーバー
	$smtp_server = array(
		'host' => SMTP_HOST,
		'port' => SMTP_PORT,
		'auth' => SMTP_AUTH,
		'username' => SMTP_USER,
		'password' => SMTP_PASS
		);
	$mailObject = Mail::factory('smtp', $smtp_server);

	// DB接続
	$dbh = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8;', DB_USER, DB_PASSWORD);

	// 送信メール取得
	$select_sql = <<< SELECT_SQL
select
T02_Id
,T02_To
,T02_Type
,T02_CustomerId
,T02_Subject
,T02_Body
,T02_Attach_Name
,T02_Attach_Mimetype
,T02_Attach_FileName
,T02_ReserveSndTime
,T02_SndTime
,T02_LastUpDate
,T02_LastUpDateUser
from T02_SND_MAIL
where T02_SndTime is NULL
and T02_ReserveSndTime < ?
LIMIT 100
SELECT_SQL;

	// 送信メール更新
	$update_sql = <<< UPDATE_SQL
update T02_SND_MAIL
set T02_SndTime = ?
where T02_Id = ?
UPDATE_SQL;

	$update_sql1 = <<< UPDATE_SQL
update T01_CUSTOMER
set T01_SurveyDate_send = ?
,T01_SurveyDate_sendflg = '0'
where T01_Id = ?
UPDATE_SQL;

	$update_sql2 = <<< UPDATE_SQL
update T01_CUSTOMER
set T01_ConstructionDate_send = ?
,T01_ConstructionDate_sendflg = '0'
where T01_Id = ?
UPDATE_SQL;

	$update_sql3 = <<< UPDATE_SQL
update T01_CUSTOMER
set T01_ContractStart_send = ?
,T01_ContractStart_sendflg = '0'
where T01_Id = ?
UPDATE_SQL;

	$update_sql9 = <<< UPDATE_SQL
update T01_CUSTOMER
set T01_Mail_send = ?
,T01_Mail_sendflg = '0'
where T01_Id = ?
UPDATE_SQL;

	//var_dump($select_sql);
	$select_stmt = $dbh->prepare($select_sql);
	$update_stmt = $dbh->prepare($update_sql);
	$update_stmt1 = $dbh->prepare($update_sql1);
	$update_stmt2 = $dbh->prepare($update_sql2);
	$update_stmt3 = $dbh->prepare($update_sql3);
	$update_stmt9 = $dbh->prepare($update_sql9);

	$now = date('YmdHis');
	//var_dump($now);
	$select_stmt->execute(array($now));

	$send_count = 0;
	$error = FALSE;

	while ($row = $select_stmt->fetch()) {
		$dbh->beginTransaction();

		if (empty($row['T02_Attach_Name'])) {
			$filename = null;
			$name = null;
			$mime = null;
		} else {
			$filename = $row['T02_Attach_FileName'];
			$name = $row['T02_Attach_Name'];
			$mime = $row['T02_Attach_Mimetype'];
		}

		// メール送信
		$res = send_mail($mailObject,
			         MAIL_ADDR_FROM, $row['T02_To'],
			         $row['T02_Subject'], $row['T02_Body'],
			         $filename, $name, $mime);

		if($res !== TRUE){
			$error = "mail send failed.[ID:{$row['T02_Id']}]";
			break;
		}
		// 送信済UPDATE
		$res = $update_stmt->execute(array($now, $row['T02_Id']));
		if( ! $res){
			$error = "mail update failed.[ID:{$row['T02_Id']}]";
			break;
		}
		if ($row['T02_Type'] === '10') {
			$res = $update_stmt1->execute(array($now, $row['T02_CustomerId']));
			if( ! $res){
				$error = "mail update1 failed.[ID:{$row['T02_CustomerId']}]";
				break;
			}
		}
		if ($row['T02_Type'] === '20') {
			$res = $update_stmt2->execute(array($now, $row['T02_CustomerId']));
			if( ! $res){
				$error = "mail update2 failed.[ID:{$row['T02_CustomerId']}]";
				break;
			}
		}
		if ($row['T02_Type'] === '30') {
			$res = $update_stmt3->execute(array($now, $row['T02_CustomerId']));
			if( ! $res){
				$error = "mail update3 failed.[ID:{$row['T02_CustomerId']}]";
				break;
			}
		}
		if ($row['T02_Type'] === '90') {
			$res = $update_stmt9->execute(array($now, $row['T02_CustomerId']));
			if( ! $res){
				$error = "mail update9 failed.[ID:{$row['T02_CustomerId']}]";
				break;
			}
		}

		// 送信数
		$send_count++;

		$dbh->commit();
	}

	if($error){
		$dbh->rollBack();
		syslog(LOG_ERR, $error);
	// }else{
	// 	$flag = $dbh->commit();
	// 	if($flag){
	// 		syslog(LOG_INFO, "mail send[count:{$send_count}]");
	// 	}else{
	// 		syslog(LOG_ERR, "mail insert failed.[ID:{$customerId}]");
	// 	}
	} else {
		syslog(LOG_INFO, "mail send[count:{$send_count}]");
	}
    $dbh = null;
}catch(PEAR_Error $e){
	//var_dump($e);
	if($dbh){
		$dbh->rollBack();
		$dbh = null;
	}
	syslog(LOG_ERR, "mail pear failed. PEAR_Error.".PHP_EOL.$e->getMessage());
    die();
} catch (PDOException $e) {
	//var_dump($e);
	if($dbh){
		$dbh->rollBack();
		$dbh = null;
	}
	syslog(LOG_ERR, "mail insert failed. PDOException.".PHP_EOL.$e->getMessage());
    die();
}

/**
 * メール送信
 */
function send_mail($mailObject, $from_addr, $to, $title, $body, $attach_filename, $attach_name, $attach_mime) {
//	$header = "From: <{$from_addr}>\n";
//	return mb_send_mail($to, $title, $body, $header);

	// $body = mb_convert_encoding($body, 'ISO-2022-JP', 'UTF-8');
	// $body = mb_convert_encoding($body, 'ISO-2022-JP');
	$mimeObject = new Mail_Mime();
	$mimeObject->setTxtBody(mb_convert_encoding($body, "ISO-2022-JP", 'auto'));
	// $mimeObject->setTxtBody($body);
	if ($attach_name !== null) {
		$attach_name1 = mb_convert_encoding($attach_name, 'ISO-2022-JP');
		$mimeObject->addAttachment("/var/www/html/files/{$attach_filename}", $attach_mime, $attach_name1
			,true // isfile
			,'base64' // encoding
			,'attachment' // disposition attachment
			,'ISO-2022-JP' // charset
			,'' // language
			,'' // location
			,'base64' // n_encoding
			,'ISO-2022-JP' // f_encoding Encoding of the attachment's filename in Content-Disposition header.
			,'' // description
			,'ISO-2022-JP' // h_charset
			);
	}
	$bodyParam = array(
	  'head_charset' => 'ISO-2022-JP',
	  'text_charset' => 'ISO-2022-JP'
	);

	$body = $mimeObject->get($bodyParam);

$to = 'chiakis0819@gmail.com';
$from_addr = 'c.suzuki@willink.co.jp';

	$addHeaders = array(
	  'To' => $to,
	  'From' => $from_addr,
	  'Bcc' => $from_addr,
	  'Subject' => mb_encode_mimeheader($title, 'ISO-2022-JP')
	);

	$to = implode(',', array($to, $from_addr));

	$headers = $mimeObject->headers($addHeaders);

	return $mailObject->send($to, $headers, $body);
}
