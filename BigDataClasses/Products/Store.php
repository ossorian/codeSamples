<?php
namespace Products;

class Store implements iStore
{
use tStore;

    const MAIN_NALICHIE_PROPERTY_ID = 2423;

	protected $cityPropStores = [
		LOCATION_SPB => [128 => "NALICHIESPB"],
		LOCATION_KIRISHI => [126 => "NALICHIEKIRISHI"],
		LOCATION_KOSTROMA => [129 => "NALICHIEKOSTROMA"],
		LOCATION_KINESHMA => [113 => "NALICHIEKINESHMA113", 127 => "NALICHIEKINESHMA127"],
		LOCATION_KINGISEPP => [118 => "NALICHIEKINGISEPP118", 124 => "NALICHIEKINGISEPP124", 131 => "NALICHIEKINGISEPP131"],
		LOCATION_SLANCI => [111 =>"NALICHIESLANTSY"],
		LOCATION_VOLGORECH => [119 => "NALICHIEVOLGORECHENSK"],
		LOCATION_YAROSLAVL => [130 => "NALICHIEYAROSLAVL"]
	];
	
	//Тексты пока делаю по-быстрому. В идеале нужно брать реальные из свойства.
	protected $statusTexts =[
		AVAILABLE => "в наличии",
		AT_STORE => "на складе",
		NOT_AVAIL => "нет на складе",
		NEEDS_ORDER => "на заказ"
	];

	public static function getStatusTexts()
	{
		$instance = new self;
		return $instance->statusTexts;
	}

/* Interface iStore */

	public static function getStoreAmounts($offerID, $cityID = false)
	{
		if (!\CModule::IncludeModule("iblock") || !\CModule::IncludeModule("catalog") || empty($offerID)) return [];
		$storePropsCommas = $storeProps = self::collectStorePropNames($cityID);

		array_walk(
			$storePropsCommas, function(&$propName) {$propName = '"'.$propName.'"';}
		);
		$storeIDs = self::getStoreIDs($cityID);
		$arStoreAmounts = [];
		
		global $DB;
 		$dbResult = $DB->Query($query = 
			"SELECT PROPNAMES.CODE AS STORE, PROP.VALUE AS AMOUNT FROM `b_iblock_element_property` AS PROP 
				LEFT JOIN b_iblock_property AS PROPNAMES ON PROPNAMES.ID = PROP.IBLOCK_PROPERTY_ID
				WHERE PROP.IBLOCK_ELEMENT_ID = '{$offerID}' 
				AND PROP.IBLOCK_PROPERTY_ID IN (
					SELECT `ID` FROM b_iblock_property AS PROPNAMES 
						WHERE PROPNAMES.IBLOCK_ID = '".IBLOCK_TORG."' 
						AND PROPNAMES.CODE IN (".implode(', ', $storePropsCommas).")
				);"
		);

		while ($amountValue = $dbResult->Fetch()) {
			$storeID = $storeIDs[$amountValue["STORE"]];
			if ($storeID) $arStoreAmounts[$storeID] = $amountValue["AMOUNT"];
		}

 		foreach ($storeIDs as $storeID) {
			if (!isset($arStoreAmounts[$storeID])) $arStoreAmounts[$storeID] = NOT_AVAIL;
		}

		return $arStoreAmounts;
	}

	public static function countTotalAmount(array $arStoreAmounts, $offerID = false, &$nalichieValue = false)
	{
		$totalAmount = 0;
		foreach ($arStoreAmounts as $storeID => $amount) {
			if ($amount > 0) $totalAmount += $amount;
		}
		return self::getStoreStatus($totalAmount, $offerID, $nalichieValue);
	}

	//В offerID можно передать данные, полученые ранее в getOfferStatus, но нужно следить, чтобы MIN_ELEMENT_ID был меньше возможного количества.
	public static function getBuyStatus($offerData, $cityID = true)
	{
		if ($offerData < 2) $status = $offerData;
		else {
			$status = self::getOfferStatus($offerID = $offerData, $cityID);
		}
		if ($status == NOT_AVAIL) return false;
		return true;
	}
	
	public static function canBuyStatus($status)
	{
		if ($status > 1) $status = 1;
		return self::getBuyStatus($status);
	}
	
	//Замена реально получаемых данных складов на данные в соответствии со статусами по схеме 
	//Scheme: https://wireframepro.mockflow.com/view/M2b67a44fad17f630c49d006f536106cf1571150334742#/page/6f3527375a5f42f09dffc27d015f4a07
	public static function convertStoreAmounts(array &$arStoreAmounts, $offerID = false, &$nalichieValue = false)
	{
		if (!self::checkNalichieValue($offerID, $nalichieValue)) return false;
		foreach ($arStoreAmounts as $key => &$storeAmount) {
			if (is_numeric($key)) $storeAmount = self::getStoreStatus($storeAmount, $offerID, $nalichieValue);
		}
		return true;
	}

