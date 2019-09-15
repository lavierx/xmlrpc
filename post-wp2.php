<?php

// get自動入力
if($_POST[host]==""){$_POST[host] = $_GET[host];}
if($_POST[rpc]==""){$_POST[rpc] = $_GET[rpc];}
if($_POST[user]==""){$_POST[user] = $_GET[user];}
if($_POST[pw]==""){$_POST[pw] = $_GET[pw];}
//db
if($_POST[db_host]==""){$_POST[db_host] = $_GET[db_host];}
if($_POST[db_name]==""){$_POST[db_name] = $_GET[db_name];}
if($_POST[db_table]==""){$_POST[db_table] = $_GET[db_table];}
if($_POST[db_user]==""){$_POST[db_user] = $_GET[db_user];}
if($_POST[db_pw]==""){$_POST[db_pw] = $_GET[db_pw];}
?>	
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
 <meta name="viewport" content="width=device-width, initial-scale=1">
<title>記事一括投稿くん2</title>     
</head>
<body>
<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
	<p>host：<input value="<?php echo $_POST[host];?>" type="text" name="host" size="20"></p>
	<p>xmlrpc_path：<input value="<?php echo $_POST[rpc];?>" type="text" name="rpc" size="20"></p>
	<p>user：<input value="<?php echo $_POST[user];?>" type="text" name="user" size="20"></p>		
	<p>password：<input value="<?php echo $_POST[pw];?>" type="text" name="pw" size="20"></p>		
	<p>
	投稿内容(Y-m-d,タイトル,カテゴリ,本文,pw)：<br>
	<textarea name="body" rows="4" cols="40" placeholder="2018-6-24,記事タイトル,カテゴリ,本文,pw
2018-6-24,記事タイトル,カテゴリ,本文,pw
※カンマ、タブどちらの区切りでもOK
※pw省略可"><?php echo $_POST[body];?></textarea>
	</p>	
	<p><input name=sb type="submit" value="送信"></p>	
	<!--DB-->
	<div style="background:#D8D8D8;padding:20px;">
	<p>※pwつき記事投稿の場合は、以下のDB情報も要設定</p>
	<p>host：<input value="<?php echo $_POST[db_host];?>" type="text" name="db_host" size="20"></p>
	<p>db：<input value="<?php echo $_POST[db_name];?>" type="text" name="db_name" size="20"></p>
	<p>table：<input value="<?php echo $_POST[db_table];?>" type="text" name="db_table" size="20"></p>
	<p>user：<input value="<?php echo $_POST[db_user];?>" type="text" name="db_user" size="20"></p>		
	<p>password：<input value="<?php echo $_POST[db_pw];?>" type="text" name="db_pw" size="20"></p>		
	</div>
</form>
<?php
if($_POST[sb]<>""){
	$_POST[body] = str_replace(",","\t",$_POST[body]);
	$body0 = explode("\n",$_POST[body]);
	foreach ($body0 as $line_num => $line) {
		$body = explode("\t",$line);
		//記事投稿
		$id = wppost($_POST[host],$_POST[rpc],$_POST[user],$_POST[pw],trim($body[0]),trim($body[1]),trim($body[2]),trim($body[3]));
		//pw設定
		if(trim($body[4])<>""){
			pw($_POST[db_host],$_POST[db_name],$_POST[db_table],$_POST[db_user],$_POST[db_pw],trim($body[4]),$id);
		}	
	}		
}	


//---------------------------------//
// pw設定関数
//---------------------------------//
function pw($db_host,$db_name,$db_table,$db_user,$db_pw,$pw,$id){
	//データベースに接続 //////////////////////////////////////
	//$con = mysql_connect($db_host, $db_user,$db_pw);
	$con = mysqli_connect($db_host, $db_user,$db_pw, $db_name);
	//mysql_query('SET NAMES utf8', $con);
	//データベースを選択////////////////////////////////////////
	//mysql_select_db($db_name, $con);
	//SQL文をセット/////////////////////////////////////////////
	//$quryset = mysql_query("
	//UPDATE `{$db_name}`.`{$db_table}` SET `post_password` = '{$pw}' WHERE `{$db_table}`.`ID` = {$id}
	//;");
	$quryset = mysqli_query($con,"
	UPDATE `{$db_name}`.`{$db_table}` SET `post_password` = '{$pw}' WHERE `{$db_table}`.`ID` = {$id}
	;");
}
//---------------------------------//
// 投稿関数
//---------------------------------//
function wppost($host0,$xmlrpc_path0,$user0,$passwd0,$ptime0,$title0,$cat0,$description0){
	//PEAR XML_PRCの読み出し
	require_once("XML/RPC.php");
	//--------------------HOST---------------------//

	$host = $host0;
	$xmlrpc_path = $xmlrpc_path0;
	$appkey = '';
	$user = $user0;
	$passwd =$passwd0;

	$c = new XML_RPC_client($xmlrpc_path, $host, 80);

	//--------------------blogID取得---------------------//
	$appkey = new XML_RPC_Value($appkey, 'string');
	$username = new XML_RPC_Value( $user, 'string' );
	$passwd = new XML_RPC_Value( $passwd, 'string' );

	$message = new XML_RPC_Message(
	'blogger.getUsersBlogs',array($appkey, $username, $passwd)
	);

	$result = $c->send($message);

	if(!$result){
		exit('Could not connect to the server.');
	} else if( $result->faultCode() ){
		exit($result->faultString());
	}

	$blogs = XML_RPC_decode($result->value());
	$blog_id = new XML_RPC_Value($blogs[0]["blogid"], "string");

	//--------------------投稿内容---------------------//
	$title = $title0;
	$categories = array(
	new XML_RPC_Value("{$cat0}", "string"),
	);
	$description = $description0;//投稿本文
	$content = new XML_RPC_Value(
	array(
	'title' => new XML_RPC_Value($title, 'string'),
	'categories' => new XML_RPC_Value($categories, 'array'),
	'description' => new XML_RPC_Value($description, 'string'),
	'wp_slug' => new XML_RPC_Value($NO,'string'),
	//'dateCreated' => new XML_RPC_Value(time(), 'dateTime.iso8601')
	//'dateCreated' => new XML_RPC_Value( mktime($HHH,$iii, "0", $MMM, $DDD, $YYY), 'dateTime.iso8601' )
	'dateCreated' => new XML_RPC_Value( strtotime($ptime0), 'dateTime.iso8601' )
	),
	'struct');
	//下書き
	if($SHITA==1){
		$publish = new XML_RPC_Value(0, "boolean");
	}else{
		$publish = new XML_RPC_Value(1, "boolean");
	}	
		
	$message = new XML_RPC_Message(
	'metaWeblog.newPost',
	array($blog_id, $username, $passwd, $content, $publish)
	);

	//--------------------投稿---------------------//	
	$result = $c->send($message);

	if(!$result){
		exit('Could not connect to the server.');
	} else if( $result->faultCode() ){
		exit($result->faultString());
	}
	//記事ID
	$YYY=$result->xv->me;
	return $ZZZ=$YYY[string];//記事ID

}
?>
</body>
</html>		

