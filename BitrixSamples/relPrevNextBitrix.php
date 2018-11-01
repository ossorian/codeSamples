<?//Add this script to any system.pagenavigation template to component_epilog.php file?>
<?
if ($currentPage = $arResult["NavPageNomer"]){
	$arDeleteParams = array('PAGEN_1'); //You cah add some more parameters here, check for the created references
	if ($currentPage > 1) $prevPage = $currentPage - 1;
	if ($currentPage < $arResult["NavPageCount"]) $nextPage = $currentPage + 1;
	
	if ($prevPage) {
		if ($prevPage > 1) $prevHref = $APPLICATION->GetCurPageParam ("PAGEN_1=$prevPage", $arDeleteParams);
		else $prevHref = $APPLICATION->GetCurPageParam ("", $arDeleteParams);
	}
	if ($nextPage) $nextHref = $APPLICATION->GetCurPageParam ("PAGEN_1=$nextPage", $arDeleteParams);
	
	if ($prevHref) $APPLICATION->AddHeadString('<link rel="prev" href="'.$prevHref.'" />');
	if ($nextHref) $APPLICATION->AddHeadString('<link rel="next" href="'.$nextHref.'" />');
}