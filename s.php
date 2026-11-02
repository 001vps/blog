<?php
/**
 * HTML5 Speedtest 单文件整合版（含客户端 IP 显示）
 * 整合内容：index.html + speedtest_worker.min.js + empty.php + garbage.php
 * 使用方法：放到支持 PHP 的 Web 服务器（推荐 HTTP/2），直接访问本文件即可。
 *
 * 路由说明（由 JS 自动调用）：
 *   ?action=dl&ckSize=1  下载测速（随机数据）
 *   ?action=ul           上传测速（空响应）
 *   ?action=ping         延迟/抖动测试（空响应）
 *   ?action=ip           获取客户端 IP（JSON）
 *   无 action            输出测速页面
 */

$action = (isset($_GET['action']) && is_string($_GET['action'])) ? $_GET['action'] : '';

/* ============ 下载测速：输出随机数据 ============ */
if ($action === 'dl') {
    @ini_set('zlib.output_compression', 'Off');
    @ini_set('output_buffering', 'Off');
    @ini_set('output_handler', '');
    @set_time_limit(0);
    while (ob_get_level() > 0) { @ob_end_clean(); }

    header('HTTP/1.1 200 OK');
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=random.dat');
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');

    $data = openssl_random_pseudo_bytes(1048576);
    if ($data === false) { $data = str_repeat('A', 1048576); }

    $chunks = isset($_GET['ckSize']) ? intval($_GET['ckSize']) : 4;
    if ($chunks <= 0)   { $chunks = 4; }
    if ($chunks > 100)  { $chunks = 100; }

    for ($i = 0; $i < $chunks; $i++) {
        echo $data;
        flush();
    }
    exit;
}

/* ============ 上传测速 / 延迟测试：空响应 ============ */
if ($action === 'ul' || $action === 'ping') {
    header('HTTP/1.1 200 OK');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Connection: keep-alive');
    header('Content-Length: 0');
    exit;
}

