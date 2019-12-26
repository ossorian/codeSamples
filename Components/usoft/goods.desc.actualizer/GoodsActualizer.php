<?php
class GoodsActualizer {
	
	const TMP_DATA_FILE = "tmp.ser";
	
	public function __construct($iblockID, $propertyCode, $dataPath, $componentPath)
	{
		$this->componentPath = $componentPath;
		$this->iblockID = $iblockID;
		$this->propertyCode = $propertyCode;
		$this->dataPath = $dataPath;
		
		$property = CIBlockProperty::GetList([], ["IBLOCK_ID" => $iblockID, "CODE" => $propertyCode])->Fetch();
		$this->propertyID = $property["ID"];
	}
	
	public function makeData()
	{
		if (!$this->propertyID) return [];

		//Получение всех строковых данных свойства в единичном экземпляре
		global $DB;
		$dbResult = $DB->Query($query = 
		"SELECT DISTINCT A.VALUE AS DATA 
			FROM `b_iblock_element_property` AS A LEFT JOIN `b_iblock_element` AS B ON A.IBLOCK_ELEMENT_ID = B.ID
			WHERE B.IBLOCK_ID = '{$this->iblockID}' AND A.IBLOCK_PROPERTY_ID = {$this->propertyID} 
			ORDER BY DATA ASC"
		);
		
		while ($result = $dbResult->Fetch()) {
			$arData[] = $result["DATA"];
		}
		
		if ($arData) {
			$this->saveTmpData($arData);
			return $arData;
		}
		
		return [];
 	}
	
	public function updatePosition($updatePosition)
	{
		if (!$data = $this->getData()) self::throwError("No data to allocate");
		if (!$collectionName = $data[$updatePosition - 1])	self::throwError("No data position");
		if (!$description = self::getDescription($collectionName)) self::throwError("Wrong file data");
		if ($description == 'error') self::throwError("Wrong collection name");

		if (self::updateGoodsData($collectionName, $description))
			$this->sendResult($updatePosition, $collectionName, $description);
		else self::throwError("Wrong update at $updatePosition position");
	}
	
	private function sendResult($updatePosition, $collectionName, $description)
	{
		$result = [
			'result' => "Данные по позиции $updatePosition коллекции $collectionName заменены на '" . htmlspecialcharsbx($description) . "'"
		];
		die(json_encode($result));
	}
	
	private function getDescription($value)
	{
		 //Для реальных удаленных запросов, задаётся в параметрах компонента
		if (strpos($this->dataPath, 'http')) $mainPath = $this->dataPath;
		 //Для фейкового запроса
		else $mainPath = $_SERVER["HTTPS"] ? "https" : "http" . "://" . 
						 $_SERVER["HTTP_HOST"] . $this->componentPath . '/' . $this->dataPath;
		
		$fullPath = $mainPath . '?collection=' . urlencode($value);
		return $content = self::getContent($fullPath);
	}
	
	private static function updateGoodsData($collectionValue, $description)
	{
		/*Здесь логику замены информации эелементов прописывать не буду, т.к. нужны тестовые данные,
			и это стандартная операция. В двух чертах:
		
		1. Находим все товары со свойством PROPERTY_{$this->propertyCode} == {$collectionValue}
		и делаем в цикле CIBlockElement::Update() - меняем PREVIEW_TEXT или DETAIL_TEXT на $description 
		Т.к. товаров 20 тыс, а коллекций около 100, то можно предположить, что это не займёт много времени.
		
		Но желательно заменять описание напрямую в БД для всех товаров сразу, предварительно протестировав, что 
		это не затронет других сущностей. Родной метод битрикс, к сожалению, не предусматривает замену свойства сразу
		у нескольких элементов.
		*/
		
		// Считаем, что по-умолчанию все апдейты прошли успешно, если нет - то выдаст соответствующую ощибку.
		return $allUpdatesDone = true;
	}
	
	private static function throwError($subject)
	{
		echo json_encode(["error" => $subject]);
		die;
	}
	
	private function getData()
	{
		$data = file_get_contents($_SERVER["DOCUMENT_ROOT"] . $this->componentPath . '/'. self::TMP_DATA_FILE);
		return unserialize($data);
	}
	
	private function saveTmpData(&$arData)
	{
		return file_put_contents($_SERVER["DOCUMENT_ROOT"] . $this->componentPath . '/'. self::TMP_DATA_FILE, serialize($arData));
	}
	
	private static function getContent($fullPath)
	{
  		$ch = curl_init($fullPath);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$html = curl_exec($ch);
		curl_close($ch);
		return $html;
	}
	
}
?>