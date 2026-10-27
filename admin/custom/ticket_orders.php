<?php
declare(strict_types=1);
$pdo=db();
$eventId=is_string($_GET['event_id']??null)&&ctype_digit($_GET['event_id'])?(int)$_GET['event_id']:null;
$status=in_array($_GET['status']??null,ORDER_STATUSES,true)?$_GET['status']:null;
if(admin_post()){
    csrf_require();
    $id=(int)($_POST['id']??0);
    if(($_POST['do']??'')==='delete'){
        if(admin_can($admin,['super_admin'])){order_delete($pdo,$id);flash('ok','Pesanan dihapus.');}
        else flash('error','Hanya super admin yang bisa menghapus pesanan.');
    }elseif(order_set_status($pdo,$id,is_string($_POST['status']??null)?$_POST['status']:'')) flash('ok','Status pesanan diperbarui.');
    else flash('error','Status tidak valid.');
    redirect('index.php?r=ticket_orders');
}
admin_render('custom/ticket_orders',['title'=>'Pesanan Tiket','eventId'=>$eventId,'status'=>$status,'orders'=>order_list($pdo,$eventId,$status),'events'=>fk_options($pdo,'events','title')]);
