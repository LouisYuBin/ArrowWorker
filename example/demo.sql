create database if not exists ArrowWorker default charset utf8 collate	utf8_general_ci;
use ArrowWorker;
create table if not exists project(
id int auto_increment primary key,
itemName varchar(100) default null comment '名称',
itemIntro text default null comment '项目介绍',
author varchar(100) default null comment '作者',
authorIntro text default null comment '作者介绍'
) engine=innodb charset=utf8 comment '项目介绍';