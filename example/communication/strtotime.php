<?php
$message_queue_key = ftok("/home/louis/github/ArrowWorker/App/Runtime/ArrowWorker.queue", 'A');
$message_queue = msg_get_queue($message_queue_key, 0666);

$message_queue_key1 = ftok("/home/louis/github/ArrowWorker/App/Runtime/app.queue", 'A');
$message_queue1 = msg_get_queue($message_queue_key1, 0666);


$message_queue_status = msg_stat_queue($message_queue);
print_r($message_queue_status);

$message_queue_status1 = msg_stat_queue($message_queue1);
print_r($message_queue_status1);


?>
