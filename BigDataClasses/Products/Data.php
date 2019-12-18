<?php
namespace Products;

class Data {
	
	public function __construct($orderID = false)
	{
		global $DB;
		$dbResult = $DB->Query("SELECT `ID`, `IBLOCK_SECTION_ID`, `NAME` FROM `b_iblock_section` WHERE `ACTIVE`='Y' AND `IBLOCK_ID` = ".IBLOCK_CAT);
		while ($section = $dbResult->Fetch()) {
			$arSections[$section["ID"]] = $section;
		}
		$this->arSections = $arSections;
		$this->orderID = $orderID;
	}

	public function getECommerceData($arOffers, $type)
	{
		$arYMoffers = $this->getYMoffers($arOffers);
		$objECommerce = [
			"ecommerce" => [
				"currencyCode" => "RUB",
				$type => [
					"products" => $arYMoffers
				]		
			]
		];

		if ($type == 'purchase' && $this->orderID) {
			$objECommerce["ecommerce"]["purchase"]["actionField"] = ["id" => $this->orderID];
		}
		return $objECommerce;
	}

	public function getYMoffers($arOffers)
	{
		$arResult = [];
		foreach ($arOffers as $offer) {
			$arResult[] = $this->getYMdata($offer["ID"], $offer["QUANTITY"]);
		}
		return $arResult;
	}
	
	public function getYMdata($offerID, $quantity)
	{
		$obCache  = new \CPHPCache();
		global $CACHE_MANAGER;
		$cacheTime  = 10e5;
		$cachePath = '/ZVERINUS/';
		$cacheID  = "Metrika_$offerID";

		if($obCache->InitCache($cacheTime, $cacheID, $cachePath)){
			$objECommerce = $obCache->GetVars(); 

		}elseif( $obCache->StartDataCache() ){
			\CModule::IncludeModule('iblock');
			\CModule::IncludeModule('catalog');
			\CModule::IncludeModule('sale');

			$offerResult = \CCatalogSku::GetProductInfo($offerID);
			if (is_array($offerResult)) {
				$iblockID = IBLOCK_TORG;
				$productID = $offerResult["ID"];
			}
			else {
				$iblockID = IBLOCK_CAT;
				$productID = $offerID;
			}

			//Элемент
			$element = \CIBlockElement::GetList([], ["IBLOCK_ID" => $iblockID, "ID" => $offerID], false, ["nTopCount" => 1], 
				["ID", "NAME", "PROPERTY_BREND", "PROPERTY_VOZRAST"]
			)->GetNext();

			//Цена
			$cPrice = new \CPrice;
			$aPrice = $cPrice->GetBasePrice($offerID);
			$price = number_format($aPrice["PRICE"], 2, '.', '');

			//Категории
			$mainSection = \CIBlockElement::GetElementGroups($productID, false, ["ID"])->fetch();
			$offerSections = $this->getOfferSectionsString($mainSection["ID"]);

			if (empty($element["ID"]) || empty(intval($price))) $objECommerce = null;
			else {
				$CACHE_MANAGER->StartTagCache($cachePath);
				$CACHE_MANAGER->RegisterTag("iblock_id_" . IBLOCK_CAT);
				$CACHE_MANAGER->RegisterTag("iblock_id_" . IBLOCK_TORG);
				$CACHE_MANAGER->EndTagCache();
				
				$objECommerce = [
					"id" => $element["ID"],
					"name" => $element["NAME"],
					"brand" => $element["PROPERTY_BREND_VALUE"] ?: "",
					"category" => $offerSections,
					"price" => $price,	
					"quantity" => $quantity,
					"variant" => $element["PROPERTY_VOZRAST_VALUE"] ?: ""
				];
			}
			$obCache->EndDataCache($objECommerce);
		}

		return $objECommerce;
	}

	private function getOfferSectionsString($sectionID) {
		$this->sectionResult = [];
		$this->getOfferSections($sectionID);
		$offerSections = $this->sectionResult;

		if ($offerSections) {
			$offerSections = array_reverse($offerSections);
			$sectionNames = array_column($offerSections, "NAME");
			return implode('/', $sectionNames);
		}
		return "";
	}

	private function getOfferSections($sectionID) {
		if ($this->arSections[$sectionID]) $this->sectionResult[] = $this->arSections[$sectionID];
		$parentID = $this->arSections[$sectionID]["IBLOCK_SECTION_ID"];
		if (!empty($parentID)) $this->getOfferSections($parentID);
	}
}