/* ============ 获取客户端 IP ============ */
/* ============ 获取客户端 IP ============ */
if ($action === 'ip') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    function speedtest_client_ip() {
        $keys = array(
            'HTTP_CF_CONNECTING_IP',   // Cloudflare
            'HTTP_X_REAL_IP',          // Nginx 反向代理
            'HTTP_X_FORWARDED_FOR',    // 通用代理链
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        );
        foreach ($keys as $k) {
            if (empty($_SERVER[$k])) { continue; }
            $ip = $_SERVER[$k];
            if (strpos($ip, ',') !== false) {
                $parts = explode(',', $ip);
                $ip = trim($parts[0]);
            }
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return '';
    }

    $ip = speedtest_client_ip();
    $result = array(
        'processedString' => $ip,
        'rawIspInfo'      => ''
    );

    // 兼容 PHP 5.3/5.4：仅在常量存在时启用对应标志
    $flags = 0;
    if (defined('JSON_UNESCAPED_UNICODE')) { $flags |= JSON_UNESCAPED_UNICODE; }
    if (defined('JSON_UNESCAPED_SLASHES')) { $flags |= JSON_UNESCAPED_SLASHES; }

    echo json_encode($result, $flags);
    exit;
}
?><!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no" />
<title>HTML5 Speedtest</title>
<style type="text/css">
	html,body{
		border:none; padding:0; margin:0;
		background:#FFFFFF;
		color:#202020;
	}
	body{
		text-align:center;
		font-family:"Roboto",sans-serif;
	}
	h1{
		color:#404040;
	}
	/* 客户端 IP 显示区 */
	#ipArea{
		margin: 0.6em auto 0;
		font-size: 1.1em;
		color: #606060;
	}
	#ipArea .ip-label{
		color: #808080;
	}
	#ipArea #ip{
		color: #303030;
		font-weight: bold;
		font-family: "Consolas","Menlo","Courier New",monospace;
		letter-spacing: 0.02em;
	}
	#ipArea #ip.error{
		color: #C04040;
		font-weight: normal;
	}
	#startStopBtn{
		display:inline-block;
		margin:1.2em auto 0;
		color:#6060AA;
		background-color:rgba(0,0,0,0);
		border:0.15em solid #6060FF;
		border-radius:0.3em;
		transition:all 0.3s;
		box-sizing:border-box;
		width:8em; height:3em;
		line-height:2.7em;
		cursor:pointer;
		box-shadow: 0 0 0 rgba(0,0,0,0.1), inset 0 0 0 rgba(0,0,0,0.1);
	}
	#startStopBtn:hover{
		box-shadow: 0 0 2em rgba(0,0,0,0.1), inset 0 0 1em rgba(0,0,0,0.1);
	}
	#startStopBtn.running{
		background-color:#FF3030;
		border-color:#FF6060;
		color:#FFFFFF;
	}
	#startStopBtn:before{
		content:"开始测试";
	}
	#startStopBtn.running:before{
		content:"强制停止";
	}
	#test{
		margin-top:2em;
		margin-bottom:12em;
	}
	div.testArea{
		display:inline-block;
		width:16em;
		height:12.5em;
		position:relative;
		box-sizing:border-box;
	}
	div.testName{
		position:absolute;
		top:0.1em; left:0;
		width:100%;
		font-size:1.4em;
		z-index:9;
	}
	div.meterText{
		position:absolute;
		bottom:1.55em; left:0;
		width:100%;
		font-size:2.5em;
		z-index:9;
	}
	div.meterText:empty:before{
		content:"0.00";
	}
	div.unit{
		position:absolute;
		bottom:2em; left:0;
		width:100%;
		z-index:9;
	}
	div.testArea canvas{
		position:absolute;
		top:0; left:0; width:100%; height:100%;
		z-index:1;
	}
	div.testGroup{
		display:inline-block;
	}
	@media all and (max-width:65em){
		body{ font-size:1.5vw; }
	}
	@media all and (max-width:40em){
		body{ font-size:0.8em; }
		div.testGroup{
			display:block;
			margin: 0 auto;
		}
	}
	.footer {
		width: 96%;
		margin: 60px 2% 0;
		padding: 16px 0;
		text-align: center
	}
	.footer a {
		margin: 0 12px;
		font-family: "Arial",sans-serif;
		font-size: 13px;
		font-weight: 400;
		letter-spacing: 0;
		line-height: 16px;
		color: #5c6f84
	}
	.footer a:hover {text-decoration: underline}
</style>
<script type="text/javascript">
function I(id){return document.getElementById(id);}
var meterBk="#E0E0E0";
var dlColor="#6060AA",
	ulColor="#309030",
	pingColor="#AA6060",
	jitColor="#AA6060";
var progColor="#EEEEEE";

/* ============ 客户端 IP 显示 ============ */
var clientIpLoaded = false;
function loadClientIp(){
	var url = location.protocol + '//' + location.host + location.pathname + '?action=ip&r=' + Math.random();
	var x = new XMLHttpRequest();
	x.onload = function(){
		var text = '';
		try {
			var d = JSON.parse(x.responseText);
			text = (d && (d.processedString || d.ip)) ? d.processedString || d.ip : '';
		} catch(e) {
			text = (x.responseText || '').replace(/^\s+|\s+$/g, '');
		}
		if (text) {
			I("ip").textContent = text;
			I("ip").className = "";
		} else {
			I("ip").textContent = "获取失败";
			I("ip").className = "error";
		}
		clientIpLoaded = true;
	};
	x.onerror = function(){
		I("ip").textContent = "获取失败";
		I("ip").className = "error";
		clientIpLoaded = true;
	};
	x.open("GET", url, true);
	x.send();
}

