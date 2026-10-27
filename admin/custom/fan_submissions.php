<?php
declare(strict_types=1);
$pdo=db();$status=in_array($_GET['status']??null,REVIEW_STATUSES,true)?$_GET['status']:'pending';
if(admin_post()){
    csrf_require();$id=(int)($_POST['id']??0);$action=$_POST['do']??'';
    if($action==='delete'){submission_delete($pdo,$id);flash('ok','Kiriman dihapus.');}
    elseif(is_string($action)&&submission_review($pdo,$id,$action,$admin['id']))flash('ok','Status kiriman diperbarui.');
    else flash('error','Aksi tidak valid.');
    redirect('index.php?r=fan_submissions&status='.rawurlencode($status));
}
admin_render('custom/fan_submissions',['title'=>'Kiriman Fans','status'=>$status,'items'=>submission_list($pdo,$status)]);
