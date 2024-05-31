sudo -i sudo add-apt-repository ppa:mc3man/trusty-media

sudo apt update

apt install ffmpeg

mkdir bili

cd bili

wget https://github.com/BililiveRecorder/BililiveRecorder/releases/download/v2.6.2/BililiveRecorder-CLI-linux-x64.zip
wget http://123.haodianxin.cn/olive/BililiveRecorder-CLI-linux-x64.zip

apt install unzip 

unzip BililiveRecorder-CLI-linux-x64.zip

chmod +x ./BililiveRecorder.Cli

wget http://123.haodianxin.cn/olive/d.sh