//CODE FOR GAUGES
function drawMeter(c,amount,bk,fg,progress,prog){
	var ctx=c.getContext("2d");
	var dp=window.devicePixelRatio||1;
	var cw=c.clientWidth*dp, ch=c.clientHeight*dp;
	var sizScale=ch*0.0055;
	if(c.width==cw&&c.height==ch){
		ctx.clearRect(0,0,cw,ch);
	}else{
		c.width=cw;
		c.height=ch;
	}
	ctx.beginPath();
	ctx.strokeStyle=bk;
	ctx.lineWidth=16*sizScale;
	ctx.arc(c.width/2,c.height-58*sizScale,c.height/1.8-ctx.lineWidth,-Math.PI*1.1,Math.PI*0.1);
	ctx.stroke();
	ctx.beginPath();
	ctx.strokeStyle=fg;
	ctx.lineWidth=16*sizScale;
	ctx.arc(c.width/2,c.height-58*sizScale,c.height/1.8-ctx.lineWidth,-Math.PI*1.1,amount*Math.PI*1.2-Math.PI*1.1);
	ctx.stroke();
	if(typeof progress !== "undefined"){
		ctx.fillStyle=prog;
		ctx.fillRect(c.width*0.3,c.height-16*sizScale,c.width*0.4*progress,4*sizScale);
	}
}
function mbpsToAmount(s){
	return 1-(1/(Math.pow(1.3,Math.sqrt(s))));
}
function msToAmount(s){
	return 1-(1/(Math.pow(1.08,Math.sqrt(s))));
}

//SPEEDTEST AND UI CODE
var w=null;
var data=null;
var workerBlobUrl=null;

function getBaseUrl(){
	return location.protocol + '//' + location.host + location.pathname;
}
function getWorkerUrl(){
	if(workerBlobUrl) return workerBlobUrl;
	var src = document.getElementById('workerSource').textContent.replace(/__BASE__/g, getBaseUrl());
	workerBlobUrl = URL.createObjectURL(new Blob([src], {type:'application/javascript'}));
	return workerBlobUrl;
}

function startStop(){
	if(w!=null){
		try{ w.terminate(); }catch(e){}
		w=null;
		data=null;
		I("startStopBtn").className="";
		initUI();
	}else{
		w=new Worker(getWorkerUrl());
		w.postMessage('start');
		I("startStopBtn").className="running";
		w.onmessage=function(e){
			data=JSON.parse(e.data);
			var status=data.testState;
			if(status>=4){
				I("startStopBtn").className="";
				try{ w.terminate(); }catch(ex){}
				w=null;
				updateUI(true);
			}
		};
	}
}
function updateUI(forced){
	if(!forced&&(!data||!w)) return;
	var status=data.testState;
	// 不再覆盖 IP 显示
	I("dlText").textContent=(status==1&&data.dlStatus==0)?"...":data.dlStatus;
	drawMeter(I("dlMeter"),mbpsToAmount(Number(data.dlStatus*(status==1?oscillate():1))),meterBk,dlColor,Number(data.dlProgress),progColor);
	I("ulText").textContent=(status==3&&data.ulStatus==0)?"...":data.ulStatus;
	drawMeter(I("ulMeter"),mbpsToAmount(Number(data.ulStatus*(status==3?oscillate():1))),meterBk,ulColor,Number(data.ulProgress),progColor);
	I("pingText").textContent=data.pingStatus;
	drawMeter(I("pingMeter"),msToAmount(Number(data.pingStatus*(status==2?oscillate():1))),meterBk,pingColor,Number(data.pingProgress),progColor);
	I("jitText").textContent=data.jitterStatus;
	drawMeter(I("jitMeter"),msToAmount(Number(data.jitterStatus*(status==2?oscillate():1))),meterBk,jitColor,Number(data.pingProgress),progColor);
}
function oscillate(){
	return 1+0.02*Math.sin(Date.now()/100);
}
setInterval(function(){
	if(w) w.postMessage('status');
},200);
window.requestAnimationFrame=window.requestAnimationFrame||window.webkitRequestAnimationFrame||window.mozRequestAnimationFrame||window.msRequestAnimationFrame||(function(callback,element){setTimeout(callback,1000/60);});
function frame(){
	requestAnimationFrame(frame);
	updateUI();
}
frame();
function initUI(){
	drawMeter(I("dlMeter"),0,meterBk,dlColor,0);
	drawMeter(I("ulMeter"),0,meterBk,ulColor,0);
	drawMeter(I("pingMeter"),0,meterBk,pingColor,0);
	drawMeter(I("jitMeter"),0,meterBk,jitColor,0);
	I("dlText").textContent="";
	I("ulText").textContent="";
	I("pingText").textContent="";
	I("jitText").textContent="";
}
</script>
</head>
<body>
<h1>HTML5 Speedtest</h1>
<div id="ipArea"><span class="ip-label">您的 IP：</span><span id="ip">获取中...</span></div>
<div id="startStopBtn" onclick="startStop()"></div>
<div id="test">
	<div class="testGroup">
		<div class="testArea">
			<div class="testName">下载速度</div>
			<canvas id="dlMeter" class="meter"></canvas>
			<div id="dlText" class="meterText"></div>
			<div class="unit">Mbps</div>
		</div>
		<div class="testArea">
			<div class="testName">上传速度</div>
			<canvas id="ulMeter" class="meter"></canvas>
			<div id="ulText" class="meterText"></div>
			<div class="unit">Mbps</div>
		</div>
	</div>
	<div class="testGroup">
		<div class="testArea">
			<div class="testName">网络延迟</div>
			<canvas id="pingMeter" class="meter"></canvas>
			<div id="pingText" class="meterText"></div>
			<div class="unit">ms</div>
		</div>
		<div class="testArea">
			<div class="testName">网络抖动</div>
			<canvas id="jitMeter" class="meter"></canvas>
			<div id="jitText" class="meterText"></div>
			<div class="unit">ms</div>
		</div>
	</div>
