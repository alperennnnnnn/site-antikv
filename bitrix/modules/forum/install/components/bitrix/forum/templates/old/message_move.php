<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

	if (($arParams["TMPLT_SHOW_MENU"] == "BOTH") || ($arParams["TMPLT_SHOW_MENU"] == "TOP"))
	{
	$APPLICATION->IncludeComponent(
		"bitrix:forum.menu",
		"",
		array(
			"FID" =>  $arResult["FID"],
			"TID" =>  $arResult["TID"],
			"PAGE_NAME" =>  $arResult["PAGE_NAME"],
			
			"URL_TEMPLATES_INDEX" => $arResult["URL_TEMPLATES_INDEX"],
			"URL_TEMPLATES_FORUMS"	=>	$arResult["URL_TEMPLATES_FORUMS"],
			"URL_TEMPLATES_READ" => $arResult["URL_TEMPLATES_READ"],
			"URL_TEMPLATES_LIST" =>  $arResult["URL_TEMPLATES_LIST"],
			"URL_TEMPLATES_PROFILE_VIEW" =>  $arResult["URL_TEMPLATES_PROFILE_VIEW"],
			"URL_TEMPLATES_SUBSCR_LIST" =>  $arResult["URL_TEMPLATES_SUBSCR_LIST"],
			"URL_TEMPLATES_ACTIVE" =>  $arResult["URL_TEMPLATES_ACTIVE"],
			"URL_TEMPLATES_SEARCH" =>  $arResult["URL_TEMPLATES_SEARCH"],
			"URL_TEMPLATES_HELP" =>  $arResult["URL_TEMPLATES_HELP"],
			"URL_TEMPLATES_RULES" =>  $arResult["URL_TEMPLATES_RULES"],
			"URL_TEMPLATES_USER_LIST" =>  $arResult["URL_TEMPLATES_USER_LIST"],
			"URL_TEMPLATES_PM_LIST" =>  $arResult["URL_TEMPLATES_PM_LIST"],
			"URL_TEMPLATES_PM_EDIT" =>  $arResult["URL_TEMPLATES_PM_EDIT"],
			"URL_TEMPLATES_PM_FOLDER" =>  $arResult["URL_TEMPLATES_PM_FOLDER"],
			
			"PATH_TO_AUTH_FORM" =>  $arResult["PATH_TO_AUTH_FORM"],
			"FID_RANGE" => $arParams["FID"],
			"SHOW_FORUM_ANOTHER_SITE" =>  $arParams["SHOW_FORUM_ANOTHER_SITE"],
			"AJAX_TYPE" => $arParams["AJAX_TYPE"],
			
			"CACHE_TIME" => $arResult["CACHE_TIME"],
			"CACHE_TYPE" => $arResult["CACHE_TYPE"],
			
			"TMPLT_SHOW_AUTH_FORM" =>  $arParams["TMPLT_SHOW_AUTH_FORM"],
		),
		$component
	);
	?><br/><?
	}
		
		$APPLICATION->IncludeComponent(
			"bitrix:forum.message.move",
			"",
			array(
				"FID" =>  $arResult["FID"],
				"TID" =>  $arResult["TID"],
				"MID" =>  $arResult["MID"],
				
				"URL_TEMPLATES_INDEX" =>  $arResult["URL_TEMPLATES_INDEX"],
				"URL_TEMPLATES_FORUMS"	=>	$arResult["URL_TEMPLATES_FORUMS"],
				"URL_TEMPLATES_LIST" =>  $arResult["URL_TEMPLATES_LIST"],
				"URL_TEMPLATES_READ" => $arResult["URL_TEMPLATES_READ"],
				"URL_TEMPLATES_MESSAGE" =>  $arResult["URL_TEMPLATES_MESSAGE"],
				"URL_TEMPLATES_PROFILE_VIEW" => $arResult["URL_TEMPLATES_PROFILE_VIEW"],
				"URL_TEMPLATES_TOPIC_SEARCH" => $arResult["URL_TEMPLATES_TOPIC_SEARCH"],
				
				"PATH_TO_SMILE" => $arParams["PATH_TO_SMILE"],
				"PATH_TO_ICON" => $arParams["PATH_TO_ICON"],
				"WORD_LENGTH" => $arParams["WORD_LENGTH"],
				"IMAGE_SIZE" => $arParams["IMAGE_SIZE"], 
				"DATE_FORMAT" =>  $arResult["DATE_FORMAT"],
				"DATE_TIME_FORMAT" =>  $arResult["DATE_TIME_FORMAT"],
				
				"SET_NAVIGATION" => $arResult["SET_NAVIGATION"],
				"DISPLAY_PANEL" => $arParams["DISPLAY_PANEL"],
				"SET_TITLE" => $arResult["SET_TITLE"],
				"CACHE_TIME" => $arResult["CACHE_TIME"],
				
				"SHOW_TAGS" => $arParams["SHOW_TAGS"],
			),
			$component
		);
	if (($arParams["TMPLT_SHOW_MENU"] == "BOTH") || ($arParams["TMPLT_SHOW_MENU"] == "BOTTOM"))
	{
	?><br/><?
	$APPLICATION->IncludeComponent(
		"bitrix:forum.menu",
		"",
		array(
			"FID" =>  $arResult["FID"],
			"TID" =>  $arResult["TID"],
			"PAGE_NAME" =>  $arResult["PAGE_NAME"],
			
			"URL_TEMPLATES_INDEX" => $arResult["URL_TEMPLATES_INDEX"],
			"URL_TEMPLATES_FORUMS"	=>	$arResult["URL_TEMPLATES_FORUMS"],
			"URL_TEMPLATES_READ" => $arResult["URL_TEMPLATES_READ"],
			"URL_TEMPLATES_LIST" =>  $arResult["URL_TEMPLATES_LIST"],
			"URL_TEMPLATES_PROFILE_VIEW" =>  $arResult["URL_TEMPLATES_PROFILE_VIEW"],
			"URL_TEMPLATES_SUBSCR_LIST" =>  $arResult["URL_TEMPLATES_SUBSCR_LIST"],
			"URL_TEMPLATES_ACTIVE" =>  $arResult["URL_TEMPLATES_ACTIVE"],
			"URL_TEMPLATES_SEARCH" =>  $arResult["URL_TEMPLATES_SEARCH"],
			"URL_TEMPLATES_HELP" =>  $arResult["URL_TEMPLATES_HELP"],
			"URL_TEMPLATES_RULES" =>  $arResult["URL_TEMPLATES_RULES"],
			"URL_TEMPLATES_USER_LIST" =>  $arResult["URL_TEMPLATES_USER_LIST"],
			"URL_TEMPLATES_PM_LIST" =>  $arResult["URL_TEMPLATES_PM_LIST"],
			"URL_TEMPLATES_PM_EDIT" =>  $arResult["URL_TEMPLATES_PM_EDIT"],
			"URL_TEMPLATES_PM_FOLDER" =>  $arResult["URL_TEMPLATES_PM_FOLDER"],
			
			"PATH_TO_AUTH_FORM" =>  $arResult["PATH_TO_AUTH_FORM"],
			"FID_RANGE" => $arParams["FID"],
			"SHOW_FORUM_ANOTHER_SITE" =>  $arParams["SHOW_FORUM_ANOTHER_SITE"],
			"AJAX_TYPE" => $arParams["AJAX_TYPE"],
			
			"CACHE_TIME" => $arResult["CACHE_TIME"],
			"CACHE_TYPE" => $arResult["CACHE_TYPE"],
			
			"TMPLT_SHOW_AUTH_FORM" =>  $arParams["TMPLT_SHOW_AUTH_FORM"],
		),
		$component
	);
	}
?>