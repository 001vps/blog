<?php

function GetHtml($url)
{
$ch2 = curl_init();
$header = array(
    'Accept: application/json',
	'User-Agent:Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1',
);
curl_setopt($ch2, CURLOPT_HTTPHEADER  , $header); 
curl_setopt($ch2, CURLOPT_URL, $url);
curl_setopt($ch2, CURLOPT_HEADER, false);
curl_setopt ($ch2, CURLOPT_RETURNTRANSFER, 1);
curl_setopt ($ch2, CURLOPT_TIMEOUT, 6);

curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER,0); 
curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST,0);

$temp=curl_exec($ch2);

if( $temp ===  false )
{
	file_put_contents ( 'err.htm' ,  curl_error ( $ch2 ).date(DATE_RFC822).$_SERVER["REQUEST_URI"]."<br/>" ,  FILE_APPEND );
	exit();

}
// 关闭cURL资源，并且释放系统资源
curl_close($ch2);
return $temp;
}

function GetAll()
{
	$i_url = $_SERVER['REQUEST_URI'];
	$i_url_a = explode("/",$i_url);
	$n = count($i_url_a);
	if($n>1)
	{
		return $i_url_a[$n-1];

	}
}

$ip =GetAll();

if (empty($ip) or strlen($ip)<7) {
    if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
}else {
        $ip = $_SERVER['REMOTE_ADDR'];
}

}


$url = "https://api.ip.sb/geoip/".$ip;
$r = GetHtml($url);

//print_r($r);
$s = json_decode ( $r ,  true );

//print_r($s);
if(count($s)>3)
{   $ipinfo= $s['ip']."-".$s['country']."-".$s['isp']."";
	//$ipinfo  =  str_replace ( " " ,  "" ,  $ipinfo );
	$ipinfo = preg_replace('/\s+/', '', $ipinfo);
	echo $ipinfo;

	//echo $s['ip']."-".$s['country']."-".$s['isp']."";
}




?>