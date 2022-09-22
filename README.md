# Certbot Hooks

#### 介绍
创建此项目是为了存储将在certbot（Let's Encrypt）的证书创建和生成过程中使用的钩子脚本。
以下是文件列表、用途和使用说明。

#### 软件架构
1. domain.ini 是配置域名信息
2. 支持2级域名，3级域名等等，按里面的格式书写
3. 支持阿里云，腾讯云和Cloudflare
4. 服务器需要支持php

#### 安装教程

1. 安装certboot，确保服务器能正常运行 /usr/bin/certbot
2. 将整个目录复制到服务器
3. 安装php，修改 hook.sh 里的php目录，改成你服务器上的php目录

#### 使用说明

1. 配置domain.ini里的域名
2. 申请新证书：编辑new.sh，里面的域名在domain.ini都需要有正确配置，之后运行 ./new.sh
3. 更新证书：直接运行 ./renew.sh

#### 贡献
如果你想贡献创建一个问题并且如果你知道你在做什么创建一个 PR。



