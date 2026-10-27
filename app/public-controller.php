<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

/** Controller publik: muat view HTML dan isi area dinamis dari model query. */
function public_page(string $page): void
{
    $allowed=['index','cerita-band','diskografi','tur-event','berita-media','sahabat-noah','toko'];
    if(!in_array($page,$allowed,true)){http_response_code(404);return;}
    $template=__DIR__."/views/band/$page.html";
    $html=file_get_contents($template);
    if($html===false)throw new RuntimeException('Template halaman tidak ditemukan.');
    $html=preg_replace('/\b(href|action)="([a-z-]+)\.html(?=[#"?])/', '$1="$2.php', $html);
    $html=str_replace('&copy; 2024 NOAH', '&copy; '.date('Y').' NOAH', $html);
    $dom=new DOMDocument('1.0','UTF-8');
    $previous=libxml_use_internal_errors(true);
    $dom->loadHTML($html,LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();libxml_use_internal_errors($previous);
    $xpath=new DOMXPath($dom);
    require_once __DIR__.'/views/band-sections.php';
    band_sections($page,db(),$xpath);
    $output=$dom->saveHTML();
    if ($output === false) {
        throw new RuntimeException('Halaman tidak bisa dirender.');
    }
    header('Content-Type: text/html; charset=UTF-8');
    echo str_replace(' viewbox=', ' viewBox=', $output);
}

/** Replace children only so CSS classes and IDs on the original wrapper remain intact. */
function view_replace(DOMXPath $xpath,string $selector,string $html):void
{
    $node=$xpath->query($selector)->item(0);
    if(!$node)return;
    while($node->firstChild)$node->removeChild($node->firstChild);
    $fragment=new DOMDocument('1.0','UTF-8');
    $previous=libxml_use_internal_errors(true);
    if (!$fragment->loadHTML('<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div id="__fragment__">'.$html.'</div></body></html>')) {
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        throw new RuntimeException('Fragmen tampilan tidak valid.');
    }
    libxml_clear_errors();libxml_use_internal_errors($previous);
    $container=$fragment->getElementById('__fragment__');
    if(!$container)throw new RuntimeException('Fragmen HTML tidak valid.');
    foreach(iterator_to_array($container->childNodes) as $child)$node->appendChild($node->ownerDocument->importNode($child,true));
}

function view_html(string $template,array $vars):string
{
    extract($vars,EXTR_SKIP);
    ob_start();
    require __DIR__.'/views/'.$template.'.php';
    return (string)ob_get_clean();
}

function view_flash(DOMXPath $xpath,string $form):void
{
    $message=flash($form);$error=flash($form.'_error');
    $node=$xpath->query(match($form){
        'cerita' => '//*[@id="form-cerita"]',
        'tiket' => '//*[@id="pemesanan-event"]',
        default => '//*[@id="komunitas" or @id="langganan"]',
    })->item(0);
    if(!$node||(!$message&&!$error))return;
    $p=$node->ownerDocument->createElement('p');$p->setAttribute('class','form-feedback '.($error?'error':'ok'));
    $p->appendChild($node->ownerDocument->createTextNode($error??$message));$node->insertBefore($p,$node->firstChild);
}
