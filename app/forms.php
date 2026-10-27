<?php
declare(strict_types=1);

const FORM_MAX_PER_WINDOW = 5;
const FORM_WINDOW_SEC = 600;
const TICKET_FIELDS = [
    'event_id'=>['type'=>'int','label'=>'Konser','required'=>true,'min'=>1],
    'full_name'=>['type'=>'text','label'=>'Nama lengkap','required'=>true,'max'=>120],
    'email'=>['type'=>'email','label'=>'Email','required'=>true],
    'phone'=>['type'=>'phone','label'=>'Nomor telepon','required'=>true],
    'quantity'=>['type'=>'int','label'=>'Jumlah tiket','required'=>true,'min'=>1,'max'=>10],
    'notes'=>['type'=>'textarea','label'=>'Catatan','max'=>500],
];
const FAN_FIELDS = [
    'name'=>['type'=>'text','label'=>'Nama','required'=>true,'max'=>120],
    'email'=>['type'=>'email','label'=>'Email','required'=>true],
    'type'=>['type'=>'enum','label'=>'Kategori','required'=>true,'options'=>['story','artwork','cover']],
    'content'=>['type'=>'textarea','label'=>'Pesan','required'=>true,'max'=>2000],
];
function is_bot(array $input): bool {return trim(is_string($input['website']??null)?$input['website']:'')!=='';}
function honeypot_field(): string {return '<input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">';}
function tickets_sold(PDO $pdo,int $id):int
{
    return (int)fetch_one($pdo,"SELECT COALESCE(SUM(quantity),0) AS sold FROM ticket_orders WHERE event_id=:id AND status<>'cancelled'",['id'=>$id])['sold'];
}
function order_ticket(PDO $pdo,array $input):array
{
    [$data,$errors]=validate(TICKET_FIELDS,$input);
    if($errors)return ['ok'=>false,'errors'=>$errors];
    return with_transaction($pdo,function(PDO $pdo)use($data):array{
        $event=fetch_one($pdo,"SELECT id,ticket_price,quota FROM events WHERE id=:id AND type='concert' AND status='published' AND start_at>now() AND ticket_price IS NOT NULL FOR UPDATE",['id'=>$data['event_id']]);
        if(!$event)return ['ok'=>false,'errors'=>['event_id'=>'Konser tidak tersedia untuk dipesan.']];
        if($event['quota']!==null&&tickets_sold($pdo,(int)$event['id'])+$data['quantity']>(int)$event['quota'])
            return ['ok'=>false,'errors'=>['quantity'=>'Sisa tiket tidak mencukupi.']];
        $stmt=$pdo->prepare('INSERT INTO ticket_orders(event_id,full_name,email,phone,quantity,total_price,notes) VALUES (:event_id,:full_name,:email,:phone,:quantity,CAST(:price AS numeric)*CAST(:qty AS integer),:notes) RETURNING id');
        $stmt->execute($data+['price'=>$event['ticket_price'],'qty'=>$data['quantity']]);
        return ['ok'=>true,'errors'=>[],'order_id'=>(int)$stmt->fetchColumn()];
    });
}
function submit_fan(PDO $pdo,array $input):array
{
    [$data,$errors]=validate(FAN_FIELDS,$input);
    if($errors)return ['ok'=>false,'errors'=>$errors];
    $pdo->prepare('INSERT INTO fan_submissions(name,email,type,content) VALUES (:name,:email,:type,:content)')->execute($data);
    return ['ok'=>true,'errors'=>[]];
}
function subscribe(PDO $pdo,array $input):array
{
    [$data,$errors]=validate(['email'=>['type'=>'email','label'=>'Email','required'=>true]],$input);
    if($errors)return ['ok'=>false,'errors'=>$errors];
    $pdo->prepare('INSERT INTO subscribers(email) VALUES (:email) ON CONFLICT(email) DO UPDATE SET is_active=TRUE')->execute($data);
    return ['ok'=>true,'errors'=>[]];
}
function handle_public_form(string $name,callable $handler,string $target,string $success):never
{
    csrf_require();
    if(is_bot($_POST)){flash($name,$success);redirect($target);}
    $key='form:'.$name.':'.($_SERVER['REMOTE_ADDR']??'unknown');
    if(throttle_exceeded($key,FORM_MAX_PER_WINDOW,FORM_WINDOW_SEC)){
        flash($name.'_error','Terlalu banyak kiriman. Coba lagi nanti.');redirect($target);
    }
    throttle_record($key,FORM_WINDOW_SEC);
    try {
        $result=$handler(db(),$_POST);
    } catch (Throwable $e) {
        error_log((string)$e);
        flash($name.'_error','Permintaan belum dapat diproses. Coba lagi nanti.');
        redirect($target);
    }
    if($result['ok']){flash($name,$success);unset($_SESSION['old'][$name]);}
    else{
        flash($name.'_error',implode(' ',$result['errors']));
        $_SESSION['old'][$name]=array_map(fn($v)=>is_string($v)?mb_substr($v,0,2000):'',array_diff_key($_POST,['_csrf'=>1,'website'=>1]));
    }
    redirect($target);
}
function old(string $form,string $field):string {return is_string($_SESSION['old'][$form][$field]??null)?$_SESSION['old'][$form][$field]:'';}
