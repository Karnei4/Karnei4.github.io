<?php
header('Content-Type: text/xml; charset=utf-8');
print "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\r\n";
?>
<items>
<?php
//$url=$_GET['name'];
$url=str_replace("*","&",$url);
$url="https://fe.svc.iptv.rt.ru/CacheClientJson/json/VodPackage/list_movies?locationId=700001&from=0&to=2000&packageId=1000";
$page = curl($url);


//$page=str_replace("<!--TBegin:","<!--dle_image_begin:http://o.zfilm-hd666.monster",$page);


preg_match_all ("/logo\"\: \"(.*?)\"\,/",$page,$pic);
preg_match_all ("/name\"\: \"(.*?)\"/",$page,$title);
preg_match_all ("/ifn\"\: \"hls(.*?)film\/variant\.m3u8/",$page,$link);

		
		 
$a="https://zabava-htvod.cdn.ngenix.net/hls";
$b="http://sdp.svc.iptv.rt.ru:8080/images/";

$klv=count($link[1])-1;
for ($i = 0; $i <= 2000; $i++)
  {

	 
?>
<channel>
<title><![CDATA[ <?php echo $title[1][$i] ?> ]]></title>
<stream_url><?php echo $a.$link[1][$i] ?>film/variant.m3u8</stream_url>
<description><![CDATA[<center></br><img src="<?php echo $b.$pic[1][$i] ?>" height="280" width="200"</br></br></br><?php echo $title[1][$i] ?> </center>]]></description>
<logo_30x30><![CDATA[<?php echo $b.$pic[1][$i] ?>]]></logo_30x30>
</channel>
<?php
  }  

$url=$_GET['name'];
$url=str_replace("*","&",$url);
$page = curl($url);

//$content=str_replace("amp;","",$content);
//$content=str_replace("&","*",$content);
//$page = iconv('cp1251', 'utf-8', $page);

preg_match_all ("/<\/span><\/li>
                                                    <li><a href=\"(.*?)\"/",$page,$next);

		$next[1]=str_replace("&","*",$next[1]);
		 
$a="http://kinoteatr.kg";
$b="http://kinoteatr.kg/site/view?id=";

$klv=count($next[1])-1;
for ($i = 0; $i <= $klv; $i++)
  {

	 
?>
<channel>
<title><![CDATA[Далее]]></title>
<playlist_url><![CDATA[http://sp-social.ru/29/tch.php?name=<?php echo $next [1][$i] ?>]]></playlist_url>
<description><![CDATA[<center></br><img src="http://sp-social.ru/pic/next.png" height="200" width="200"</br></br></br>Далее</center>]]></description>
<logo_30x30><![CDATA[http://sp-social.ru/pic/next.png]]></logo_30x30>
</channel>
<?php
  }  

////////////////////////////////////////////////////////////////////
function GetRegexs($text, $pattern, $group=1) {
    if (preg_match($pattern, $text, $matches))
      return $matches[$group];
    return "";
}

function curl($url, $post='', $mode=array()) {
     
    $defaultmode = array('charset' => 'utf-8', 'ssl' => 0, 'cookie' => 1, 'headers' => 0, 'useragent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:59.0) Gecko/20100101 Firefox/59.0');
     
    foreach ($defaultmode as $k => $v) {
    if (!isset($mode[$k]) ) {
    $mode[$k] = $v;
    }
    }
     
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, $mode['headers']);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, $mode['useragent']);
    curl_setopt($ch, CURLOPT_ENCODING, $mode['charset']);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 200);
    if ($post) {
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    if ($mode['cookie']) {
    //curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookies.txt');
    //curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookies.txt');
    }
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    if ($mode['ssl']) {
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
    }

?>
</items>