	public static function getBuyStatusByCities($offerID, $convert2props = true)
	{
		$offerAmounts = self::getStoreAmounts($offerID);
		$nalichieValue = self::getNalichieValue($offerID);

		$arLocations = self::getCityPropStores();

		foreach ($arLocations as $locationID => $stores) {
			$locationCode = \CityHandler::getCityCode($locationID, $uppercase = true);
			if ($convert2props) $locationCode = "CANBUY_".$locationCode;
			$cityOfferAmount = [];

			foreach ($stores as $storeCode => $storeProperty) {
				$cityOfferAmount[$storeCode] = $offerAmounts[$storeCode];
			}
			$cityStatus = self::countTotalAmount($cityOfferAmount, $offerID, $nalichieValue);
			$arResult[$locationCode] = (self::canBuyStatus($cityStatus) ? 1 : 0);
		}
		return $arResult;
	}

	public static function getCleanOfferStatus($offerID)
    {
        return self::getNalichieValue($offerID);
    }

/* --Interface iStore */

	public static function getOfferStatus($offerID, $cityID = true, &$statusText = '')
	{
		if ($cityID === true) $cityID = \UserHandler::getLocationID();
		$arAmounts = self::getStoreAmounts($offerID, $cityID);
		if ($arAmounts) {
		    $status = self::countTotalAmount($arAmounts, $offerID);
        }
		else {
		    $status = self::getNalichieValue($offerID);
        }

		$arStatusTexts = self::getStatusTexts();
		$statusText = $arStatusTexts[$status];

		return $status;
	}
	
	public static function collectStorePropNames($cityID = false)
	{
		$cStore = new self;
		if ($cityID) $arStores = $cStore->cityPropStores[$cityID];
		else {
            $arStores = [];
			array_walk_recursive($cStore->cityPropStores, function ($storeName, $storeKey) use (&$arStores) {
				$arStores[$storeKey] = $storeName;
			});
		}
		if (empty($arStores)) $arStores = self::getDefaultStores();
		return $arStores;
	}

	public static function getStoreIDs($cityID = false)
	{
		$arStoresWalk = self::getCityPropStores($cityID);
		array_walk_recursive($arStoresWalk, function ($storeName, $storeKey) use (&$arStores) {
			$arStores[$storeName] = $storeKey;
		});
		return $arStores;
	}

	public static function getCityPropStores($cityID = false)
	{
		$cStore = new self;
		if ($cityID) return $cStore->cityPropStores[$cityID];
		else return $cStore->cityPropStores;
	}

/* Protected */

	protected static function getDefaultStores()
	{
		$obj = new self;
		return $obj->cityPropStores[LOCATION_SPB];
	}

	protected static function getNalichieValue($offerID)
	{
		$dbResult = \CIBlockElement::GetList([], ["ID" => $offerID, "IBLOCK_ID" => IBLOCK_TORG], false, ["nTopCount" =>1], ["ID", "PROPERTY_NALICHIE"])->Fetch();
		$enumValues = [
			"1124360" => AVAILABLE,
			"1124361" => AT_STORE,
			"1124362" => NOT_AVAIL,
			"1143213" => NEEDS_ORDER
		];
		return $enumValues[$dbResult["PROPERTY_NALICHIE_ENUM_ID"]];
	}

	protected static function checkNalichieValue($offerID = false, &$nalichieValue = false)
	{
		if (is_numeric($nalichieValue)) return true;
		if (!empty($offerID)) {
			$nalichieValue = self::getNalichieValue($offerID);
			if (is_numeric($nalichieValue)) return true;
		}
		return false;
	}

	//Основной метод определения статуса товара
	protected static function getStoreStatus($totalAmount, $offerID = false, &$nalichieValue = false)
	{
		if ($totalAmount > 0) return $totalAmount;
		if (!self::checkNalichieValue($offerID, $nalichieValue)) return NOT_AVAIL;

		//Scheme: https://wireframepro.mockflow.com/view/M2b67a44fad17f630c49d006f536106cf1571150334742#/page/6f3527375a5f42f09dffc27d015f4a07
		if ($nalichieValue == NEEDS_ORDER) return NEEDS_ORDER;
 		elseif ($nalichieValue == AT_STORE) return AT_STORE;
		elseif ($nalichieValue == AVAILABLE) return AT_STORE;
		return NOT_AVAIL;
	}
	
	//yuri, Изначальная функция. Не стал менять, можно улучшить.
	protected static function isCityStore($store, $cityName)
	{
		return stristr($store['TITLE'], $cityName) || stristr($store['ADDRESS'], $cityName);
	}
}