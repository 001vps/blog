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
    // 自定义函数，用于格式化字节大小
    

    // 获取服务器当前时间
    $current_time = date("Y-m-d H:i:s");

    // 获取服务器已运行时间
    $uptime = file_get_contents('/proc/uptime');
    $uptime_seconds = (int)trim($uptime);
    $uptime = date('Y-m-d H:i:s', time() - $uptime_seconds);

    // 获取内存大小
    $memory_info = file_get_contents('/proc/meminfo');
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

// 调用函数获取系统信息
$system_info = getSystemInfo();

// 输出获取的信息
foreach ($system_info as $key => $value) {
    echo "$key: $value <br>";
}
?>