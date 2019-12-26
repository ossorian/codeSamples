<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true || !CModule::IncludeModule('iblock'))die();
//ini_set('display_errors', 1);
//error_reporting(E_ALL ^ E_NOTICE ^ E_STRICT);

require($_SERVER["DOCUMENT_ROOT"] . $componentPath . "/GoodsActualizer.php");

$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);
if (empty($arParams["IBLOCK_ID"])) {
	echo "Неверно задан код инфоблока!";return;
}

$adminGroupID = intval($arParams["ADMIN_GROUP"]) ?: 1;

// В идеале тексты выносятся в языковыек файлы, здесь не реализовано
$arGroups = $USER->GetUserGroupArray();
if (!in_array($adminGroupID, $arGroups)) {
	echo "Вы не можете использовать данный компонент без прав администратора!";return;
}

$cUpdater = new GoodsActualizer($arParams["IBLOCK_ID"], $arParams["PROPERTY_CODE"], $arParams["DATA_PATH"], $componentPath);

if ($updatePosition = intval($_REQUEST['position'])) {
	$APPLICATION->RestartBuffer();
	$cUpdater->updatePosition($updatePosition);
	die;
}
else {
	$arResult = [
		"TOTAL" => count($cUpdater->makeData()),
	];
	$this->IncludeComponentTemplate();
}
?>