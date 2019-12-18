<?php
namespace Products;

class Store implements iStore
{
use tStore;

	//Early taken from /local/ajax/get_Store_amount.php
	public static function getStoreAmounts($offerID, $cityName = false)
	{
		if (!\CModule::IncludeModule("iblock") || !\CModule::IncludeModule("catalog") || empty($offerID)) return [];
		$arStores = self::getStoreList($cityName);

		foreach ($arStores as $store) {
			$storeList[$store["ID"]] = 0;
		}
		if (!empty($storeList)) $arStoreAmounts = $storeList;
		else $arStoreAmounts = array();

		$rsStore = \CCatalogStoreProduct::GetList(array(), array('PRODUCT_ID' => $offerID, false, false, array())); 

		while ($arStoreValue = $rsStore->Fetch()) {
			$storeID = $arStoreValue["STORE_ID"];
			if (!isset($storeList[$storeID])) continue;
			
			if (!empty($arStoreValue["AMOUNT"]) && isset($arStoreValue["AMOUNT"]))
				$arStoreAmounts[$storeID] = $arStoreValue["AMOUNT"];
			else
				$arStoreAmounts[$storeID] = 0;
		}
		return $arStoreAmounts;
	}
	
	public static function countTotalAmount(array $arStoreAmounts)
	{
		$totalAmount = 0;
		foreach ($arStoreAmounts as $storeID => $amount) {
			if ($amount > 0) $totalAmount += $amount;
			if ($amount == NOT_AVAIL) $notAvail = true;
			if ($amount == NEEDS_ORDER) $needsOrder = true;
		}
		if ($totalAmount) return $totalAmount;
		if ($totalAmount == 0 && (!$needsOrder && !$notAvail)) return AT_STORE;
		if ($needsOrder)  return NEEDS_ORDER;
		return NOT_AVAIL;
	}

	public static function getStoreList($cityName = false)
	{
		if (!\CModule::IncludeModule("iblock") || !\CModule::IncludeModule("catalog")) return [];
        $dbResult = \CCatalogStore::GetList(
            array('PRODUCT_ID' => 'ASC', 'ID' => 'ASC'), //PRODUCT_ID - так и было, не стал менять
            array('ACTIVE' => 'Y'),
            false,
            false,
            array("*")
        );

        while ($store = $dbResult->Fetch()){
			if (!$cityName || ($cityName && self::isCityStore($store, $cityName))) $arStores[] = $store;
        }
		
		return $arStores;
	}

	//yuri, Изначальная функция. Не стал менять, можно улучшить.
	protected static function isCityStore($store, $cityName)
	{
		return stristr($store['TITLE'], $cityName) || stristr($store['ADDRESS'], $cityName);
	}
}