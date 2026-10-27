<?php
declare(strict_types=1);
$pdo=db();$eventId=is_string($_GET['event_id']??null)&&ctype_digit($_GET['event_id'])?(int)$_GET['event_id']:null;
if(admin_post()){
    csrf_require();
    if($eventId===null)admin_not_found();
    try{
        if(($_POST['do']??'')==='remove')setlist_remove($pdo,$eventId,(int)($_POST['position']??0));
        else setlist_add($pdo,$eventId,(int)($_POST['track_id']??0),!empty($_POST['is_encore']));
        flash('ok','Setlist diperbarui.');
    }catch(PDOException | InvalidArgumentException){flash('error','Lagu atau event tidak valid.');}
    redirect('index.php?r=setlist&event_id='.$eventId);
}
admin_render('custom/setlist',['title'=>'Setlist','eventId'=>$eventId,'events'=>fk_options($pdo,'events','title'),
    'items'=>$eventId===null?[]:setlist_items($pdo,$eventId),
    'tracks'=>fetch_all($pdo,'SELECT t.id,t.title,a.title AS album FROM tracks t LEFT JOIN albums a ON a.id=t.album_id ORDER BY t.title')]);
