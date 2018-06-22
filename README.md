xhgui
-----

xhgui的安装信息可到源项目查看文档：[xhgui](https://github.com/perftools/xhgui)  

[![Latest Stable Version](https://poser.pugx.org/light/xhgui/v/stable.png)](https://packagist.org/packages/light/xhgui)
[![Total Downloads](https://poser.pugx.org/light/xhgui/downloads.png)](https://packagist.org/packages/light/xhgui)
[![Build Status](https://travis-ci.org/light/xhgui.svg?branch=master)](https://travis-ci.org/light/xhgui)

## xhprof

如果是PHP7环境的话，推荐安装[longxinH/xhprof](https://github.com/longxinH/xhprof) 扩展

## 部署

### LNMP环境部署

需要安装mongodb扩展, 如果希望使用udp方式接受数据的话, 可以选择workerman或者swoole, 安装相应依赖的扩展即可

```
$ composer create-project light/xhgui xhgui
``` 

nginx:
```
server {
    listen 80;
    server_name www.php-xhgui.dev php-xhgui.dev;
    root /server/wwwroot/xhprof/webroot;
    index index.php index.html;
    location / {
        try_files $uri $uri/ /index.php?$args;
    }
    location ~ \.php$ {
        try_files $uri = 404;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Docker(推荐)

```bash
$ cp env-docker .env
$ docker-compose build
$ docker-compose up -d
```

> 如果发现nginx的80或者9000端口别占用，可以通过修改 .env 文件中的端口号

查看输出日志:

```bash
$ docker-compose logs -f
```