</div>

<!-- 内嵌 Worker 源码，__BASE__ 运行时替换为当前 PHP 文件绝对地址 -->
<script id="workerSource" type="text/plain">var testStatus=-1,dlStatus="",ulStatus="",pingStatus="",jitterStatus="",clientIp="",dlProgress=0,ulProgress=0,pingProgress=0,testId=null,log="";function tlog(s){2<=settings.telemetry_level&&(log+=Date.now()+": "+s+"\n")}function tverb(s){3<=settings.telemetry_level&&(log+=Date.now()+": "+s+"\n")}function twarn(s){2<=settings.telemetry_level&&(log+=Date.now()+" WARN: "+s+"\n"),console.warn(s)}var settings={test_order:"IP_D_U",time_ul_max:20,time_dl_max:20,time_auto:!0,time_ulGraceTime:3,time_dlGraceTime:3,count_ping:10,url_dl:"__BASE__?action=dl",url_ul:"__BASE__?action=ul",url_ping:"__BASE__?action=ping",url_getIp:"__BASE__?action=ip",getIp_ispInfo:!1,getIp_ispInfo_distance:"false",xhr_dlMultistream:10,xhr_ulMultistream:10,xhr_multistreamDelay:-1,xhr_ignoreErrors:1,xhr_dlUseBlob:!1,xhr_ul_blob_megabytes:1,garbagePhp_chunkSize:1,enable_quirks:!0,ping_allowPerformanceApi:!0,overheadCompensationFactor:1.06,useMebibits:!1,telemetry_level:0,url_telemetry:"telemetry/telemetry.php",telemetry_extra:""},xhr=null,interval=null,test_pointer=0;function url_sep(url){return url.match(/\?/)?"&":"?"}function clearRequests(){if(tverb("stopping pending XHRs"),xhr){for(var i=0;i<xhr.length;i++){try{xhr[i].onprogress=null,xhr[i].onload=null,xhr[i].onerror=null}catch(e){}try{xhr[i].upload.onprogress=null,xhr[i].upload.onload=null,xhr[i].upload.onerror=null}catch(e){}try{xhr[i].abort()}catch(e){}try{delete xhr[i]}catch(e){}}xhr=null}}this.addEventListener("message",function(e){var params=e.data.split(" ");if("status"===params[0]&&postMessage(JSON.stringify({testState:testStatus,dlStatus:dlStatus,ulStatus:ulStatus,pingStatus:pingStatus,clientIp:clientIp,jitterStatus:jitterStatus,dlProgress:dlProgress,ulProgress:ulProgress,pingProgress:pingProgress,testId:testId})),"start"===params[0]&&-1===testStatus){testStatus=0;try{var s={};try{var ss=e.data.substring(5);ss&&(s=JSON.parse(ss))}catch(e){twarn("Error parsing custom settings JSON. Please check your syntax")}for(var key in s)void 0!==settings[key]?settings[key]=s[key]:twarn("Unknown setting ignored: "+key);if(settings.enable_quirks||void 0!==s.enable_quirks&&s.enable_quirks){var ua=navigator.userAgent;/Firefox.(\d+\.\d+)/i.test(ua)&&void 0===s.xhr_ulMultistream&&(settings.xhr_ulMultistream=10),/Edge.(\d+\.\d+)/i.test(ua)&&void 0===s.xhr_dlMultistream&&(settings.xhr_dlMultistream=10),/Chrome.(\d+)/i.test(ua)&&self.fetch&&void 0===s.xhr_dlMultistream&&(settings.xhr_dlMultistream=10)}/Edge.(\d+\.\d+)/i.test(ua)&&(settings.forceIE11Workaround=!0),/PlayStation 4.(\d+\.\d+)/i.test(ua)&&(settings.forceIE11Workaround=!0),/Chrome.(\d+)/i.test(ua)&&/Android|iPhone|iPad|iPod|Windows Phone/i.test(ua)&&(settings.xhr_ul_blob_megabytes=1),void 0!==s.telemetry_level&&(settings.telemetry_level="basic"===s.telemetry_level?1:"full"===s.telemetry_level?2:"debug"===s.telemetry_level?3:0),settings.test_order=settings.test_order.toUpperCase()}catch(e){twarn("Possible error in custom test settings. Some settings may not be applied. Exception: "+e)}tverb(JSON.stringify(settings)),test_pointer=0;var iRun=!1,dRun=!1,uRun=!1,pRun=!1,runNextTest=function(){if(5!=testStatus)if(test_pointer>=settings.test_order.length)0<settings.telemetry_level?sendTelemetry(function(id){testStatus=4,null!=id&&(testId=id)}):testStatus=4;else switch(settings.test_order.charAt(test_pointer)){case"I":if(test_pointer++,iRun)return void runNextTest();iRun=!0,getIp(runNextTest);break;case"D":if(test_pointer++,dRun)return void runNextTest();dRun=!0,testStatus=1,dlTest(runNextTest);break;case"U":if(test_pointer++,uRun)return void runNextTest();uRun=!0,testStatus=3,ulTest(runNextTest);break;case"P":if(test_pointer++,pRun)return void runNextTest();pRun=!0,testStatus=2,pingTest(runNextTest);break;case"_":test_pointer++,setTimeout(runNextTest,1e3);break;default:test_pointer++}};runNextTest()}"abort"===params[0]&&(tlog("manually aborted"),clearRequests(),runNextTest=null,interval&&clearInterval(interval),1<settings.telemetry_level&&sendTelemetry(function(){}),testStatus=5,jitterStatus=pingStatus=ulStatus=dlStatus="")});var ipCalled=!1,ispInfo="";function getIp(done){if(tverb("getIp"),!ipCalled){ipCalled=!0;var startT=(new Date).getTime();(xhr=new XMLHttpRequest).onload=function(){tlog("IP: "+xhr.responseText+", took "+((new Date).getTime()-startT)+"ms");try{var data=JSON.parse(xhr.responseText);clientIp=data.processedString,ispInfo=data.rawIspInfo}catch(e){clientIp=xhr.responseText,ispInfo=""}done()},xhr.onerror=function(){tlog("getIp failed, took "+((new Date).getTime()-startT)+"ms"),done()},xhr.open("GET",settings.url_getIp+url_sep(settings.url_getIp)+(settings.getIp_ispInfo?"isp=true"+(settings.getIp_ispInfo_distance?"&distance="+settings.getIp_ispInfo_distance+"&":"&"):"&")+"r="+Math.random(),!0),xhr.send()}}var dlCalled=!1;function dlTest(done){if(tverb("dlTest"),!dlCalled){dlCalled=!0;var totLoaded=0,startT=(new Date).getTime(),bonusT=0,graceTimeDone=!1,failed=!1;xhr=[];for(var testStream=function(i,delay){setTimeout(function(){if(1===testStatus){tverb("dl test stream started "+i+" "+delay);var prevLoaded=0,x=new XMLHttpRequest;xhr[i]=x,xhr[i].onprogress=function(event){if(tverb("dl stream progress event "+i+" "+event.loaded),1!==testStatus)try{x.abort()}catch(e){}var loadDiff=event.loaded<=0?0:event.loaded-prevLoaded;isNaN(loadDiff)||!isFinite(loadDiff)||loadDiff<0||(totLoaded+=loadDiff,prevLoaded=event.loaded)}.bind(this),xhr[i].onload=function(){tverb("dl stream finished "+i);try{xhr[i].abort()}catch(e){}testStream(i,0)}.bind(this),xhr[i].onerror=function(){tverb("dl stream failed "+i),0===settings.xhr_ignoreErrors&&(failed=!0);try{xhr[i].abort()}catch(e){}delete xhr[i],1===settings.xhr_ignoreErrors&&testStream(i,0)}.bind(this);try{settings.xhr_dlUseBlob?xhr[i].responseType="blob":xhr[i].responseType="arraybuffer"}catch(e){}xhr[i].open("GET",settings.url_dl+url_sep(settings.url_dl)+"r="+Math.random()+"&ckSize="+settings.garbagePhp_chunkSize,!0),xhr[i].send()}}.bind(this),1+delay)}.bind(this),i=0;i<settings.xhr_dlMultistream;i++)testStream(i,settings.xhr_multistreamDelay*i);interval=setInterval(function(){tverb("DL: "+dlStatus+(graceTimeDone?"":" (in grace time)"));var t=(new Date).getTime()-startT;if(graceTimeDone&&(dlProgress=(t+bonusT)/(1e3*settings.time_dl_max)),!(t<200))if(graceTimeDone){var speed=totLoaded/(t/1e3);if(settings.time_auto){var bonus=6.4*speed/1e5;bonusT+=800<bonus?800:bonus}dlStatus=(8*speed*settings.overheadCompensationFactor/(settings.useMebibits?1048576:1e6)).toFixed(2),((t+bonusT)/1e3>settings.time_dl_max||failed)&&((failed||isNaN(dlStatus))&&(dlStatus="Fail"),clearRequests(),clearInterval(interval),dlProgress=1,tlog("dlTest: "+dlStatus+", took "+((new Date).getTime()-startT)+"ms"),done())}else t>1e3*settings.time_dlGraceTime&&(0<totLoaded&&(startT=(new Date).getTime(),totLoaded=bonusT=0),graceTimeDone=!0)}.bind(this),200)}}var ulCalled=!1;function ulTest(done){if(tverb("ulTest"),!ulCalled){ulCalled=!0;var r=new ArrayBuffer(1048576),maxInt=Math.pow(2,32)-1;try{r=new Uint32Array(r);for(var i=0;i<r.length;i++)r[i]=Math.random()*maxInt}catch(e){}var req=[],reqsmall=[];for(i=0;i<settings.xhr_ul_blob_megabytes;i++)req.push(r);req=new Blob(req),r=new ArrayBuffer(262144);try{r=new Uint32Array(r);for(i=0;i<r.length;i++)r[i]=Math.random()*maxInt}catch(e){}reqsmall.push(r),reqsmall=new Blob(reqsmall);var totLoaded=0,startT=(new Date).getTime(),bonusT=0,graceTimeDone=!1,failed=!1;xhr=[];var testStream=function(i,delay){setTimeout(function(){if(3===testStatus){tverb("ul test stream started "+i+" "+delay);var ie11workaround,prevLoaded=0,x=new XMLHttpRequest;if(xhr[i]=x,settings.forceIE11Workaround)ie11workaround=!0;else try{xhr[i].upload.onprogress,ie11workaround=!1}catch(e){ie11workaround=!0}if(ie11workaround){xhr[i].onload=xhr[i].onerror=function(){tverb("ul stream progress event (ie11wa)"),totLoaded+=reqsmall.size,testStream(i,0)},xhr[i].open("POST",settings.url_ul+url_sep(settings.url_ul)+"r="+Math.random(),!0);try{xhr[i].setRequestHeader("Content-Encoding","identity")}catch(e){}try{xhr[i].setRequestHeader("Content-Type","application/octet-stream")}catch(e){}xhr[i].send(reqsmall)}else{xhr[i].upload.onprogress=function(event){if(tverb("ul stream progress event "+i+" "+event.loaded),3!==testStatus)try{x.abort()}catch(e){}var loadDiff=event.loaded<=0?0:event.loaded-prevLoaded;isNaN(loadDiff)||!isFinite(loadDiff)||loadDiff<0||(totLoaded+=loadDiff,prevLoaded=event.loaded)}.bind(this),xhr[i].upload.onload=function(){tverb("ul stream finished "+i),testStream(i,0)}.bind(this),xhr[i].upload.onerror=function(){tverb("ul stream failed "+i),0===settings.xhr_ignoreErrors&&(failed=!0);try{xhr[i].abort()}catch(e){}delete xhr[i],1===settings.xhr_ignoreErrors&&testStream(i,0)}.bind(this),xhr[i].open("POST",settings.url_ul+url_sep(settings.url_ul)+"r="+Math.random(),!0);try{xhr[i].setRequestHeader("Content-Encoding","identity")}catch(e){}try{xhr[i].setRequestHeader("Content-Type","application/octet-stream")}catch(e){}xhr[i].send(req)}}}.bind(this),1)}.bind(this);for(i=0;i<settings.xhr_ulMultistream;i++)testStream(i,settings.xhr_multistreamDelay*i);interval=setInterval(function(){tverb("UL: "+ulStatus+(graceTimeDone?"":" (in grace time)"));var t=(new Date).getTime()-startT;if(graceTimeDone&&(ulProgress=(t+bonusT)/(1e3*settings.time_ul_max)),!(t<200))if(graceTimeDone){var speed=totLoaded/(t/1e3);if(settings.time_auto){var bonus=6.4*speed/1e5;bonusT+=800<bonus?800:bonus}ulStatus=(8*speed*settings.overheadCompensationFactor/(settings.useMebibits?1048576:1e6)).toFixed(2),((t+bonusT)/1e3>settings.time_ul_max||failed)&&((failed||isNaN(ulStatus))&&(ulStatus="Fail"),clearRequests(),clearInterval(interval),ulProgress=1,tlog("ulTest: "+ulStatus+", took "+((new Date).getTime()-startT)+"ms"),done())}else t>1e3*settings.time_ulGraceTime&&(0<totLoaded&&(startT=(new Date).getTime(),totLoaded=bonusT=0),graceTimeDone=!0)}.bind(this),200)}}var ptCalled=!1;function pingTest(done){if(tverb("pingTest"),!ptCalled){ptCalled=!0;var startT=(new Date).getTime(),prevT=null,ping=0,jitter=0,i=0,prevInstspd=0;xhr=[];var doPing=function(){tverb("ping"),pingProgress=i/settings.count_ping,prevT=(new Date).getTime(),xhr[0]=new XMLHttpRequest,xhr[0].onload=function(){if(tverb("pong"),0===i)prevT=(new Date).getTime();else{var instspd=(new Date).getTime()-prevT;if(settings.ping_allowPerformanceApi)try{var p=performance.getEntries(),d=(p=p[p.length-1]).responseStart-p.requestStart;d<=0&&(d=p.duration),0<d&&d<instspd&&(instspd=d)}catch(e){tverb("Performance API not supported, using estimate")}var instjitter=Math.abs(instspd-prevInstspd);1===i?ping=instspd:(ping=instspd<ping?instspd:.8*ping+.2*instspd,jitter=2===i?instjitter:jitter<instjitter?.3*jitter+.7*instjitter:.8*jitter+.2*instjitter),prevInstspd=instspd}pingStatus=ping.toFixed(2),jitterStatus=jitter.toFixed(2),i++,tverb("ping: "+pingStatus+" jitter: "+jitterStatus),i<settings.count_ping?doPing():(pingProgress=1,tlog("ping: "+pingStatus+" jitter: "+jitterStatus+", took "+((new Date).getTime()-startT)+"ms"),done())}.bind(this),xhr[0].onerror=function(){tverb("ping failed"),0===settings.xhr_ignoreErrors&&(jitterStatus=pingStatus="Fail",clearRequests(),tlog("ping test failed, took "+((new Date).getTime()-startT)+"ms"),pingProgress=1,done()),1===settings.xhr_ignoreErrors&&doPing(),2===settings.xhr_ignoreErrors&&(++i<settings.count_ping?doPing():(pingProgress=1,tlog("ping: "+pingStatus+" jitter: "+jitterStatus+", took "+((new Date).getTime()-startT)+"ms"),done()))}.bind(this),xhr[0].open("GET",settings.url_ping+url_sep(settings.url_ping)+"r="+Math.random(),!0),xhr[0].send()}.bind(this);doPing()}}function sendTelemetry(done){if(!(settings.telemetry_level<1)){(xhr=new XMLHttpRequest).onload=function(){try{var parts=xhr.responseText.split(" ");if("id"==parts[0])try{var id=parts[1];done(id)}catch(e){done(null)}else done(null)}catch(e){done(null)}},xhr.onerror=function(){console.log("TELEMETRY ERROR "+xhr.status),done(null)},xhr.open("POST",settings.url_telemetry+url_sep(settings.url_telemetry)+"r="+Math.random(),!0);var telemetryIspInfo={processedString:clientIp,rawIspInfo:"object"==typeof ispInfo?ispInfo:""};try{var fd=new FormData;fd.append("ispinfo",JSON.stringify(telemetryIspInfo)),fd.append("dl",dlStatus),fd.append("ul",ulStatus),fd.append("ping",pingStatus),fd.append("jitter",jitterStatus),fd.append("log",1<settings.telemetry_level?log:""),fd.append("extra",settings.telemetry_extra),xhr.send(fd)}catch(ex){var postData="extra="+encodeURIComponent(settings.telemetry_extra)+"&ispinfo="+encodeURIComponent(JSON.stringify(telemetryIspInfo))+"&dl="+encodeURIComponent(dlStatus)+"&ul="+encodeURIComponent(ulStatus)+"&ping="+encodeURIComponent(pingStatus)+"&jitter="+encodeURIComponent(jitterStatus)+"&log="+encodeURIComponent(1<settings.telemetry_level?log:"");xhr.setRequestHeader("Content-Type","application/x-www-form-urlencoded"),xhr.send(postData)}}}</script>

<script type="text/javascript">
setTimeout(function(){
	initUI();
	loadClientIp();
},100);
</script>
</body>
</html>