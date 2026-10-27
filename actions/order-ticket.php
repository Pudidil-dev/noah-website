<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
handle_public_form('tiket','order_ticket','../tur-event.php#pemesanan-event','Pesanan tiket diterima. Silakan tunggu konfirmasi admin.');
