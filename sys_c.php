<?php

function formatBytes($bytes, $precision = 2) {
    $units = array('KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// 自定义函数，获取系统信息
function getSystemInfo() {
    // 获取服务器当前时间
    $current_time = date("Y-m-d H:i:s");

	// 尝试获取服务器已运行时间
	$uptime = @file_get_contents('/proc/uptime');

	if ($uptime === false) {
		// 如果获取失败，使用 uptime 命令
		$output = shell_exec('uptime');
		
		// 解析 uptime 输出
		preg_match('/up\s+([\d]+)\s+days?,\s+([\d]+):([\d]+)/', $output, $matches);
		
		if (count($matches) === 4) {
			$days = (int)$matches[1];
			$hours = (int)$matches[2];
			$minutes = (int)$matches[3];
			$seconds = 0; // 使用 uptime 命令时不获取秒数
		} else {
			// 如果没有匹配到，设置为0
			$days = $hours = $minutes = $seconds = 0;
		}
	} else {
		// 如果成功获取 /proc/uptime
		$uptime_seconds = (float)explode(' ', trim($uptime))[0];

		// 计算天、小时、分钟和秒
		$days = floor($uptime_seconds / 86400);
		$hours = floor(($uptime_seconds % 86400) / 3600);
		$minutes = floor(($uptime_seconds % 3600) / 60);
		$seconds = $uptime_seconds % 60;
	}

    // 格式化输出
    $uptime = sprintf("%d天 %d小时 %d分钟 %d秒", $days, $hours, $minutes, $seconds);

    // 获取内存大小
    $memory_info = @file_get_contents('/proc/meminfo');
    preg_match('/MemTotal:\s+(\d+)/', $memory_info, $matches);
    $total_memory = isset($matches[1]) ? $matches[1] : '';

    // 获取已用内存
    preg_match('/MemAvailable:\s+(\d+)/', $memory_info, $matches);
    $available_memory = isset($matches[1]) ? $matches[1] : '';

    // 计算已用内存
    $used_memory = $total_memory - $available_memory;

    // 格式化输出内存大小、已用内存和剩余内存
    $total_memory_formatted = formatBytes($total_memory);
    $used_memory_formatted = formatBytes($used_memory);
    $available_memory_formatted = formatBytes($available_memory);

    // 获取系统平均负载
    $load_average = sys_getloadavg();
    $load_average = implode(", ", $load_average);

    // 获取硬盘使用状况
    $total_space = disk_total_space('/');
    $used_space = disk_total_space('/') - disk_free_space('/');
    $disk_usage = round(($used_space / $total_space) * 100, 2);

    // 构建系统信息数组
    $system_info = array(
        '服务器当前时间' => $current_time,
        '服务器已运行时间' => $uptime,
        '内存大小' => $total_memory_formatted,
        '已用内存' => $used_memory_formatted,
        '可用内存' => $available_memory_formatted,
        '系统平均负载' => $load_average,
        '硬盘使用状况' => $disk_usage . '%'
    );

    return $system_info;
}
function executeCommand($command) {
	/*
    $allowed_commands = ['ls', 'pwd', 'whoami', 'date'];
    if (in_array($command, $allowed_commands)) {
        $output = shell_exec($command . ' 2>&1');
        return nl2br(htmlspecialchars($output));
    } else {
        return "不允许执行该命令。";
    }
	*/
	$output = shell_exec($command );
    return nl2br(htmlspecialchars($output));

}

if($_GET['s']=="json") {
    $system_info = getSystemInfo();
    header('Content-Type: application/json');
    echo json_encode($system_info);
    exit;
}

if (isset($_POST['command'])) {
    $command = trim($_POST['command']);
    $result = executeCommand($command);
    echo $result;
    exit;
}

?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Info</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fc;
            color: #333;
            margin: 0;
            padding: 0;
        }
        h1 {
            text-align: center;
            color: #4CAF50;
            margin-top: 20px;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .info-item {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .info-item span {
            display: inline-block;
            margin-right: 15px;
            font-size: 16px;
        }
		.command-input {
            margin-top: 20px;
        }
        .command-output {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            white-space: pre-wrap; /* 保持格式 */
        }
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            .info-item {
                padding: 12px;
            }
            h1 {
                font-size: 24px;
            }
        }
    </style>
    <script>
       $(document).ready(function() {
            setInterval(function() {
                $.ajax({
                    url: '<?php echo $_SERVER['PHP_SELF']; ?>?s=json', 
                    dataType: 'json',
                    success: function(data) {
                        $('#current_time').html('<strong>服务器当前时间:</strong> ' + data['服务器当前时间']);
                        $('#uptime').html('<strong>已运行时间:</strong> ' + data['服务器已运行时间']);
                        $('#total_memory').html('<strong>内存:</strong> ' + data['内存大小']);
                        $('#used_memory').html('<strong>已用:</strong> ' + data['已用内存']);
                        $('#available_memory').html('<strong>可用:</strong> ' + data['可用内存']);
                        $('#load_average').html('<strong>系统平均负载:</strong> ' + data['系统平均负载']);
                        $('#disk_usage').html('<strong>硬盘使用状况:</strong> ' + data['硬盘使用状况']);
                    }
                });
            }, 1000); // 每秒钟执行一次Ajax请求

            // 处理命令提交
            $('#commandForm').on('submit', function(e) {
                e.preventDefault(); // 阻止表单默认提交
                var command = $('#commandInput').val();
                $.ajax({
                    type: 'POST',
                    url: '<?php echo $_SERVER['PHP_SELF']; ?>',
                    data: { command: command },
                    success: function(response) {
                        $('#commandOutput').html(response);
                    }
                });
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <h1>服务器信息</h1>
        <div class="info-item" id="current_time"></div>
        <div class="info-item" id="uptime"></div>
<div class="info-item">
	<span id="total_memory"></span>
	<span id="used_memory"></span>
	<span id="available_memory"></span>
</div>

        <div class="info-item" id="load_average"></div>
        <div class="info-item" id="disk_usage"></div>

		<div class="info-item command-input">
            <form id="commandForm">
                <input type="text" id="commandInput" placeholder="输入Linux命令" required>
                <button type="submit">执行命令</button>
            </form>
        </div>
        <div class="command-output" id="commandOutput"></div>

    </div>
</body>
</html>









