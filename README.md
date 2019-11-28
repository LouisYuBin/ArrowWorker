## Introduction
High performance and componentized http/websocket/tcp/udp/worker server framework based on php 7.2+, swoole 4.4+.

## Server
We provide servers below:
- Http server
- Websocket server
- Tcp server
- Udp server
- Worker group based on multi-process and Coroutine

## Client
The following Client is provided and can be used in servers.
- Http client
- Websocket client(pool)
- Tcp client(pool)
- GRpc client(pool)
- Amqp client(pool)

## Components
Components below is 
- Mysql client(pool)
- Redis client(pool)
- Memory Table
- Queue for process communication.
- Log component
- Worker based on multi-process and coroutine
- Memory table based on swoole table
- Communication component between processes
- Standard Server monitor
- Http Rest Router

### 使用说明
- 启动服务：php main.php start server dev false/true
- 关闭服务：php main.php stop
- 重启服务：php main.php restart

### 系统优化注意事项
- 系统队列设置
    kernel.msgmni=
    kernel.msgmax
    kernel.msgmnb
    
