<?php
declare(strict_types=1);

const PRODUCT_CATEGORIES=['kaos'=>'Kaos','hoodie'=>'Hoodie','aksesoris'=>'Aksesoris','musik'=>'CD & Vinyl'];
const ALBUM_TYPES=['studio'=>'Studio','kompilasi'=>'Kompilasi','soundtrack'=>'Soundtrack'];
const NEWS_PER_PAGE=6;

function like_param(string $q):string {return '%'.str_replace(['!','%','_'],['!!','!%','!_'],$q).'%';}
function upcoming_events(PDO $pdo,string $type,?int $limit=null):array
{
    return fetch_all($pdo,"SELECT e.*,COALESCE((SELECT SUM(t.quantity) FROM ticket_orders t WHERE t.event_id=e.id AND t.status<>'cancelled'),0) AS sold FROM events e WHERE e.type=:type AND e.status='published' AND e.start_at>=now() ORDER BY e.start_at".($limit===null?'':' LIMIT '.max(1,$limit)),['type'=>$type]);
}
function event_available(array $event):bool {return $event['quota']===null||(int)$event['sold']<(int)$event['quota'];}
function past_events(PDO $pdo):array {return fetch_all($pdo,"SELECT * FROM events WHERE type='concert' AND status='published' AND start_at<now() ORDER BY start_at DESC");}
function setlists(PDO $pdo):array
{
    return group_setlists(fetch_all($pdo,"SELECT e.id,e.title,e.venue,e.start_at,e.description,s.position,s.is_encore,t.title AS track FROM events e JOIN setlist_items s ON s.event_id=e.id JOIN tracks t ON t.id=s.track_id WHERE e.status='published' AND e.start_at<now() ORDER BY e.start_at DESC,s.position"));
}
function group_setlists(array $rows):array
{
    $groups=[];
    foreach($rows as $row){
        $id=(int)$row['id'];
        $groups[$id]??=['title'=>$row['title'],'venue'=>$row['venue'],'start_at'=>$row['start_at'],'description'=>$row['description'],'main'=>[],'encore'=>[]];
        $groups[$id][db_bool($row['is_encore'])?'encore':'main'][]=['position'=>(int)$row['position'],'title'=>$row['track']];
    }
    return array_values($groups);
}
function maps_embed_url(?string $url):?string
{
    if($url===null)return null;
    $p=parse_url($url);
    return is_array($p)&&($p['scheme']??'')==='https'&&($p['host']??'')==='www.google.com'&&str_starts_with($p['path']??'','/maps')?$url:null;
}
function featured_post(PDO $pdo):?array {return fetch_one($pdo,"SELECT * FROM posts WHERE category='news' AND status='published' AND published_at<=now() AND is_featured ORDER BY published_at DESC LIMIT 1");}
function news_posts(PDO $pdo,int $limit,int $offset=0,bool $excludeFeatured=false):array
{
    $sql="SELECT * FROM posts WHERE category='news' AND status='published' AND published_at<=now()".($excludeFeatured?' AND NOT is_featured':'').' ORDER BY published_at DESC LIMIT :n OFFSET :o';
    $s=$pdo->prepare($sql);$s->bindValue('n',max(1,$limit),PDO::PARAM_INT);$s->bindValue('o',max(0,$offset),PDO::PARAM_INT);$s->execute();return $s->fetchAll();
}
function count_news(PDO $pdo,bool $excludeFeatured=false):int
{return (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE category='news' AND status='published' AND published_at<=now()".($excludeFeatured?' AND NOT is_featured':''))->fetchColumn();}
function behind_scenes(PDO $pdo,int $limit=3):array {return fetch_all($pdo,"SELECT * FROM posts WHERE category='behind_scenes' AND status='published' AND published_at<=now() ORDER BY published_at DESC LIMIT ".max(1,$limit));}
function media_items(PDO $pdo,string $type):array {return fetch_all($pdo,'SELECT * FROM media WHERE type=:type ORDER BY created_at,id',['type'=>$type]);}
function search_site(PDO $pdo,string $q):array
{
    $q=mb_substr(trim($q),0,100);
    if($q==='')return ['posts'=>[],'albums'=>[],'events'=>[]];
    $like=like_param($q);
    return [
        'posts'=>fetch_all($pdo,"SELECT title,slug FROM posts WHERE status='published' AND published_at<=now() AND (title ILIKE :a ESCAPE '!' OR excerpt ILIKE :b ESCAPE '!') ORDER BY published_at DESC LIMIT 10",['a'=>$like,'b'=>$like]),
        'albums'=>fetch_all($pdo,"SELECT title,slug FROM albums WHERE title ILIKE :a ESCAPE '!' ORDER BY release_date DESC LIMIT 10",['a'=>$like]),
        'events'=>fetch_all($pdo,"SELECT title,city,start_at FROM events WHERE status='published' AND (title ILIKE :a ESCAPE '!' OR city ILIKE :b ESCAPE '!') ORDER BY start_at DESC LIMIT 10",['a'=>$like,'b'=>$like]),
    ];
}
function attach_tracks(PDO $pdo,array $albums):array
{
    if(!$albums)return [];
    $ids='{'.implode(',',array_map(fn($a)=>(int)$a['id'],$albums)).'}';
    $tracks=fetch_all($pdo,'SELECT album_id,track_no,title,duration_sec FROM (SELECT t.*,row_number() OVER(PARTITION BY album_id ORDER BY track_no) rn FROM tracks t WHERE track_no IS NOT NULL AND album_id=ANY(CAST(:ids AS bigint[]))) s WHERE rn<=3 ORDER BY album_id,track_no',['ids'=>$ids]);
    return array_map(fn($album)=>$album+['tracks'=>array_values(array_filter($tracks,fn($t)=>(int)$t['album_id']===(int)$album['id']))],$albums);
}
function albums_for_grid(PDO $pdo):array {return attach_tracks($pdo,fetch_all($pdo,"SELECT * FROM albums WHERE type IN ('studio','kompilasi') ORDER BY release_date DESC NULLS LAST,id"));}
function featured_album(PDO $pdo):?array {$album=fetch_one($pdo,'SELECT * FROM albums WHERE is_featured ORDER BY release_date DESC LIMIT 1');return $album===null?null:attach_tracks($pdo,[$album])[0];}
function album_links(array $album):array {$a=json_decode((string)($album['streaming_links']??'{}'),true);return is_array($a)?$a:[];}
function singles(PDO $pdo):array {return fetch_all($pdo,'SELECT t.title,t.single_year,a.title AS album FROM tracks t LEFT JOIN albums a ON a.id=t.album_id WHERE t.single_year IS NOT NULL ORDER BY t.single_year DESC,t.title');}
function active_members(PDO $pdo):array {return fetch_all($pdo,'SELECT * FROM members WHERE is_active ORDER BY sort_order,id');}
function milestones_by_type(PDO $pdo,string $type):array {return fetch_all($pdo,'SELECT * FROM milestones WHERE type=:type ORDER BY sort_order,year,id',['type'=>$type]);}
function communities(PDO $pdo):array {return fetch_all($pdo,'SELECT * FROM communities ORDER BY member_count DESC,city');}
function approved_submissions(PDO $pdo,string $type,int $limit):array {return fetch_all($pdo,"SELECT s.*,c.city FROM fan_submissions s LEFT JOIN communities c ON c.id=s.community_id WHERE s.status='approved' AND s.type=:type ORDER BY s.created_at DESC LIMIT ".max(1,$limit),['type'=>$type]);}
function products(PDO $pdo,bool $limited):array {return fetch_all($pdo,'SELECT * FROM products WHERE is_limited=:limited ORDER BY created_at DESC,id',['limited'=>$limited]);}
function product_counts(array $items):array {return array_count_values(array_column($items,'category'));}
function badge_class(string $badge):string {return $badge==='Terbatas'?'badge-limited':'badge-baru';}
