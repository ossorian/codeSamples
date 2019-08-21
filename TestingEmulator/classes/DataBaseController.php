<?php
class DataBaseController
{
	function __construct()
	{
		$this->connect = new mysqli('mysql.ossorian.myjino.ru', '045234115_mira', 'U{kEHiMrb}', 'ossorian_mirafox', '3306', '	/var/lib/mysql/mysql.sock');
		if ($this->connect->connect_error) {
			die('Ошибка подключения (' . $this->connect->connect_errno . ') '
					. $this->connect->connect_error);
		}
	}
	
	public function generateInitialData()
	{
		$this->connect->query('TRUNCATE TABLE `questions`');
		for ($i = 1; $i <= 100; $i++) {
			$query[] = "($i, 0)";
		}
		$query = "INSERT INTO `questions` (`ID`, `FREQ`) VALUES ".implode(', ', $query);
		$this->connect->query($query);
		$this->generateComplexity();
	}
	
	public function getQuestionsFreq()
	{
		$result = $this->connect->query("SELECT * FROM `questions`");
		while ($row = $result->fetch_row()) {
			$aResult[$row[0]] = intval($row[1]);
		}
		return $aResult;
	}
	
	public function increaseFrequency($qIDs)
	{
		$this->connect->query('UPDATE `questions` SET `FREQ` = `FREQ` + 1 WHERE `ID` IN ('. implode(', ', $qIDs) . ')');
	}
	
	public function generateComplexity()
	{
//		$this->connect->query('UPDATE `questions` SET `COMPLEXITY` = ' . intval(rand($_SESSION["COMPLEX_FROM"], $_SESSION["COMPLEX_TO"])) . '\'');
		$start = $_SESSION["COMPLEX_FROM"];
		$range = $_SESSION["COMPLEX_TO"] - $_SESSION["COMPLEX_FROM"];
		$this->connect->query("UPDATE `questions` SET `COMPLEXITY` = ROUND(RAND() * {$range}) + {$start}");
	}
	
	public function getComplexValues()
	{
		$result = $this->connect->query('SELECT MIN(COMPLEXITY), MAX(COMPLEXITY) FROM `questions`') -> fetch_row();
		return $result;
	}
	
	public function getQuestionsComplexity($qIDs)
	{
		$result = $this->connect->query('SELECT `ID`, `COMPLEXITY` FROM `questions` WHERE ID IN (' . implode(', ', $qIDs). ')');
		while ($row = $result->fetch_row()) {
			$aResult[$row[0]] = intval($row[1]);
		}
		return $aResult;
	}
	
	public function saveResult($result)
	{
		$this->connect->query("INSERT INTO `results` VALUES(0, '{$_SESSION['INTELLIGENCE']}', '{$_SESSION['COMPLEX_FROM']}', '{$_SESSION['COMPLEX_TO']}', '$result')");
	}
	
	public function getResults()
	{
		$result = $this->connect->query("SELECT * FROM `results`");
		while ($row = $result->fetch_row()) {
			$aResult[] = $row;
		}
		return $aResult;
	}
	
	public function clearResults()
	{
		$this->connect->query("TRUNCATE TABLE `results`");
	}
	
	public function clearFreq()
	{
		$this->connect->query("UPDATE `questions` SET `FREQ` = 0");
	}
}