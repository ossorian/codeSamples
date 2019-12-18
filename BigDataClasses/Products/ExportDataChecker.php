<?php
class ExportDataChecker
{

	const ERROR_WRONGDATA = 1;
	const ERROR_NOTHING_CHANGED = 2;
	
	const SEND_EMAILS = true;
	const EMAILS = "ossorian@ya.ru";

	public $filePath = '';
	private $check = false;
	private $sendResultAnyway = false;

	public function __construct(string $timeStart, $debug = false) {
		
		$this->go = false;
		if ($this->debug = $debug) $this->go = true;
		else {
			list($hour, $minute) = explode('-', $timeStart);
			echo date('H').' '.date('i').' ';
			$checkTime = mktime($hour, $minute);
			$currentTime = mktime();
			$diff = $checkTime - $currentTime;
			if ($diff <= 3600 && $diff >= 0) $this->go = true;
		}
		$this->currentCity = "spb";
	}

	public static function offerProperData($offer) {
		return [
			"ID" => $offer["ID"],
			"NAME" => $offer["NAME"],
			"ARTICLE" => $offer["PROPERTY_CML2_ARTICLE_VALUE"]
		];
	}
	
	public function check($data) {
		
		if (!$this->go || empty($data)) return;
		$this->data = &$data;
		
		foreach ($data as $city => $offers) {
			$currentOffersID = array_column($offers, "ID");
			$lastOffersID = self::getLastData();
			
			if ($lastOffersID && $currentOffersID && is_array($currentOffersID) && is_array($lastOffersID)) {
				$this->checkArrays($currentOffersID, $lastOffersID, self::SEND_EMAILS);
			}
			else $this->sendError(self::ERROR_WRONGDATA);
			if (!$this->check) $this->saveData($currentOffersID, $city);
		}
	}

	public static function getLastData() {
		$filename = self::getLastFilename();
		if ($filename && file_exists($filename)) {
			$data = file_get_contents($filename);
			return unserialize($data);
		}
		return false;
	}

	public function makeMissedOffers($missedIDs)
	{
		$dbResult = \CIBlockElement::GetList([], ["IBLOCK_ID" => 111, "ID" =>$missedIDs], false, false, ["ID", "NAME", "PROPERTY_CML2_ARTICLE"]);
		while ($row = $dbResult->GetNext()) {
			$this->data[$this->currentCity][$row["ID"]] = [
				"ID" => $row["ID"],
				"NAME" => $row["NAME"],
				"ARTICLE" => $row["PROPERTY_CML2_ARTICLE_VALUE"]
			];
		}
	}
	
	private function saveData($offersID, $city) {
		$filename = self::getFileName($city);
		return file_put_contents($filename, serialize($offersID));
	}
	
	private static function getFileName($city) {
		return $_SERVER["DOCUMENT_ROOT"] . "/upload/zverinus-yandex/idCollection_{$city}_" . date('Y-m-d') . ".ser";
	}
	
	private static function getLastFilename() {
		$result = glob($_SERVER["DOCUMENT_ROOT"] . "/upload/zverinus-yandex/*.ser");
		if (sizeof($result) == 1) $filename = $result[0];
		else {
			$latest = 0;
			foreach ($result as $file) {
				$filemtime = filemtime($file);
				if (!$latest || $latest < $filemtime) {
					$latest = $filemtime;
					$filename = $file;
				}
			}
		}
		return $filename;
	}
	
	private function checkArrays($currentOffersID, $lastOffersID, $sendEmails) {
		if ($this->check) array_pop($currentOffersID);
		$newIDs = array_diff($currentOffersID, $lastOffersID);
		$missedIDs = array_diff($lastOffersID, $currentOffersID);
		
		if ($this->check) var_dump($newIDs, $missedIDs, $sendEmails);
		
		if ((!$this->check || $this->sendResultAnyway) && $sendEmails && ($newIDs || $missedIDs)) $this->sendReport($newIDs, $missedIDs);
		elseif ($this->sendResultAnyway) $this->sendError(self::ERROR_NOTHING_CHANGED);
	}


	//TODO: Если понадобится по нескольким городам, то нужно будет слегка переделать
	private function sendReport(&$newIDs, &$missedIDs) {
		
		if ($missedIDs)	$this->makeMissedOffers($missedIDs);//Отсутвующих позиций нет в передающиъся данных извне.
		foreach ($this->data[$this->currentCity] as $datum) {
			$dataByID[$datum["ID"]] = $datum;
		}
		
		$text = "Отчет по данным выгрузки в Яндекс-маркет по городу Санкт-Петербургу" . PHP_EOL . PHP_EOL;
		if ($newIDs) {
			$text .= "Новые позиции (Артикул - ID сайта - Название):" . PHP_EOL . PHP_EOL;
			foreach ($newIDs as $id) {
				$text .= self::makeText($dataByID[$id]) . PHP_EOL;
			}
		}
		$text .= PHP_EOL . PHP_EOL;
		if ($missedIDs) {
			$text .= "Отсутствующие позиции (Артикул - ID сайта - Название):" . PHP_EOL . PHP_EOL;
			foreach ($missedIDs as $id) {
				$text .= self::makeText($dataByID[$id]) . PHP_EOL;
			}
		}
		
		$mailResult = mail(self::EMAILS, "Отчет о новых и отсутствующих позициях с сайта zverinus.ru", $text);
		if ($this->debug) { 
			if ($mailResult) echo "<br>Письмо о выполнении отправлено<br>";
			else echo "<br>Письмо о выполнении отправить не удалось<br>";
		}
	}
	
	private static function makeText($offer) {
		return ($offer["ARTICLE"] || $offer["ID"]) ? "{$offer["ARTICLE"]} - {$offer["ID"]} - {$offer["NAME"]}" : "";
	}
	
	private function sendError($errorNumber) {
		switch ($errorNumber) {
			case self::ERROR_WRONGDATA 			: $text = "Неверно полученные данные из последней выгрузки";break;
			case self::ERROR_NOTHING_CHANGED	: $text = "Данные не изменились";break;
		}
		
		$subject = "Ошибка при создании отчета о новых или отсутствующих позициях с сайта zverinus.ru";
		$mailResult = mail(self::EMAILS, $subject, $text);
		if ($this->debug || $this->sendResultAnyway) {
			if ($mailResult) echo "<br>Информация об ошибке &laquo;$text&raquo; отправлено<br>";
			else echo "<br>Информацию об ошибке &laquo;$text&raquo; отправить не удалось<br>";
		}
	}

}