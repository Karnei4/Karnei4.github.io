<?php
if(!defined('XMLCMS')) exit();
$div="";
if(!empty($_GET["id"])){
	$PLUGIN=getInfoPlugin($_GET["id"]);
	$TITLE=$PLUGIN["name"]." ";
	if($PLUGIN["enabled"]){
		$Channels=[];
		include dirname(__FILE__)."/plugin/$PLUGIN[id]/$PLUGIN[id].php";
			//print_r($Channels);
		if(!$_ISPC){			
			if(!empty($div)) $_PL["notify"]=$div;
			for($i=0;$i<count($Channels);$i++){
				if(!empty($Channels[$i]["search_on"])&&empty($Channels[$i]["logo_30x30"])) $Channels[$i]["logo_30x30"]="$siteurl/include/templates/images/edit.png";
				elseif(empty($Channels[$i]["playlist_url"])&&empty($Channels[$i]["stream_url"])&&empty($Channels[$i]["logo_30x30"]))
					$Channels[$i]["logo_30x30"]="$siteurl/include/templates/images/1px.png";
			}
			$_CH=array_merge($_CH,$Channels);
			return;
		}
		$tp=file_get_contents(dirname(__FILE__)."/plugin/$PLUGIN[id]/page.xml");
		for($i=0;$i<count($Channels)&&$i<$siteperpage;$i++){
			$div.=chToHtml($Channels[$i],$tp);
		}
	}
	else $div.="Плагин отключен или отсутствует!<br>";
}
$content.="$divpc$div";