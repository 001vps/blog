#!/bin/sh


find . -name "*.flv" -print0 | while IFS= read -r -d $'\0' file; do
  curl -k -F "file=@${file}" -F "token=uze8t9syem86eomiuzh0" -F "model=2"  -X POST "https://tmp-cli.vx-cdn.com/app/upload_cli"
  rm "${file}"
  echo "上传文件 ${file} 完成"
done