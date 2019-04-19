<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
//echo '<pre>'; print_r($arResult); echo '</pre>';
if (count($arResult) < 1)
	return;

$bManyIblock = array_key_exists("IBLOCK_ROOT_ITEM", $arResult[0]["PARAMS"]);
?>

	<ul id="left-menu">
<?php
	$previousLevel = 0;
	foreach($arResult as $key => $arItem):
 //if ($arItem["TEXT"]= " ") $arItem["TEXT"] = "???";
 log_array($arItem);
  if ($arItem["ITEM_INDEX"] =='0') $arItem["TEXT"] = "Весь каталог";
  if ($arItem["ITEM_INDEX"] =='1') $arItem["TEXT"] = "Распродажа";
  if ($arItem["ITEM_INDEX"] =='2') $arItem["TEXT"] = "Игровые приставки Денди";
  if ($arItem["ITEM_INDEX"] =='3') $arItem["TEXT"] = "Картриджи для Денди";
  if ($arItem["ITEM_INDEX"] =='4') $arItem["TEXT"] = "Аксессуары Денди (8 бит)";
  if ($arItem["ITEM_INDEX"] =='5') $arItem["TEXT"] = "Игровые приставки Sega (16-бит) Mega Drive";
  if ($arItem["ITEM_INDEX"] =='6') $arItem["TEXT"] = "Картриджи Sega(16-bit)";
  if ($arItem["ITEM_INDEX"] =='7') $arItem["TEXT"] = "Картриджи Sega (16-bit) на русском языке.";
  if ($arItem["ITEM_INDEX"] =='8') $arItem["TEXT"] = "Сборники игр Sega(16 bit)";
  if ($arItem["ITEM_INDEX"] =='9') $arItem["TEXT"] = "Аксессуары Sega 16 бит";
  if ($arItem["ITEM_INDEX"] =='10') $arItem["TEXT"] = "Аксессуары для Sony PlayStation и XBOX";
  if ($arItem["ITEM_INDEX"] =='11') $arItem["TEXT"] = "Портативные приставки DVTech / Game Boy";
  if ($arItem["ITEM_INDEX"] =='12') $arItem["TEXT"] = "Картриджи Game Boy Advance на русском языке";
  if ($arItem["ITEM_INDEX"] =='13') $arItem["TEXT"] = "Аксессуары и аккумуляторы для Game Boy и портативных приставок DVTech";
  if ($arItem["ITEM_INDEX"] =='14') $arItem["TEXT"] = "Аксессуары для Sony PSP и PlayStation Vita";
  if ($arItem["ITEM_INDEX"] =='15') $arItem["TEXT"] = "Рули для игровых приставок и компьютеров";
  if ($arItem["ITEM_INDEX"] =='16') $arItem["TEXT"] = "Джойстики для компьютеров и ноутбуков";
  if ($arItem["ITEM_INDEX"] =='17') $arItem["TEXT"] = "Контейнеры и сумки для хранения дисков";
  if ($arItem["ITEM_INDEX"] =='18') $arItem["TEXT"] = "Акссесуары прочие (USB-кабели/переходники ...)";
  if ($arItem["ITEM_INDEX"] =='19') $arItem["TEXT"] = "Картины на холсте размер 30 на 40";
  if ($arItem["ITEM_INDEX"] =='20') $arItem["TEXT"] = "Раскраски по номерам 40 на 50";
  if ($arItem["ITEM_INDEX"] =='21') $arItem["TEXT"] = "Живопись на цветном холсте 40 на 50";
  if ($arItem["ITEM_INDEX"] =='22') $arItem["TEXT"] = "Раскраски на цветном трафарете 50 на 60";
  if ($arItem["ITEM_INDEX"] =='23') $arItem["TEXT"] = "Живопись на трафарете 60 на 80";
  if ($arItem["ITEM_INDEX"] =='24') $arItem["TEXT"] = "Картины на картоне";
  if ($arItem["ITEM_INDEX"] =='25') $arItem["TEXT"] = "Резиночки для плетения";
  if ($arItem["ITEM_INDEX"] =='26') $arItem["TEXT"] = "Скрапбукинг";
  if ($arItem["ITEM_INDEX"] =='27') $arItem["TEXT"] = "Мозаичные панно и картины";
  if ($arItem["ITEM_INDEX"] =='28') $arItem["TEXT"] = "Мозаичные картины со стразами";
  if ($arItem["ITEM_INDEX"] =='29') $arItem["TEXT"] = "Мозаика на подрамнике";
  if ($arItem["ITEM_INDEX"] =='30') $arItem["TEXT"] = "Мозаика со стразами на подрамнике";
  if ($arItem["ITEM_INDEX"] =='31') $arItem["TEXT"] = "Фоторамки и багеты";
  if ($arItem["ITEM_INDEX"] =='32') $arItem["TEXT"] = "Наборы для создания бижутерии";
  if ($arItem["ITEM_INDEX"] =='33') $arItem["TEXT"] = "Аксессуары для хобби";
  if ($arItem["ITEM_INDEX"] =='34') $arItem["TEXT"] = "Наборы для вышивания";
 //echo '<pre>'; print_r($arItem); echo '</pre>';

		if ($previousLevel && $arItem["DEPTH_LEVEL"] < $previousLevel):
			echo str_repeat("1234</ul></li>", ($previousLevel - $arItem["DEPTH_LEVEL"]));
		endif;

		if ($arItem["IS_PARENT"]):
			$i = $key;
			$bHasSelected = $arItem['SELECTED'];
			$childSelected = false;
			if (!$bHasSelected)
			{
				while ($arResult[++$i]['DEPTH_LEVEL'] > $arItem['DEPTH_LEVEL'])
				{
					if ($arResult[$i]['SELECTED'])
					{
						$bHasSelected = $childSelected = true; break;
					}
				}
			}
			
			$className = $nHasSelected ? 'selected' : '';//($bHasSelected ? 'selected' : '');
