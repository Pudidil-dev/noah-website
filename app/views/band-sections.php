<?php
declare(strict_types=1);

function band_sections(string $page,PDO $pdo,DOMXPath $xp):void
{
    switch($page){
        case 'index':
            view_replace($xp,'//*[@id="player"]/div',view_html('featured-album',['album'=>featured_album($pdo)]));
            view_replace($xp,'//section[.//h2[text()="Konser Mendatang"]]//div[contains(concat(" ",normalize-space(@class)," ")," grid-3 ")]',view_html('event-cards',['events'=>upcoming_events($pdo,'concert',3),'home'=>true]));
            view_replace($xp,'//section[.//h2[text()="Berita Terbaru"]]//div[contains(concat(" ",normalize-space(@class)," ")," grid-3 ")]',view_html('news-cards',['posts'=>news_posts($pdo,3)]));
            band_form($xp,'//form[contains(@class,"newsletter")]',view_html('newsletter-form',['from'=>'index']),'actions/subscribe.php');
            view_flash($xp,'langganan');
            break;
        case 'cerita-band':
            view_replace($xp,'//div[contains(concat(" ",normalize-space(@class)," ")," timeline ")]',view_html('timeline',['rows'=>milestones_by_type($pdo,'history')]));
            view_replace($xp,'//section[.//h2[text()="Kenali Anggota"]]//div[contains(concat(" ",normalize-space(@class)," ")," grid-3 ")]',view_html('members',['rows'=>active_members($pdo)]));
            view_replace($xp,'//div[contains(concat(" ",normalize-space(@class)," ")," stats ")]',view_html('awards',['rows'=>milestones_by_type($pdo,'award')]));
            break;
        case 'diskografi':
            view_replace($xp,'//section[contains(@class,"section-kecil")]//div[contains(concat(" ",normalize-space(@class)," ")," grid-3 ")]',view_html('album-cards',['albums'=>albums_for_grid($pdo)]));
            view_replace($xp,'//table[contains(@class,"tabel")]/tbody',view_html('singles',['rows'=>singles($pdo)]));
            break;
        case 'tur-event':
            $events=upcoming_events($pdo,'concert');
            view_replace($xp,'//*[@id="konser-mendatang"]//div[contains(concat(" ",normalize-space(@class)," ")," grid-auto ")]',view_html('event-cards',['events'=>$events,'home'=>false]));
            view_replace($xp,'//section[.//h2[text()="Arsip Event"]]//div[contains(concat(" ",normalize-space(@class)," ")," grid-auto ")]',view_html('event-archive',['events'=>past_events($pdo)]));
            view_replace($xp,'//*[@id="arsip-setlist"]',view_html('setlist',['groups'=>setlists($pdo)]));
            band_form($xp,'//*[@id="pemesanan-event"]//form',view_html('ticket-form',['events'=>array_values(array_filter($events,'event_available'))]),'actions/order-ticket.php');
            band_form($xp,'//form[contains(@class,"banner-form")]',view_html('newsletter-form',['from'=>'tur-event']),'actions/subscribe.php');
            $map = maps_embed_url($events[0]['maps_url'] ?? null);
            view_replace($xp,'//*[@id="pemesanan-event"]//div[contains(concat(" ",normalize-space(@class)," ")," map-wrap ")]',view_html('event-map',['url'=>$map]));
            view_flash($xp,'tiket');view_flash($xp,'langganan');
            break;
        case 'berita-media':
            $featured=featured_post($pdo);
            if($featured)view_replace($xp,'//*[@id="artikel-hiatus"]',view_html('featured-post',['post'=>$featured]));
            $q=is_string($_GET['q']??null)?trim($_GET['q']):'';
            $pageNo=is_string($_GET['p']??null)&&ctype_digit($_GET['p'])?min(1000000,max(1,(int)$_GET['p'])):1;
            view_replace($xp,'//*[@id="berita-terbaru"]//div[contains(concat(" ",normalize-space(@class)," ")," grid-auto ")]',view_html('news-cards',['posts'=>news_posts($pdo,NEWS_PER_PAGE,($pageNo-1)*NEWS_PER_PAGE,true)]));
            view_replace($xp,'//*[@id="berita-terbaru"]//div[contains(concat(" ",normalize-space(@class)," ")," pagination ")]',view_html('news-pagination',['pages'=>(int)ceil(count_news($pdo,true)/NEWS_PER_PAGE),'current'=>$pageNo]));
            view_replace($xp,'//section[.//h2[text()="Di Balik Layar"]]//div[contains(concat(" ",normalize-space(@class)," ")," grid-auto ")]',view_html('news-cards',['posts'=>behind_scenes($pdo)]));
            view_replace($xp,'//*[@id="galeri-foto"]//div[contains(concat(" ",normalize-space(@class)," ")," galeri-foto ")]',view_html('gallery',['rows'=>media_items($pdo,'photo'),'filter'=>true]));
            view_replace($xp,'//*[@id="materi-pers"]//div[contains(concat(" ",normalize-space(@class)," ")," card ")]',view_html('press-kit',['rows'=>media_items($pdo,'press_kit')]));
            if($q!=='')view_replace($xp,'//*[@id="berita-terbaru"]',view_html('news-search',['q'=>$q,'found'=>search_site($pdo,$q)]));
            $slug=is_string($_GET['artikel']??null)?$_GET['artikel']:'';
            if($slug!==''){
                $article=fetch_one($pdo,"SELECT * FROM posts WHERE slug=:slug AND status='published' AND published_at<=now()",['slug'=>$slug]);
                if($article)view_replace($xp,'//*[@id="berita-terbaru"]',view_html('news-article',['post'=>$article]));
            }
            break;
        case 'sahabat-noah':
            view_replace($xp,'//section[.//h2[text()="Cerita Sahabat"]]//div[contains(concat(" ",normalize-space(@class)," ")," grid-auto ")]',view_html('fan-stories',['rows'=>approved_submissions($pdo,'story',6)]));
            view_replace($xp,'//section[.//h2[text()="Karya Sahabat"]]//div[contains(concat(" ",normalize-space(@class)," ")," galeri-foto ")]',view_html('gallery',['rows'=>approved_submissions($pdo,'artwork',12),'filter'=>false]));
            view_replace($xp,'//section[.//h2[text()="Komunitas Regional"]]//div[contains(concat(" ",normalize-space(@class)," ")," grid-auto ")]',view_html('community-cards',['rows'=>communities($pdo)]));
            view_replace($xp,'//section[.//h2[text()="Event Komunitas"]]//div[contains(concat(" ",normalize-space(@class)," ")," grid-auto ")]',view_html('community-events',['rows'=>upcoming_events($pdo,'community')]));
            band_form($xp,'//*[@id="form-cerita"]//form',view_html('fan-form',[]),'actions/submit-fan.php');view_flash($xp,'cerita');
            break;
        case 'toko':
            $regular=products($pdo,false);
            view_replace($xp,'//ul[contains(concat(" ",normalize-space(@class)," ")," kategori-list ")]',view_html('product-filters',['counts'=>product_counts($regular),'total'=>count($regular)]));
            view_replace($xp,'//*[@id="produk"]',view_html('product-cards',['rows'=>$regular]));
            view_replace($xp,'//section[.//h2[text()="Edisi Terbatas"]]//div[contains(concat(" ",normalize-space(@class)," ")," grid-2 ")]',view_html('limited-products',['rows'=>products($pdo,true)]));
            break;
    }
}

function band_form(DOMXPath $xp,string $selector,string $html,string $action):void
{
    $node=$xp->query($selector)->item(0);if(!$node)return;
    view_replace($xp,$selector,$html);
    $node->setAttribute('action',$action);
    $node->setAttribute('method','post');
}
