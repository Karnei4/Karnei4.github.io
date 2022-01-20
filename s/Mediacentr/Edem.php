<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('allow-origin: *');
header("Access-Control-Allow-Headers: ACCEPT, CONTENT-TYPE, X-CSRF-TOKEN");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
//header("Content-type: application/x-mpegurl");
//header("Content-Disposition: attachment; filename=Playlist_edem_epg_ico3.m3u"); //Забрать файлом снимаем комент
$key = $_GET["key"];   
   
if(isset($url)) {
  $m3ufile = file_get_contents($url);
} else {
$url = "http://epg.it999.ru/edem_epg_ico3.m3u8";
  $m3ufile = file_get_contents($url);
}

//$re = '/#(EXTINF|EXTM3U):(.+?)[,]\s?(.+?)[\r\n]+?((?:https?|rtmp):\/\/(?:\S*?\.\S*?)(?:[\s)\[\]{};"\'<]|\.\s|$))/';
$re = '/#EXTINF:(.+?)[,]\s?(.+?)[\r\n]+?((?:https?|rtmp):\/\/(?:\S*?\.\S*?)(?:[\s)\[\]{};"\'<]|\.\s|$))/';
//$attributes = '/([a-zA-Z0-9\-]+?)="([^"]*)"/';
$attributes = '/([a-zA-Z0-9\-\_]+?)="([^"]*)"/';
$host = 'http://hello.akadatel.com/';//Домен меняем на свой при надобности
$m3ufile = str_replace('http://localhost/', $host, $m3ufile);
$m3ufile = str_replace('00000000000000', $key, $m3ufile);

print_r($m3ufile);// выводит листом m3u
?>
