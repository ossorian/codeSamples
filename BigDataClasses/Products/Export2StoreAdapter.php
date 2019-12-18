<?php
/** Класс получает данные от класса ZverYandex для экспорта в яндекс-маркет и на их основе использует
общий функционал \Products\Store::getBuyStatus для определения наличия товара и принятия решения выгружать товар или нет*/

class Export2StoreAdapter {
	
	public function __construct()
	{
		$this->initStoreData();
		$this->initNalichieValues();
	}

	//После некоторого времени использования дебаг информацию можно будет убрать
	public function getBuyStatus($offer, $cityProps, $debug = false)
	{
		$arAmounts = $this->getStoreAmounts($offer, $cityProps);
		
		if ($debug) {
			if ((count($cityProps) != count($arAmounts)) || !count($cityProps)) {
				echo "<b>Массивы данных о складах и результов на складе не совпадают</b><br>";
				var_dump($cityProps);
				echo '<br>';
			}

			echo "{$offer["ID"]} - {$offer["NAME"]}<br>Наличие на складах: ";
			var_dump($arAmounts);
			echo '<br>';
		}
		
		$nalichieValue = $this->getNalichieValue($offer);
		$status = \Products\Store::countTotalAmount($arAmounts, $offer["ID"], $nalichieValue);
		$result = \Products\Store::canBuyStatus($status);
		
		if ($debug) {
			echo "Значение параметра наличия, окончательный расчитанный статус и одобрение для выгрузки: ";
			var_dump($nalichieValue, $status, $result);
			if (($nalichieValue == AVAILABLE) && $status == NOT_AVAIL) {
				echo "<br><b>Внимание! Статус наличия = 1, а остатки нулевые. Исправьте в 1с!</b>";
			}
			echo '<hr>';
		}
		
		return $result;
	}

	private function initStoreData()
	{
		$cityPropSores = \Products\Store::getCityPropStores();
		$arStores2Keys = [];
		array_walk_recursive($cityPropSores, function ($storeName, $storeKey) use (&$arStores2Keys) {
			$arStores2Keys[$storeName] = $storeKey;
		});
		$this->stores = $arStores2Keys;
	}

	private function initNalichieValues()
	{
		$statusTexts = \Products\Store::getStatusTexts();
		$this->nalichieValues = array_flip($statusTexts);
	}

	private function getNalichieValue($offer)
	{
		return $this->nalichieValues[$offer["PROPERTY_NALICHIE_VALUE"]];
	}

	private function getStoreAmounts($offer, $cityProps)
	{
		$arAmounts = [];
		foreach ($cityProps as $prop) {
			$storeKey = $this->stores[$prop];
			$value = $offer["STORES"]["PROPERTY_".$prop."_VALUE"];
			if ($storeKey) $arAmounts[$storeKey] = $value;
		}
		return $arAmounts;
	}
	
}