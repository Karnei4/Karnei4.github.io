<?php
/*
	Class for working with ipgeobase.ru geo database.

	Copyright (C) 2013, Vladislav Ross

	This library is free software; you can redistribute it and/or
	modify it under the terms of the GNU Lesser General Public
	License as published by the Free Software Foundation; either
	version 2.1 of the License, or (at your option) any later version.

	This library is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
	Lesser General Public License for more details.

	You should have received a copy of the GNU Lesser General Public
	License along with this library; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA

    E-mail: vladislav.ross@gmail.com
	URL: https://github.com/rossvs/ipgeobase.php
	
*/
/*
 * @class IPGeoBase
 * @brief Класс для работы с текстовыми базами ipgeobase.ru
 * @see example.php
 *
 * Определяет страну, регион и город по IP для России и Украины
 */
class IPGeoBase 
{
	private $fhandleCIDR, $fhandleCities, $fSizeCIDR, $fsizeCities;

    /*
     * @brief Конструктор
     *
     * @param CIDRFile файл базы диапазонов IP (cidr_optim.txt)
     * @param CitiesFile файл базы городов (cities.txt)
     */
	function __construct($CIDRFile = false, $CitiesFile = false)
	{
		if(!$CIDRFile)
		{
			$CIDRFile = dirname(__FILE__) . '/cidr_optim.txt';			
		}
		if(!$CitiesFile)
		{
			$CitiesFile = dirname(__FILE__) . '/cities.txt';			
		}
		$this->fhandleCIDR = fopen($CIDRFile, 'r') or die("Cannot open $CIDRFile");
		$this->fhandleCities = fopen($CitiesFile, 'r') or die("Cannot open $CitiesFile");
		$this->fSizeCIDR = filesize($CIDRFile);
		$this->fsizeCities = filesize($CitiesFile);
	}

    /*
     * @brief Получение информации о городе по индексу
     * @param idx индекс города
     * @return массив или false, если не найдено
     */
	private function getCityByIdx($idx)
	{
		rewind($this->fhandleCities);
		while(!feof($this->fhandleCities))
		{
			$str = fgets($this->fhandleCities);
			$arRecord = explode("\t", trim($str));
			if($arRecord[0] == $idx)
			{
				return array(	'city' => $arRecord[1],
								'region' => $arRecord[2],
								'district' => $arRecord[3],
								'lat' => $arRecord[4],
								'lng' => $arRecord[5]);
			}
		}
		return false;
	}

    /*
     * @brief Получение гео-информации по IP
     * @param ip IPv4-адрес
     * @return массив или false, если не найдено
     */
	function getRecord($ip)
	{
		$ip = sprintf('%u', ip2long($ip));
		rewind($this->fhandleCIDR);
		$rad = floor($this->fSizeCIDR / 2);
		$pos = $rad;
		while(fseek($this->fhandleCIDR, $pos, SEEK_SET) != -1)			
		{
			if($rad) 
			{
				$str = fgets($this->fhandleCIDR);
			}
			else
			{
				rewind($this->fhandleCIDR);
			}
			
			$str = fgets($this->fhandleCIDR);
			
			if(!$str)
			{
				return false;
			}
			
			$arRecord = explode("\t", trim($str));

			$rad = floor($rad / 2);
			if(!$rad && ($ip < $arRecord[0] || $ip > $arRecord[1]))
			{
				return false;
			}
			
			if($ip < $arRecord[0])
			{
				$pos -= $rad;
			}
			elseif($ip > $arRecord[1])
			{
				$pos += $rad;
			}
			else
			{
				$result = array('range' => $arRecord[2], 'cc' => $arRecord[3]);
											
				if($arRecord[4] != '-' && $cityResult = $this->getCityByIdx($arRecord[4]))
				{
					$result += $cityResult;
				}
				return $result;
			}
		}
		return false;		
	}
}

