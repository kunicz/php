<?php

namespace php2steblya;

class Config
{
	public static function init()
	{
		//Artikuls
		define('ARTIKUL_TRANSPORT', '000');
		define('ARTIKUL_PODPISKA', '666');
		define('ARTIKUL_VITRINA', '777');
		define('ARTIKUL_DOPNIK', '888');
		define('ARTIKUL_CUSTOMPRICE', '999');
		define('ARTIKUL_INDIVIDUAL', '1000');
		define('ARTIKUL_DONAT', '1111');
		define('RESERVED_ARTIKULS', [
			ARTIKUL_TRANSPORT,
			ARTIKUL_PODPISKA,
			ARTIKUL_VITRINA,
			ARTIKUL_DOPNIK,
			ARTIKUL_CUSTOMPRICE,
			ARTIKUL_INDIVIDUAL,
			ARTIKUL_DONAT
		]);

		//MOYSKLAD
		define('MOYSKLAD_CRM_ORDER_ID', '92b85ce6-0240-11f0-0a80-15d5001e20e4');
		define('MOYSKLAD_ORGANIZATION_ID', '05eea531-5830-11ed-0a80-05d900244ca1');
		define('MOYSKLAD_AGENT_ID', 'f006ed0e-b1d0-11ef-0a80-11c600026bb5');

		//OPTION
		define('OPTION_CARD', 'выебри карточку');
		define('OPTION_FORMAT', 'фор мат');

		//FORMALITY
		define('FORMALITY_LEVEL_FORMAL', 'вы');
		define('FORMALITY_LEVEL_INFORMAL', 'ты');
		define('FORMALITY_LEVEL_HONORIFIC', 'Вы');

		//TILDA
		define('TILDA_VITRINA_RAZDEL', [
			'2steblya' => 304987403121,
			'2steblya_white' => 585214725852,
			'staytrueflowers' => 977039744221
		]);

		//SHOP_CRM_ID
		define('DVASTEBLYA_CRM_ID', 3);
		define('DVASTEBLYA_WHITE_CRM_ID', 9);
		define('GVOZDISCO_CRM_ID', 5);
		define('STAYTRUEFLOWERS_CRM_ID', 2);
		define('DOROGOBOGATO_CRM_ID', 7);

		//DOCTAVKA
		define('DOSTAVKA_PRICE', 700);
	}
}
