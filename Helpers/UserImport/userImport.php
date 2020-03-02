<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule('helper');
CModule::IncludeModule('iblock');

set_time_limit(150);

$data = new UserImport($_SERVER["DOCUMENT_ROOT"].'/local/support/UserData/UsersIgsT2.csv');

$data->convertData();
//$data->saveUsers();
$data->findDirectors();
$data->showWrongDictionaries();
$data->saveUserDirectors();

class UserImport
{
	private $arDictionaries = ['company', 'professions'];
	private $addProfessions = false;

	function __construct($file)
	{
		$this->csvUserData = \Helper\Data::getFromCsv($file);
		foreach ($this->arDictionaries as $dict) {
			$this->$dict = \Helper\Element::GetList([], ["IBLOCK_CODE" => "$dict"]);
			foreach ($this->$dict as &$element) {
				$element["NAME"] = str_replace('&quot;', '"', $element["NAME"]);
			}
		}
		$this->getCurrentUsers();
	}
	
	private function getCurrentUsers()
	{
		$cUsers = \Dictionary\User::getInstance();
		$users = $cUsers->getAll();
		foreach ($users as $user) {
			$this->users[$user["ID"]] = $user;
		}
	}
	
	public function convertData()
	{
		$this->convertDictionary('company', 'UF_DZO');
		$this->convertDictionary('professions', 'UF_POSITION');
		if ($this->addProfessions && $this->wrongData['professions']) {
			$this->addNewProfessions();
		}
		$this->findUsers();
	}

	private function addNewProfessions()
	{
		$iblockID = \Helper\Iblock::getIdByCode('professions');
		$dict = array_unique($this->wrongData['professions']);
		sort($dict);
		$cElement = new \CIBlockElement;
 		foreach ($dict as $name) {
			if (!$cElement->Add([
					"NAME" => $name,
					"IBLOCK_ID" => $iblockID,
					"CODE" =>CUtil::translit($name, "ru")
			])) {echo $cElement->LAST_ERROR;die;}
		}
	}

	private function makeInitials()
	{
		foreach ($this->users as $user) {
			$this->initials[$user["LAST_NAME"].' '. substr($user["NAME"], 0, 1). '.' . substr($user["SECOND_NAME"], 0, 1)] = $user["ID"];
		}
//		var_dump(count($this->initials));
	}

	public function findDirectors()
	{
		//reloading current users
		$this->getCurrentUsers();
		$this->makeInitials();
		foreach ($this->csvUserData as &$csvUser) {
			$supervisor = trim($csvUser["UF_SUPERVISOR"], ' .,');
//			$supervisor = str_replace(',.', '.', $supervisor);
//			if ($supervisor && substr($supervisor,-1, 1) != '.') $supervisor .= '.';
			if ($supervisor) {
				if ($this->initials[$supervisor]) {
					$csvUser["UF_SUPERVISOR"] = $this->initials[$supervisor];
//					echo "<b>".$supervisor . ' найден</b><br>';
				} else {
//					echo $supervisor . 'не найден<br>';
					$csvUser["UF_SUPERVISOR"] = '';
					$this->wrongData['users'][] = $supervisor;
					$total++; 
				}
			}
		}
		echo $total;
	}


	protected function findUsers()
	{
		$found = 0;
		foreach ($this->csvUserData as &$csvUser) {
			foreach ($this->users as $user) {
				$isFound = false;
				if ($user["EMAIL"] == $csvUser["EMAIL"]) {
					$csvUser["ID"] = $user["ID"];
					$found++;
					$isFound = true;
					break;
				}
			}
			if (!$isFound) {
				foreach ($this->users as $user) {
					if ($user["LOGIN"] == $csvUser["LOGIN"]) {
						$csvUser["ID"] = $user["ID"];
						$found++;
						break;
					}
				}
			}
		}
		echo "Найдено $found пользователей из ".count($this->csvUserData)."<br>";
	}

	protected function convertDictionary($dict, $field)
	{
		foreach ($this->csvUserData as &$user) {
			if (empty($user[$field])) continue;
			$fieldValue = $user[$field];
			$found = false;
			foreach ($this->$dict as $dictElement) {
				if ($dictElement["NAME"] == $fieldValue) {
					$user[$field] = $dictElement["ID"];
					$found = true;
					break;
				}
			}
			if (!$found) {
				$user[$field] = '';
				$this->wrongData[$dict][] = $fieldValue;
//				$this->exit("Не найдено ни одного элемента для значения $fieldValue в поле $field");
			}
		}
	}
	
	public function saveUsers($fields = [])
	{
		$cUser = new \CUser;
		foreach ($this->csvUserData as $user) {
			if ($user["ID"]) {
				if ($cUser->Update($user["ID"] , $user)) $update++;
			}
			else {
				$user["PASSWORD"] = uniqid();
				if ($cUser->Add($user)) $add++;
				else echo $cUser->LAST_ERROR.'<br>';
			}
		}
		echo "Изменено $update пользователей, добавлено $add пользователей<br>";
	}

	public function saveUserDirectors()
	{
		$cUser = new \CUser;
		foreach ($this->csvUserData as $user) {
			if ($user["ID"] && intval($user["UF_SUPERVISOR"]) && empty($this->users[$user["ID"]]["UF_SUPERVISOR"])) {
				var_dump($this->users[$user["ID"]]);
				echo '<hr>';
				if ($cUser->Update($user["ID"] , ["UF_SUPERVISOR" => $user["UF_SUPERVISOR"]])) $update++;
			}
		}
		echo "Изменено ".intval($update)." данных о руководстве<br>";
	}
	
	protected function exit($str, $vars = [])
	{
		echo $str.'<br>';
		if ($vars) var_dump($vars);
//		die;
	}
	
	public function showWrongDictionaries()
	{
		foreach ($this->wrongData as $iblock => $dict) {
			$dict = array_unique($dict);
			sort($dict);
			echo "<b>Ошибочные данные для инфоблока $iblock</b><br>";
			foreach ($dict as $row) var_dump($row);
			echo '<hr>';
		}
	}

}