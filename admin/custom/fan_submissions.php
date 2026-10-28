<?php
declare(strict_types=1);
$pdo=db();$status=in_array($_GET['status']??null,REVIEW_STATUSES,true)?$_GET['status']:'pending';
if(admin_post()){
    csrf_require();$id=(int)($_POST['id']??0);$action=$_POST['do']??'';
    if($action==='delete'){
        $saved=fetch_one($pdo,'SELECT file_url FROM fan_submissions WHERE id=:id',['id'=>$id]);
        submission_delete($pdo,$id);
        if($saved!==null){
            $uploadConfig=config()['app'];
            delete_fan_upload($saved['file_url'],$uploadConfig['upload_dir'],$uploadConfig['upload_url']);
        }
        flash('ok','Kiriman dihapus.');
    }
    elseif(is_string($action)&&submission_review($pdo,$id,$action,$admin['id']))flash('ok','Status kiriman diperbarui.');
    else flash('error','Aksi tidak valid.');
    redirect('index.php?r=fan_submissions&status='.rawurlencode($status));
}
admin_render('custom/fan_submissions',['title'=>'Kiriman Fans','status'=>$status,'items'=>submission_list($pdo,$status)]);
