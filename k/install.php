<?php
header('Content-Type: text/html; charset=UTF-8');
?>

<!DOCTYPE html>
<html class="client-nojs" lang="ru" dir="ltr">
<head>
<meta charset="UTF-8"/>
<title>FXML CMS - Установка</title>
</head>
<body style="width:100%;text-align:center;background-color:gray;">
<div style="width:1000px;min-height:600px; margin: auto;background-color:white;padding:10px;">
	<div>
		<div style="float:left;"><img src="/include/templates/images/logo.png" style="width:200px;" /></div>
		<div style="text-align:center;font-size:2em;">FXML CMS - Установка</div>
	</div>	
	<div style="clear:both;"><hr></div>
<div style="text-align:left;">

<?php
if(file_exists("config.php")) exit("Файл config.php уже существует! Удалите его для повторной установки FXML CMS");
if ( ! function_exists('is_https'))
{
    function is_https()
    {
        if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        {
            return TRUE;
        }
        elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        {
            return TRUE;
        }
        elseif ( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')
        {
            return TRUE;
        }
        return FALSE;
    }
}

if(@$_GET["act"]=="setup"){
	$mysqlData=$_POST["mysql"];
	$op=$_POST["op"];
	$div="";
	$err="";
	if(version_compare(PHP_VERSION, '7.0.0','>=')) include_once dirname(__FILE__).'/include/mysql.php';
	define('XMLCMS', true );
	include_once dirname(__FILE__).'/include/functions.php';

	$dbh=mysql_connect($mysqlData["host"], $mysqlData["user"], $mysqlData["password"]) or exit("Не удалось подключится к базе данных MySql");
	$dbn=mysql_select_db($mysqlData["db"]) or exit("Неверное имя базы данных $db");
	mysql_query("set names 'utf8'");
	$dbprefix=$mysqlData["dbprefix"];
	$siteurl=$_POST["op"]["siteurl"];
	if(empty($dbprefix)) exit("Не задан префикс таблиц!");
	
	$sql="CREATE TABLE `{$dbprefix}category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `icon` varchar(256) NOT NULL,
  `title` varchar(120) NOT NULL,
  `seo_url` varchar(120) NOT NULL DEFAULT '',
  `showmain` tinyint(4) NOT NULL,
  `showpage` tinyint(4) NOT NULL DEFAULT 1,
  `access` varchar(20) NOT NULL DEFAULT '0',
  `webplayer` tinyint(4) NOT NULL DEFAULT 0,
  `count` int(11) NOT NULL DEFAULT 0,
  `numsess` int(11) NOT NULL DEFAULT 3,
  `onlyfid` tinyint(1) NOT NULL DEFAULT '0',
  `onlymac` tinyint(1) NOT NULL DEFAULT '0',
  `typeList` varchar(100) NOT NULL DEFAULT 'list',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE `{$dbprefix}device` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mac` varchar(256) NOT NULL COMMENT 'MAC или ForkPlayerID',
  `userid` int(11) NOT NULL,
  `role` int(11) NOT NULL,
  `dateto` int(11) NOT NULL,
  `ip` varchar(15) NOT NULL DEFAULT '',
  `last` int(11) NOT NULL,
  `initial` varchar(512) NOT NULL DEFAULT '',
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `c` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE `{$dbprefix}meta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(255) NOT NULL,
  `key` varchar(256) NOT NULL, 
  `src` longtext NOT NULL DEFAULT '',
  `inc` text NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `{$dbprefix}meta` (`id`, `uid`, `key`, `src`, `inc`) VALUES
(1, '1', 'PluginMeta__[MAINBEFORE]', '<center>Добро пожаловать в наш портал!</center><div style=\"position:absolute;   left:70%;top:80%;font-size:80%;\">Создано в FXML CMS<br>(C) $siteurl ".date("Y")."</div>', '');


CREATE TABLE `{$dbprefix}page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(256) NOT NULL,
  `icon` varchar(256) NOT NULL,
  `category` varchar(256) NOT NULL,
  `description` text NOT NULL DEFAULT '',
  `src` longtext NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` int(11) NOT NULL DEFAULT 0,
  `author` int(11) NOT NULL,
  `seourl` varchar(256) NOT NULL DEFAULT '',
  `sticked` varchar(1) NOT NULL DEFAULT '0',
  `encrypt` varchar(1) NOT NULL DEFAULT '0',
  `is_iptv` varchar(1) NOT NULL DEFAULT '0',
  `view` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE `{$dbprefix}role` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `{$dbprefix}role` (`id`, `name`) VALUES
(1, 'Заблокирован'),
(2, 'Читатель'),
(3, 'Гость'),
(4, 'Пользователь'),
(5, 'Важный пользователь'),
(6, 'Группа 1'),
(7, 'Группа 2'),
(8, 'Группа 3'),
(9, 'Модератор'),
(10, 'Администратор');

CREATE TABLE `{$dbprefix}sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `mac` varchar(32) NOT NULL,
  `initial` varchar(512) NOT NULL DEFAULT '',
  `ua` varchar(256) DEFAULT '',
  `sid` varchar(32) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE `{$dbprefix}users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(120) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(32) NOT NULL,
  `mac` varchar(32) NOT NULL DEFAULT '',
  `role` varchar(3) NOT NULL DEFAULT '4',
  `dateto` int(11) NOT NULL DEFAULT 0,
  `hash` varchar(32) NOT NULL DEFAULT '' COMMENT 'ForkPlayer ID hash',
  `forkplayerid` varchar(256) NOT NULL DEFAULT '' COMMENT 'ForkPlayer ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;";
	$errm="";
	$sql=str_replace("\r","",$sql);
	foreach($result = explode(";\n", $sql) as $key=>$qq){
		$q=str_replace("\n","",trim($result[$key]));		
		if($mysqlData["delete"]){
			preg_match("/(CREATE TABLE `.*?`)/",$q,$a);
			if(isset($a[1])){
				mysql_query(str_replace("CREATE TABLE","DROP TABLE IF EXISTS",$a[1]));
				$errm.=mysql_error();
			}
		}
		mysql_query($q);
		$errm.=mysql_error();
	}
	if(!empty($errm)) exit("Ошибки mysql: $errm");
	
	$secretKey=md5(time()*rand(1000,9999));
$configText="<?php
if(!defined('XMLCMS')) exit();


\$offline_message = 'XML портал закрыт на техническое обслуживание.<br />Пожалуйста, зайдите позже.';
\$display_offline_message = '0';
\$offline_image = '';
\$friendly_links=false;
\$sitename='$op[title]';
\$sitebackground='$op[siteurl]/include/templates/images/fon20.jpg';

\$sitecolor='$op[color]'; //Цвет текста, если пустое то будет использовно системное
\$sitechbkg=''; //Фон ссылки при выделении в rgba, если пустое то будет использовно системное, если none то без фона
\$sitechcolor='$op[chcolor]'; //Цвет текста ссылки при выделении, если пустое то будет использовно системное
\$typelistStart='".$_POST["pltag"]["typeList"]."'; //Вид главной '' - списком, 'start' - плиткой

\$siteicon='$op[siteurl]/include/templates/images/logo.png';
\$sitepageinfo='$op[pageinfo]';
\$siteperpage=50;
\$siteurl= '$op[siteurl]'; //without / in end
\$secret=md5('$secretKey'); // Соль для токенов паролей и авторизаций аккаунтов
\$friendly_links=0;
\$logo='$op[siteurl]/include/templates/images/logo.png';
\$host = '$mysqlData[host]';
\$user = '$mysqlData[user]';
\$password = '$mysqlData[password]';
\$db = '$mysqlData[db]';
\$dbprefix = '$mysqlData[dbprefix]';";
	if(!filter_var($_POST["op"]["email"], FILTER_VALIDATE_EMAIL))  $err.="<br><span style='color:red;'>Email ".$_POST["op"]["email"]." не верный!</span><br>";
	elseif($_POST["op"]["pass1"]!=$_POST["op"]["pass2"]) $div.="Ошибка! Пароли не совпадают<br>";
	elseif(strlen($_POST["op"]["pass1"])<4) $div.="Ошибка! Пароль должен иметь длину не меньше 4!<br>";
	else
	{
		if(file_put_contents("config.php",$configText)) print "<br>Файл config.php создан!<br><a href='$op[siteurl]'>Перейти на главную портала</a><br>";
		else  print "<br>Ошибка записи файла config.php! Выставьте необходимые права папки с сайтом<br>";
		require("config.php");		
		
		if(register_user($_POST["op"]["login"],$_POST["op"]["email"],$_POST["op"]["pass1"],"",10)) $div.="Администратор ".$_POST["op"]["login"]." создан<br>";
		else $div.="Ошибка! ".mysql_error()."<br>";
		
		mysql_query("insert into {$dbprefix}category set title='Информация',icon='',`showmain`='1',access=',0,',`showpage`='1',`typeList`='list',id=1,count=1");
		mysql_query("insert into {$dbprefix}category set title='Мультимедиа',icon='',`showmain`='1',access=',0,',`showpage`='1',`typeList`='list',id=2,count=1");
		
		print mysql_error();
	 
		mysql_query("insert into {$dbprefix}page set category=',2,',src=\"".mysql_real_escape_string(json_encode(["title"=>"Видео с YouTube","channels"=>[["title"=>"Водопад","stream_url"=>"https://www.youtube.com/watch?v=myaU45u0v90","description"=>""],["title"=>"Камин","stream_url"=>"https://www.youtube.com/watch?v=e51bI9GovXY","description"=>""]]]))."\",icon='$op[siteurl]/include/templates/images/logo.png',title='Видео с YouTube',description='', modified=".time().",sticked='0',author=1");
		
		mysql_query("insert into {$dbprefix}page set category=',1,',src=\"".mysql_real_escape_string(json_encode(["title"=>"Приветственная страница","channels"=>[["title"=>"Про FXML CMS!","stream_url"=>"description","description"=>""]]]))."\",icon='$op[siteurl]/include/templates/images/logo.png',title='Приветственная страница',description='Это система управления сайтами для работы ForkPlayer.<br>Основные возможности:<br>Создание и редактирование страниц<br>Настраиваемое Меню сайта которое всегда отображается вверху ваших страниц<br>Персонализация внешнего вида сайта таких как фон, цвет и другие<br>Авторизация и регистрация пользователей, включая быструю через ForkPlayerID<br>Поддержка плагинов (есть с чатом, пользовательскими ссылками, вашей рекламой)<br>Создание категорий (разделов) сайта с доступом по мак адресу, ForkPlayerID, логину<br>Возможность дать доступ на ограниченное время<br>Шифрование ссылок на странице<br>Возможность просмотра вашего портала в виджетах с поддержкой XML или обычном браузере', modified=".time().",sticked='1',author=1");		
		
		print mysql_error();
	}
	
	print $err."<br>".$div;
	exit;
}

?>


<br><form id='formxml' action='?act=setup' method='POST'>
<b>Настройки базы данных mysql</b><br>
<div style='border:1px solid gray;border-radius:10px;padding:15px;'>
Сервер<br>
<input name='mysql[host]' value='localhost' /><br>
Имя пользователя mysql<br>
<input name='mysql[user]' value='' /><br>
Пароль пользователя mysql<br>
<input name='mysql[password]' value='' /><br>
Имя базы данных<br>
<input name='mysql[db]' value='' /><br>
Префикс (если установлено несколько FXML CMS в этой базе)<br>
<input name='mysql[dbprefix]' value='axml_' /><br>
<input type='checkbox' name='mysql[delete]' value='1' /> Удалить таблицы если существуют<br>
</div>
<b>Придумайте аккаунт администратора этого сайта</b><br>
<div style='border:1px solid gray;border-radius:10px;padding:15px;'>
Логин<br>
<input name='op[login]' value='admin' /><br>
Email<br>
<input name='op[email]' value='' /><br>
Пароль<br>
<input name='op[pass1]' value='' /><br>
Повторите пароль<br>
<input name='op[pass2]' value='' /><br>
</div>
<b>Настройки сайта	</b>
<div style='border:1px solid gray;border-radius:10px;padding:15px;'>	
				URL (без / в конце)
				<br>
				<input name='op[siteurl]' style='width:250px;' value='<?=(is_https()?"https":"http")."://$_SERVER[HTTP_HOST]"?>' /><br>
				Название
				<br>
				<input id='pltagid_title' name='op[title]' value='FXML CMS - Мой портал' /><br>
				Описание портала (256 символов, учитывается поисковой системой)
				<br>
				<input id='pltagid_pageinfo' name='op[pageinfo]' style='width:800px;' value='Мой портал на FXML CMS' /><br>
				
				Вид главной страницы
				<input type='radio'  name='pltag[typeList]' value='' /> Список <input type='radio' name='pltag[typeList]'  checked value='start' /> Плитка<br>
				
				Цвет текста<br>
				<input id='pltagid_color' type='color' name='op[color]' value='#eeeeee' /><br>
			
				Цвет текста ссылки при выделении<br>
				<input id='pltagid_chcolor' type='color' name='op[chcolor]' value='#FA5961' /><br>			
				</div>
				<br><input type='submit' value='Установить!' />
				</form><br>
</div>
</body>
</html>
