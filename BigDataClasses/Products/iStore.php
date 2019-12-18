<?php
namespace Products;

interface iStore
{
	//Получает данные по всем складам с их кодами, в зависимости от города или в целом.
	public static function getStoreAmounts($offerID, $cityID = false);
	
	//Сумма товаров по скаладам (если > 0 или статус товара. Можно передать ID товара либо статус наличия - тогда расчет ведётся по ним. 
	public static function countTotalAmount(array $arStoreAmounts, $offerID = false, &$nalichieValue = false);

	//Передаётся ID оффера. Статус товара в $offerData - передавать не рекомендуется. Нужно использовать canBuyStatus
	public static function getBuyStatus($offerData, $cityID = true);
	
	//С уже полученгным статусом или количеством
	public static function canBuyStatus($status);

	//Получение общего свойства нраличия (в основном нужен для сторонних городов)
    public static function getCleanOfferStatus($offerID);

	//Методы для получения свойства наличия во время выгрузки
	public static function convertStoreAmounts(array &$arStoreAmounts);
	public static function getBuyStatusByCities($offerID, $convert2props = true);
}