<?php
declare(strict_types=1);

final class MvcTest extends DbTestCase
{
    public function testAllAdminResourceColumnsExist(): void
    {
        $resources = require dirname(__DIR__, 2) . '/admin/resources.php';
        foreach ($resources as $key => $resource) {
            if (isset($resource['custom'])) continue;
            $columns = array_column(fetch_all(self::$pdo,
                "SELECT column_name FROM information_schema.columns WHERE table_schema='public' AND table_name=:table",
                ['table'=>$resource['table']]), 'column_name');
            $expected = array_unique([...array_keys($resource['fields']), ...$resource['list'], ...($resource['search']??[]), ...($resource['filters']??[])]);
            $this->assertSame([], array_values(array_diff($expected,$columns)), $key);
        }
    }

    public function testCrudFiltersEscapeWildcardsAndSupportsOrdering(): void
    {
        $resource=(require dirname(__DIR__,2).'/admin/resources.php')['products'];
        foreach(['Kaos%','Kaos B','Vinyl'] as $name)crud_insert(self::$pdo,$resource,[
            'name'=>$name,'slug'=>slugify($name),'category'=>$name==='Vinyl'?'musik':'kaos','price'=>'100000','is_limited'=>false,
        ]);
        $this->assertSame(1,crud_list(self::$pdo,$resource,'%')['total']);
        $this->assertSame(2,crud_list(self::$pdo,$resource,'',['category'=>'kaos'])['total']);
        $this->assertSame(1,crud_list(self::$pdo,$resource,'',['category'=>'musik'])['total']);
    }

    public function testPublicOrdersEnforceQuotaAndServerComputedTotal(): void
    {
        $event=$this->insert("INSERT INTO events(title,slug,city,start_at,ticket_price,quota,status) VALUES ('Konser','konser','Jakarta',now()+interval '30 days',125000,2,'published')");
        $input=['event_id'=>(string)$event,'full_name'=>'Budi','email'=>'BUDI@example.com','phone'=>'08123456789','quantity'=>'2','notes'=>''];
        $first=order_ticket(self::$pdo,$input);
        $this->assertTrue($first['ok']);
        $this->assertSame('250000.00',fetch_one(self::$pdo,'SELECT total_price FROM ticket_orders WHERE id=?',[$first['order_id']])['total_price']);
        $this->assertSame('Sisa tiket tidak mencukupi.',order_ticket(self::$pdo,$input)['errors']['quantity']);
    }

    public function testWorkflowAndNewsSearch(): void
    {
        $event=$this->insert("INSERT INTO events(title,slug,city,start_at,status) VALUES ('K','k','Jakarta',now()-interval '1 day','published')");
        $track=$this->insert("INSERT INTO tracks(title) VALUES ('Lagu')");
        setlist_add(self::$pdo,$event,$track,false);
        $this->assertCount(1,setlist_items(self::$pdo,$event));
        setlist_remove(self::$pdo,$event,1);
        $this->assertSame([],setlist_items(self::$pdo,$event));
        $this->insert("INSERT INTO posts(title,slug,body,status,published_at) VALUES ('Diskon 50%','diskon','Teks','published',now()-interval '1 day')");
        $this->assertCount(1,search_site(self::$pdo,'%')['posts']);
        $this->assertNull(maps_embed_url('https://www.google.com.evil.test/maps?q=x'));
    }

    public function testPublicPagesRenderDatabaseDataAndFormControls(): void
    {
        $this->insert("INSERT INTO members(name,position,sort_order) VALUES ('Anggota Uji','Bass',1)");
        $this->insert("INSERT INTO products(name,slug,category,price,stock) VALUES ('Produk Uji','produk-uji','musik',90000,3)");
        $this->insert("INSERT INTO events(title,slug,city,start_at,ticket_price,status) VALUES ('Konser Uji','konser-uji','Jakarta',now()+interval '30 days',100000,'published')");
        require_once dirname(__DIR__,2).'/app/public-controller.php';
        foreach (['cerita-band'=>'Anggota Uji','toko'=>'Produk Uji','tur-event'=>'Konser Uji'] as $page=>$text) {
            ob_start();public_page($page);$html=(string)ob_get_clean();
            $this->assertStringContainsString($text,$html);
            $this->assertStringNotContainsString('<?xml',$html);
        }
        $this->assertStringContainsString('name="event_id"',$html);
        $this->assertStringContainsString('action="actions/order-ticket.php"',$html);
    }
}