$arGeoCodes = array(
    'AU' => 'Австралия',
    'AT' => 'Австрия',
    'AZ' => 'Азербайджан',
    'AX' => 'Аландские острова',
    'AL' => 'Албания',
    'DZ' => 'Алжир',
    'VI' => 'Американские Виргинские острова',
    'AS' => 'Американское Самоа',
    'AI' => 'Ангилья',
    'AO' => 'Ангола',
    'AD' => 'Андорра',
    'AQ' => 'Антарктида',
    'AG' => 'Антигуа и Барбуда',
    'AR' => 'Аргентина',
    'AM' => 'Армения',
    'AW' => 'Аруба',
    'AF' => 'Афганистан',
    'BS' => 'Багамы',
    'BD' => 'Бангладеш',
    'BB' => 'Барбадос',
    'BH' => 'Бахрейн',
    'BZ' => 'Белиз',
    'BY' => 'Белоруссия',
    'BE' => 'Бельгия',
    'BJ' => 'Бенин',
    'BM' => 'Бермуды',
    'BG' => 'Болгария',
    'BO' => 'Боливия',
    'BQ' => 'Бонэйр, Синт-Эстатиус и Саба',
    'BA' => 'Босния и Герцеговина',
    'BW' => 'Ботсвана',
    'BR' => 'Бразилия',
    'IO' => 'Британская территория в Индийском океане',
    'VG' => 'Британские Виргинские острова',
    'BN' => 'Бруней',
    'BF' => 'Буркина-Фасо',
    'BI' => 'Бурунди',
    'BT' => 'Бутан',
    'VU' => 'Вануату',
    'VA' => 'Ватикан',
    'GB' => 'Великобритания',
    'HU' => 'Венгрия',
    'VE' => 'Венесуэла',
    'UM' => 'Внешние малые острова (США)',
    'TL' => 'Восточный Тимор',
    'VN' => 'Вьетнам',
    'GA' => 'Габон',
    'HT' => 'Гаити',
    'GY' => 'Гайана',
    'GM' => 'Гамбия',
    'GH' => 'Гана',
    'GP' => 'Гваделупа',
    'GT' => 'Гватемала',
    'GF' => 'Гвиана',
    'GN' => 'Гвинея',
    'GW' => 'Гвинея-Бисау',
    'DE' => 'Германия',
    'GG' => 'Гернси',
    'GI' => 'Гибралтар',
    'HN' => 'Гондурас',
    'HK' => 'Гонконг',
    'GD' => 'Гренада',
    'GL' => 'Гренландия',
    'GR' => 'Греция',
    'GE' => 'Грузия',
    'GU' => 'Гуам',
    'DK' => 'Дания',
    'JE' => 'Джерси',
    'DJ' => 'Джибути',
    'DM' => 'Доминика',
    'DO' => 'Доминиканская Республика',
    'CD' => 'ДР Конго',
    'EU' => 'Европейский союз',
    'EG' => 'Египет',
    'ZM' => 'Замбия',
    'EH' => 'Западная Сахара',
    'ZW' => 'Зимбабве',
    'IL' => 'Израиль',
    'IN' => 'Индия',
    'ID' => 'Индонезия',
    'JO' => 'Иордания',
    'IQ' => 'Ирак',
    'IR' => 'Иран',
    'IE' => 'Ирландия',
    'IS' => 'Исландия',
    'ES' => 'Испания',
    'IT' => 'Италия',
    'YE' => 'Йемен',
    'CV' => 'Кабо-Верде',
    'KZ' => 'Казахстан',
    'KY' => 'Каймановы острова',
    'KH' => 'Камбоджа',
    'CM' => 'Камерун',
    'CA' => 'Канада',
    'QA' => 'Катар',
    'KE' => 'Кения',
    'CY' => 'Кипр',
    'KG' => 'Киргизия',
    'KI' => 'Кирибати',
    'TW' => 'Китайская Республика',
    'KP' => 'КНДР',
    'CN' => 'КНР',
    'CC' => 'Кокосовые острова',
    'CO' => 'Колумбия',
    'KM' => 'Коморы',
    'CR' => 'Коста-Рика',
    'CI' => 'Кот-д’Ивуар',
    'CU' => 'Куба',
    'KW' => 'Кувейт',
    'CW' => 'Кюрасао',
    'LA' => 'Лаос',
    'LV' => 'Латвия',
    'LS' => 'Лесото',
    'LR' => 'Либерия',
    'LB' => 'Ливан',
    'LY' => 'Ливия',
    'LT' => 'Литва',
    'LI' => 'Лихтенштейн',
    'LU' => 'Люксембург',
    'MU' => 'Маврикий',
    'MR' => 'Мавритания',
    'MG' => 'Мадагаскар',
    'YT' => 'Майотта',
    'MO' => 'Макао',
    'MK' => 'Македония',
    'MW' => 'Малави',
    'MY' => 'Малайзия',
    'ML' => 'Мали',
    'MV' => 'Мальдивы',
    'MT' => 'Мальта',
    'MA' => 'Марокко',
    'MQ' => 'Мартиника',
    'MH' => 'Маршалловы Острова',
    'MX' => 'Мексика',
    'FM' => 'Микронезия',
    'MZ' => 'Мозамбик',
    'MD' => 'Молдавия',
    'MC' => 'Монако',
    'MN' => 'Монголия',
    'MS' => 'Монтсеррат',
    'MM' => 'Мьянма',
    'NA' => 'Намибия',
    'NR' => 'Науру',
    'NP' => 'Непал',
    'NE' => 'Нигер',
    'NG' => 'Нигерия',
    'NL' => 'Нидерланды',
    'NI' => 'Никарагуа',
    'NU' => 'Ниуэ',
    'NZ' => 'Новая Зеландия',
    'NC' => 'Новая Каледония',
    'NO' => 'Норвегия',
    'AE' => 'ОАЭ',
    'OM' => 'Оман',
    'BV' => 'Остров Буве',
    'IM' => 'Остров Мэн',
    'CK' => 'Острова Кука',
    'NF' => 'Остров Норфолк',
    'CX' => 'Остров Рождества',
    'PN' => 'Острова Питкэрн',
    'SH' => 'Острова Святой Елены, Вознесения и Тристан-да-Кунья',
    'PK' => 'Пакистан',
    'PW' => 'Палау',
    'PS' => 'Палестинская национальная администрация',
    'PA' => 'Панама',
    'PG' => 'Папуа — Новая Гвинея',
    'PY' => 'Парагвай',
    'PE' => 'Перу',
    'PL' => 'Польша',
    'PT' => 'Португалия',
    'PR' => 'Пуэрто-Рико',
    'CG' => 'Республика Конго',
    'KR' => 'Республика Корея',
    'RE' => 'Реюньон',
    'RU' => 'Россия',
    'RW' => 'Руанда',
    'RO' => 'Румыния',
    'SV' => 'Сальвадор',
    'WS' => 'Самоа',
    'SM' => 'Сан-Марино',
    'ST' => 'Сан-Томе и Принсипи',
    'SA' => 'Саудовская Аравия',
    'SZ' => 'Свазиленд',
    'MP' => 'Северные Марианские острова',
    'SC' => 'Сейшельские Острова',
    'BL' => 'Сен-Бартелеми',
    'MF' => 'Сен-Мартен',
    'PM' => 'Сен-Пьер и Микелон',
    'SN' => 'Сенегал',
    'VC' => 'Сент-Винсент и Гренадины',
    'KN' => 'Сент-Китс и Невис',
    'LC' => 'Сент-Люсия',
    'RS' => 'Сербия',
    'SG' => 'Сингапур',
    'SX' => 'Синт-Мартен',
    'SY' => 'Сирия',
    'SK' => 'Словакия',
    'SI' => 'Словения',
    'SB' => 'Соломоновы Острова',
    'SO' => 'Сомали',
    'SD' => 'Судан',
    'SU' => 'СССР',
    'SR' => 'Суринам',
    'US' => 'США',
    'SL' => 'Сьерра-Леоне',
    'TJ' => 'Таджикистан',
    'TH' => 'Таиланд',
    'TZ' => 'Танзания',
    'TC' => 'Тёркс и Кайкос',
    'TG' => 'Того',
    'TK' => 'Токелау',
    'TO' => 'Тонга',
    'TT' => 'Тринидад и Тобаго',
    'TV' => 'Тувалу',
    'TN' => 'Тунис',
    'TM' => 'Туркмения',
    'TR' => 'Турция',
    'UG' => 'Уганда',
    'UZ' => 'Узбекистан',
    'UA' => 'Украина',
    'WF' => 'Уоллис и Футуна',
    'UY' => 'Уругвай',
    'FO' => 'Фарерские острова',
    'FJ' => 'Фиджи',
    'PH' => 'Филиппины',
    'FI' => 'Финляндия',
    'FK' => 'Фолклендские острова',
    'FR' => 'Франция',
    'PF' => 'Французская Полинезия',
    'TF' => 'Французские Южные и Антарктические Территории',
    'HM' => 'Херд и Макдональд',
    'HR' => 'Хорватия',
    'CF' => 'ЦАР',
    'TD' => 'Чад',
    'ME' => 'Черногория',
    'CZ' => 'Чехия',
    'CL' => 'Чили',
    'CH' => 'Швейцария',
    'SE' => 'Швеция',
    'SJ' => 'Шпицберген и Ян-Майен',
    'LK' => 'Шри-Ланка',
    'EC' => 'Эквадор',
    'GQ' => 'Экваториальная Гвинея',
    'ER' => 'Эритрея',
    'EE' => 'Эстония',
    'ET' => 'Эфиопия',
    'ZA' => 'ЮАР',
    'GS' => 'Южная Георгия и Южные Сандвичевы острова',
    'SS' => 'Южный Судан',
    'JM' => 'Ямайка',
    'JP' => 'Япония'
);