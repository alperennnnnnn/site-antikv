<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<div class="catalog-title catalog-red"><div class="catalog-title_text catalog-red"><?=GetMessage("CR_TITLE")?></div></div>
<div class="catalog-hr">&nbsp;</div>
<div class="catalog-item-cards">
<table class="catalog-item-card" cellspacing="0">
	<tr class="top">
<?	foreach($arResult["ITEMS"] as $key => $arElement):
		if(is_array($arElement)):
			$bPicture = is_array($arElement["PICTURE_PREVIEW"]);
?>
			<td>
				<div class="catalog-item-card<?=$bPicture ? '' : ' no-picture-mode'?>">
<?
			if ($bPicture):
?>
					<div class="item-image">
						<a href="<?=$arElement["DETAIL_PAGE_URL"]?>"><img border="0" src="<?=$arElement["PICTURE_PREVIEW"]["SRC"]?>" width="<?=$arElement["PICTURE_PREVIEW"]["WIDTH"]?>" height="<?=$arElement["PICTURE_PREVIEW"]["HEIGHT"]?>" alt="<?=$arElement["NAME"]?>" title="<?=$arElement["NAME"]?>" id="catalog_list_image_<?=$arElement['ID']?>" /></a>
					</div>
<?
			endif;
?>
					<div class="item-info">
						<p class="item-title">
							<a href="<?=$arElement["DETAIL_PAGE_URL"]?>"><?=$arElement["NAME"]?></a>
						</p>
						<p class="item-desc">
<?
			foreach($arElement["DISPLAY_PROPERTIES"] as $pid=>$arProperty):
?>
							<small><?=$arProperty["NAME"]?>:&nbsp;<?
				if(is_array($arProperty["DISPLAY_VALUE"]))
					echo implode("&nbsp;/&nbsp;", $arProperty["DISPLAY_VALUE"]);
				else
					echo $arProperty["DISPLAY_VALUE"];
?></small><br />
<?
			endforeach;
?>
							<?=strip_tags($arElement["PREVIEW_TEXT"]);?>
							<span class="item-desc-overlay"></span>
						</p>
						<?if(count($arElement["PRICE"])>0):?>
						<p class="item-price">
						<?if ($arElement['bDiscount']):?>
							<span><?=$arElement['PRICE']['DISCOUNT_PRICE_F']?></span> <s><?=$arElement['PRICE']['PRICE_F']?></s>
						<?else:?>
							<span><?=$arElement['PRICE']['PRICE_F']?></span>
						<?endif;?>
						</p>
						<?endif;?>

					</div>
				</div>
			</td>
<?
		endif;
?>
<?
	endforeach;
?>
	</tr>
</table>
</div>
