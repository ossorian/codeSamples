<?php
namespace Products;

class Offers{

	public static function getProductID($offerID)
	{
        $result = \CCatalogSku::GetProductInfo($offerID);
        if ($result["ID"]) return $result['ID'];
        else return $offerID;
	}
	
	public static function getOffersList($productID)
	{
		return \CCatalogSKU::getOffersList($productID, IBLOCK_CAT, ["ACTIVE" => "Y"], [], []);
	}
}