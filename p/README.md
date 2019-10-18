<!DOCTYPE html>
<html class="client-nojs" lang="ru" dir="ltr">
<head>
<meta charset="UTF-8"/>
<title>Fork-portal</title>
<script src="/include/templates/js/jquery.js"></script>
<script src="/include/templates/js/adm.js?version=1.0.21"></script>
<script src="/include/templates/js/jquery-ui.js"></script>
<script type="text/javascript" src="/include/templates/js/jHtml/jHtmlArea-0.8.js"></script>
<link rel="Stylesheet" type="text/css" href="/include/templates/js/jquery-ui.css" />
<link rel="Stylesheet" type="text/css" href="/include/templates/js/jHtml/jHtmlArea.css" />
<style>
a:link, a:visited {
   color: #eeeeee;
   text-decoration:underline;
}
a:hover {
   color: #FA5961;
   text-decoration:underline;
}

#menu,
#menu ul {
    list-style: none;
}
#menu {
    float: left;
	width: 100%;
    padding: 0px;
}
#menu > li {
    float: left;
}
#menu li a {
display: block;
    height: 2em; 
    line-height: 2em;
    padding: 0 1.5em;
    text-decoration: none;
}
#menu ul {
    position: absolute;
    display: none;
z-index: 999;
}
#menu li:hover ul {
    display: block;
	padding: 0px;
}
#menu {
    font-family: Arial;
    font-size: 12px;
    background: #2f8be8;
}
#menu > li > a {
    color: #fff;
    font-weight: bold;
}
#menu > li:hover > a {
    background: #2f8be8;
    color: #000;
}
 
/* Submenu
------------------------------------------*/
#menu ul {
    background: #9dcdfd;
}
#menu ul li a {
    color: #000;
}
#menu ul li:hover a {
    background: #2f8be8;
}
.button {
    width: auto;
    height: 30px;
    background: #e3dbdb;
    padding: 5px;
    text-align: center;
    border-radius: 2px;
    color: black;
    font-weight: bold;
	text-decoration: none;
}
</style>
</head>
<body style="width:97%;text-align:center;background-color:gray;font-size:15px;">
<div style="width:1150px;min-height:600px; margin: auto;background-color:white;padding:10px;">
	<div>
		<div style="float:left;"><img src="https://Karnei4.github.io/p/include/templates/images/logo.png" style="width:200px;" /></div>
		<div style="text-align:center;font-size:2em;">Karnei4.github.io Fork-portal</div>
	</div>	
	<div style="clear:both;"><hr></div>
	<div id="site" style="min-height:460px;">
		<div style="text-align:left;"><ul id='menu' style='background-color:;'><li><a href='https://Karnei4.github.io/p/' style='color:;' ><img align='left' src='https://Karnei4.github.io/p/assets/portal-logo.png' onerror="this.style.display='none'" id='img_t' width=20 height=18 style='margin: 2px;'/> Fork-portal</a></li><li><a href='https://Karnei4.github.io/p/?do=/module&id=auth&act=login' style='color:;' > Авторизация</a></li><li><a href='cmd:info()' style='color:;' > </a></li></ul><br clear='both'></div>
		<hr>
	
		<div id="leftmenu" style='float:left;text-align: left; border-right: 1px solid gray;padding-right:5px;width:170px;'></div>
		<div id='contents' style='text-align: left;margin-left:180px;max-height:654px;overflow-y:auto;overflow-x:hidden;'>Нет страниц или нет доступа к этому разделу сайта!<script>
	var siteurl='https://Karnei4.github.io/p/';
	applyStyles({"cssid":{"menu":{"enabled":"1"},"content":{"before":"<center><img src=\"https:\/\/Karnei4.github.io\/p\/assets\/portal-logo.png\" style=\"width:300px; height:200px;\"><img><\/center>"},"site":{"background":"url(http:\/\/fork-portal.ru\/assets\/fon.jpg)","color":"#eeeeee"},"infoList":{"color":"#eeeeee"},"navigate":{"color":"#eeeeee"}},"channels":{"parent":{"default":{"background":"none","color":"#eeeeee"},"selected":{"background":"none  ","color":"#FA5961"}}}});</script></div>
	</div>

	
	
</div>	
<div style="width:1150px;clear:both;margin: auto;background-color:white;padding:40px 10px;">
		<hr>
		<div style="float:left;">© Fork-portal Karnei4.github.io 2019</div>
</div>
</body>
</html>
