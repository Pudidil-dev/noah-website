<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
$targets=['index'=>'../index.php#komunitas','tur-event'=>'../tur-event.php#langganan'];
$target=$targets[is_string($_POST['from']??null)?$_POST['from']:'']??$targets['index'];
handle_public_form('langganan','subscribe',$target,'Email kamu sudah terdaftar. Terima kasih!');
