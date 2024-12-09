<?php
$qf = './qf.txt';
if (!file_exists($qf)) {
    // 创建文件
    file_put_contents($qf, '');
    echo '文件创建成功';
	header("Location: " . $_SERVER['PHP_SELF']);
    exit();
} else {
    $s = file_get_contents ( $qf);
}



if(strlen($_POST['q'])>0)
{
	$s = $_POST['q'];
	//file_put_contents (  $qf,  $s ,FILE_APPEND );
	$s = htmlspecialchars($s);
	$s = strip_tags($s);
	file_put_contents (  $qf,  $s );
	$s = file_get_contents ( $qf);
}

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Q_FILE</title>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<link href="http://123.haodianxin.cn/cdn/bootstrap-combined.min.css" rel="stylesheet">

<!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="http://v2.bootcss.com/assets/js/html5shiv.js"></script>
    <![endif]-->

<style type="text/css">
	body {
	  background-color: #FFFFFF;
	  padding-left: 8px;
	  padding-right: 8px;
	}
	#content {
	  background-color: #FFFFFF;
	  border-radius: 5px;
	}
	.leftmargin
	{ 
	margin-left:10px;;
	}
	.comments {
       width:95%;/*自动适应父布局宽度*/
       overflow:auto;
       word-break:break-all;/*在ie中解决断行问题(防止自动变为在一行显示，主要解决ie兼容问题，ie8中当设宽度为100%时，文本域类容超过一行时，当我们双击文本内容就会自动变为一行显示，所以只能用ie的专有断行属性"word-break或word-wrap"控制其断行)*/
      }

</style>
</head>

<body>
<div class="container-fluid">
<form action="./qf.php" method="post">
Contents :<br>
<textarea  class="comments" id = 'tt' rows="12" name="q" >
<?php 
if(strlen($s)>0)
{
	echo $s;
}
?>
</textarea>

<br>
<input type="submit" value="提交">
<pre>
<?php
if(strlen($s)>0)
{
	echo $s;
	$k  =  preg_split ( "/[\s,]+/" ,  $s );
	//print_r($k);
	foreach($k as $v)
	{
		if(strstr ( $v ,  'http' ))
		{
			$s =  "<br /><a href='$v' >$v</a>";
			echo $s;

		}
	}

}

?>
</pre>

</form> 

<p>如果您点击提交，表单数据会被发送。</p>
</div>
</body>
</html>