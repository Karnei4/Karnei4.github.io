<?php
header('Content-Type: text/xml; charset=utf-8');
print "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\r\n";
?>
<items>
<?php
$url=$_GET['name'];
//$url="http://giga-film.ru/kino-novinki/";

$page = curl($url);


//$page=str_replace("</span> <a href=\"http://giga-film.ru/xfsearch/","",$page);

preg_match_all ("/<img src=\"http\:\/\/store4kino\.com\/uploads\/posts(.*?)\" alt/",$page,$pic);
preg_match_all ("/\.html\">(.*?)<\/a>/",$page,$title);
preg_match_all ("/short-title\" href=\"(.*?)\"/",$page,$link);
preg_match_all ("/short-desc\">(.*?)<\/div>/",$page,$op);
		
		 
$a="https://ofxru.net";
$b="http://store4kino.com/uploads/posts";

$klv=count($title[1])-1;
for ($i = 0; $i <= 29; $i++)
  {

	 
?>

<channel>
<title><![CDATA[ <?php echo $title[1][$i] ?> ]]></title>
<playlist_url>https://karnei4.github.io/t.php?name=<?php echo $link[1][$i] ?></playlist_url>
<description><![CDATA[<center></br><img src="<?php echo $b.$pic [1][$i] ?>" height="280" width="200"</br></br></br><?php echo $title[1][$i] ?></br></br></br><?php echo $op[1][$i] ?> </center>]]></description>
<logo_30x30><![CDATA[https://lh3.googleusercontent.com/eyZvPnKUtIiXSeLwMM5cLEjHgc0dtO_kYEEdkdYDSKRQQpOzBqBBZmy14arM-oJ47zw]]></logo_30x30>
</channel>
<?php
  }  
 
 
 $url=$_GET['name'];
//$url="http://giga-film.ru/kino-novinki/";

$page = curl($url);

$page=str_replace("</span> <a href=\"http://giga-film.ru/xfsearch/","",$page);


preg_match_all ("/\/page\/(.*?)\/\">/",$page,$str);
preg_match_all ("/<\/span> <a href=\"(.*?)\">/",$page,$next);
		
		 
$a="http://giga-film.ru/kino-novinki/page/";
$b="http://store4kino.com/uploads/posts";

$klv=count($next[1])-1;
for ($i = 0; $i <= 0; $i++)
  {

	 
?>

<channel>
<title><![CDATA[ Далее  ]]></title>
<playlist_url>https://karnei4.github.io/t.php?name=<?php echo $next[1][$i] ?></playlist_url>
<description><![CDATA[<center></br><img src="https://avatars.mds.yandex.net/get-dialogs/1017510/7ac77ef50247e0acb0ea/orig" height="200" width="200"</br></br></br>Далее </center>]]></description>
<logo_30x30><![CDATA[https://avatars.mds.yandex.net/get-dialogs/1017510/7ac77ef50247e0acb0ea/orig]]></logo_30x30>
</channel>
<?php
  }  
////////////////////////////////////////////////////////////////////
// ‘ункци¤ получени¤ значени¤ по указанному регул¤рному выражению
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
    curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookies.txt');
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
