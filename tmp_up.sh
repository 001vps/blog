#!/bin/sh


find . -name "*.flv" -print0 | while IFS= read -r -d $'\0' file; do
   response=$(curl -k -F "file=@${file}" -F "token=zyssaelck8uy826yk4ab" -F "model=2" -X POST "https://tmp-cli.vx-cdn.com/app/upload_cli")
   echo "${file} ${response}"
    echo "${file} ${response}" >> log.txt
  rm "${file}"
  echo "上传文件 ${file} 完成"
done
