<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$arTemplateParameters = array(
	"DATA_PATH" => array(
		"NAME" => GetMessage("USOFT_DATA_PATH"),
		"TYPE" => "STRING",
		"DEFAULT" => "fakeData.php",
	),
	"ADMIN_GROUP" => array(
		"NAME" => GetMessage("USOFT_ADMIN_GROUP"),
		"TYPE" => "STRING",
		"DEFAULT" => "1",
	),
	"IBLOCK_ID" => array(
		"NAME" => GetMessage("USOFT_IBLOCK_ID"),
		"TYPE" => "STRING",
		"DEFAULT" => "16",
	),
	"PROPERTY_CODE" => array(
		"NAME" => GetMessage("USOFT_PROPERTY_CODE"),
		"TYPE" => "STRING",
		"DEFAULT" => "COLLECTION",
	)
);
?>