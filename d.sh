#!/bin/bash

# 定义一个数组

urls=("$@")
find . -type f -name "*.flv" -exec rm {} \;
find . -type f -name "*.txt" -exec rm {} \;

encoded_url=$(basename "${urls[0]}")
decoded_url=${encoded_url//%/\\x}
filename01=$(printf "$decoded_url")
echo "${filename01}"

i=1

# 遍历数组并下载文件
for index in "${!urls[@]}"; do
  # 提取文件名
  filename="0${i}.flv"
  # 下载文件
  curl -g -o "$filename" "${urls[index]}"
  echo "$filename" "${urls[index]}"
  # 输出是第几个元素
  echo "第 $((index+1)) 个元素 " "${urls[index]}"
  /workspaces/blog/bili/BililiveRecorder.Cli tool fix "/workspaces/blog/${filename}" "/workspaces/blog//0${i}${i}.flv"
  i=$((i+1))
done

find . -type f -name "*.flv" ! -name "*p00*" -exec rm {} \;
find . -type f -name "*.txt" -exec rm {} \;

for f in *.flv; do
  echo "file '$f'" >> mylist.txt
done

ffmpeg -f concat -i mylist.txt -c copy "${filename01}"

find . -type f -name "*.flv" -name "*p00*" -exec rm {} \;
find . -type f -name "*.txt" -exec rm {} \;
python -m http.server