?>
		<? if ($arItem['DEPTH_LEVEL'] > 1 && !$childSelected && $bHasSelected) :?>
			<li class="current">
			<a class="selected" href="<?=$arItem["LINK"]?>"><?=$arItem["TEXT"]?></a>
			<ul>

		<? else:?>
			<li<?=$bHasSelected ? ' class="selected"' : ''?>>
			<a<?=$bHasSelected ? ' class="selected"' : ''?> href="<?=$arItem["LINK"]?>"><?=$arItem["TEXT"]?></a>
			<ul<?=$bHasSelected || ($bManyIblock && $arItem['DEPTH_LEVEL'] <= 1) ? '' : ' style="display: none;"'?>>
		<? endif?>


<?
		else:
			if ($arItem["PERMISSION"] > "D"):
				$className = $arItem['SELECTED'] ? $arItem['DEPTH_LEVEL'] > 1 ? 'current' : "selected" : '';
?>
		<li<?=$className ? ' class="'.$className.'"' : ''?>>

			<table width="100%" border="0px solid" cellpadding="0" cellspacing="0">
            <tr style="height:40px;">
           <td style='background:url("images/left1.png") no-repeat;' width="6px;">        
            </td>

            
              <td width="25px" valign="center" style="border-top:1px solid #949cff;border-bottom:1px solid #949cff; text-align:left; background:transparent url(images/5.PNG) repeat-x scroll 0 0;">          
                &nbsp<font color='blue'><b>-></b></font> 
                </td>
            <td  valign="center" style="border-top:1px solid #949cff;border-bottom:1px solid #949cff; text-align:left; background:transparent url(images/5.PNG) repeat-x scroll 0 0;">
  
            <a<?if ($arItem['SELECTED']):?> class="selected"<?endif?> href="<?=$arItem["LINK"]?>"><?=$arItem["TEXT"]?></a>
            </td>

            <td width="6px" style='background:url("images/right1.png") no-repeat;'>
		 </td>
         </tr></table>
        </li>
<?
			endif;
		endif;

		$previousLevel = $arItem["DEPTH_LEVEL"];
	endforeach;

	if ($previousLevel > 1)://close last item tags
		echo str_repeat("</ul></li>", ($previousLevel-1) );
	endif;
?>
	</ul>