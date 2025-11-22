FROM ubuntu:latest
RUN apt-get update && apt-get install -y vim \
    && apt-get install -y openssl \
    && apt-get update && apt-get install -y openssh-server

# 以下、コンテナ内に入って手動で作業する用のメモ
# SSL証明書を作成する
# openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /storage/nginx.key -out /storage/nginx.crt -subj "/C=JP/ST=Tokyo/L=Tokyo/O=Localhost/OU=Development/CN=localhost"
# SSHキーを作成する
# ssh-keygen -t rsa -b 4096 -N "" -f /storage/test_rsa