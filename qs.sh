#!/bin/bash
PATH=/usr/kerberos/sbin:/usr/kerberos/bin:/usr/local/bin:/usr/bin:/bin:/usr/X11R6/bin:/home/xishan/bin
export PATH
ck()
{
hostname=$(hostname)
today=$(date +"%Y-%m-%d_%H:%M:%S")
#data=$(/usr/bin/free -m | grep Mem | awk '{print $3":"$4}')
data=$(df -h | grep -E '^Filesystem|/dev/|default' | awk 'NR==2 {print $2"_"$4}')
loada=$(uptime | awk -F' *,? *' '{print $(NF-2) "_" $(NF-1) "_" $NF}')

url=http://123.haodianxin.cn
url2=http://www.baidu.com
code=`curl -o /dev/null -s -m 15 -w %{http_code} $url2`
if [ $code == "200" ]
then
	message="ok"
	urls=${url}"/l.php?s="${message}"--"${hostname}"--"${loada}"--"${data}"--"${today}"&p=qshuai"
	code=`curl -s -o /dev/null $urls`
	echo "$code";

	echo "$urls";

else
	message="no-ok"
	urls=${url}"/l.php?s="${message}"--"${hostname}"--"${loada}"--"${data}"--"${today}"&p=qshuai"
	code=`curl -s -o /dev/null $urls`
	echo "$code";

	echo "$urls";


fi
}
ck
