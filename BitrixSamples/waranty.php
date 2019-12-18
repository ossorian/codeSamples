<?php
class Waranty {
	
	public static function createWarantyProduct($productID, $warantyCost)
	{
		$cElement = new CIBlockElement;
		$product = $cElement->GetByID($productID)->GetNext();

		$cProduct = new CCatalogProduct;
		$cPrice = new CPrice;
		
		$name = 'Гарантия на товар «' . $product["NAME"] . '»';
		$arFields = [
			"IBLOCK_ID" => 22,
			"NAME" => $name,
			"CODE" => CUtil::translit($name, "ru"),
		];
		if ($elementID = $cElement->Add($arFields, false, false, false)) {
			$arProductFields = [
				"ID" => $elementID,
				"AVAILABLE" => "Y",
				"QUANTITY_TRACE" => "N",
				"CAN_BUY_ZERO" => "Y",
				"QUANTITY" => 1000000,
				"SUBSCRIBE" => "N",
			];
			
			if ($productAccepted = $cProduct->Add($arProductFields)) {
 				$priceResult = $cPrice->Add([
					"PRODUCT_ID" => $elementID,
					"PRICE" => intval($warantyCost),
					"CURRENCY" => "RUB",
					"CATALOG_GROUP_ID" => 1,
					 "QUANTITY_FROM"=>false,
					 "QUANTITY_TO"=>false
				]);
			}
			if ($elementID && $productAccepted && $priceResult) {
				$cElement->SetPropertyValuesEx($productID, 5, ["GUARANTY" => $elementID]);
				return $elementID;
			}
		}
		return false;
	}
	
	public static function 	attachPrices(&$data)
	{
		$warantyElements = array_unique(array_column($data, "BASKET_WARANTY_ELEMENT_ID"));
		if ($warantyElements) {
			$dbResult = CPrice::GetList([], ["PRODUCT_ID" => $warantyElements, "CATALOG_GROUP_ID" => 1]);
			while ($row = $dbResult->GetNext()) {
				$arPrices[$row["PRODUCT_ID"]] = $row["PRICE"];
			}
		}
		
		foreach ($data as &$datum) {
			$datum["PRICE"] = $arPrices[$datum["BASKET_WARANTY_ELEMENT_ID"]];
		}
	}
	
	public static function getPrices($data)
	{
		$dbResult = CPrice::GetList([], ["PRODUCT_ID" => $data, "CATALOG_GROUP_ID" => 1]);
		while ($row = $dbResult->GetNext()) {
			$arPrices[$row["PRODUCT_ID"]] = $row["PRICE"];
		}
		foreach ($data as $sectionID => $datum) {
			$result[$sectionID] = $arPrices[$datum];
		}
		return $result;
	}
	
	public static function getName($productID) 
	{
		$result = CIBlockElement::GetByID($productID)->GetNext();
		return $result["NAME"];
	}

	public static function getPrice($productID) 
	{
		CModule::IncludeModule("sale");
		CModule::IncludeModule("catalog");
		$dbResult = CPrice::GetList([], ["PRODUCT_ID" => $productID, "CATALOG_GROUP_ID" => 1]) -> Fetch();
		return intval($dbResult["PRICE"]);
	}

}