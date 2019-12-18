<?php
namespace Products;

trait tStore
{
	public static function getTotalAmount($offerID, $cityName = false)
	{
		$arStoreAmounts = self::getStoreAmounts($offerID, $cityName);
		return self::countTotalAmount($arStoreAmounts);
	}

	public static function getStoreList($city = false)
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

	public static function getStoresDesc()
	{
		$dbRes = \CIBlockSection::GetList(
            array("SORT" => "ASC"),
            array(
                'IBLOCK_ID' => IBLOCK_CITY,
                'ACTIVE' => 'Y',
            ), false, ["ID", "NAME", "UF_CITYSTORE_PHOTO", "UF_CITYSTORE_DESC"]
        );
		
		while ($row = $dbRes->GetNext()) {
			$arResult[] = $row;
		}
		
		return $arResult;
	}
	
	public static function fetchStoresDesc2Cities(&$arData)
	{
		$arStoresDesc = self::getStoresDesc();
		foreach ($arStoresDesc as &$storeDesc) {
			if ($storeDesc["UF_CITYSTORE_PHOTO"]) {
				foreach ($storeDesc["UF_CITYSTORE_PHOTO"] as $photoID) {
					$storeDesc["PHOTOS"][] = \CFile::GetPath($photoID);
				}
			}			
			$arData[$storeDesc['NAME']] = $storeDesc;
		}
	}
	
	//TODO: yuri, Объединение метода из раннего кода. Можно улучшить, сделав только один проход по циклу $arStores.
	public static function fetchStores2Cities(&$arResult, array $arStores = [], $straightCityCheck = false)
	{
	    if ($straightCityCheck) {
            $straightCityName = \UserHandler::getLocationName();
        }

		if (empty($arStores)) $arStores = self::getStoreList();
		$storesAmount = 0;

        foreach ($arResult as &$itemCity) {
            $itemCity['STORES'] = array();
            foreach ($arStores as $arStore) {
				if (self::isCityStore($arStore, $itemCity["NAME"])) {
                    if (!$straightCityName || ($straightCityName == $itemCity["NAME"])) {
                        $itemCity['STORES'][] = $arStore;
                        $storesAmount++;
                    }
                }
            }
        }
        return !!$storesAmount;
	}
	
	//TODO: yuri, Объединение метода из раннего кода. Можно улучшить, сделав только один проход по циклу $arStores.
	public static function getStoresMapData($arCities, array $arStores = [])
	{
		if (empty($arStores)) $arStores = self::getStoreList();

		$index = 1;
		foreach ($arCities as $key => $itemCity) {
			$dataPoints = array();
			foreach ($arStores as $arStore) {
				if (self::isCityStore($arStore, $itemCity["NAME"])) {
					$dataPoints[] = array(
						"TEXT" => $arStore['ADDRESS'],
						"PHONE" => $arStore['PHONE'],
						"SCHEDULE" => $arStore['SCHEDULE'],
						"LON" => $arStore['GPS_S'],
						"LAT" => $arStore['GPS_N'],
						"INDEX" => $index++
					);
				}
			}
			$arResult[$key] = $dataPoints;
		}
		return $arResult;
	}
}