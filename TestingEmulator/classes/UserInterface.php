<?php
class UserInterface
{
	public static function showMain()
	{
?>
<!DOCTYPE html>
<html>
<head>
	<title>Эмулятор системы тестирования</title>
	<script type="text/javascript" src="//code.jquery.com/jquery-latest.js"></script>
	<script type="text/javascript" src="./js/mainScript.js"></script>
	<link rel="stylesheet" href="./css/style.css">
</head>
<body>
	<div id="main" width="100%">
		<div>
			<h1>Эмулятор системы тестирования</h1>
			<p>Удовиченко Юрий, 2019-08-20</p><p></p>
		</div>
		<div>
			<p>Текущие реальные значения сложности вопросов от <span id="from"><?=$_SESSION["COMPLEX_FROM"]?></span> до <span id="to"><?=$_SESSION["COMPLEX_TO"]?></span></p>
			<input type="text" min="0" max="100" value="<?=$_SESSION["COMPLEX_FROM"]?>" name="COMPLEX_FROM">
			<input type="text" min="0" max="100" value="<?=$_SESSION["COMPLEX_TO"]?>" name="COMPLEX_TO">
			<input type="button" onClick="generateComplexity()" value="Перегенерировать сложность">
		</div>
		<div>
			Уровень интеллекта тестируемого: 
			<input type="text" min="0" max="100" value="<?=$_SESSION["INTELLIGENCE"]?>" name="INTELLIGENCE">
		</div>
		<div>
			<input type="button" value="Запуск тестирования" onClick="startCalculation()" name="START">
			<input type="button" value="Посмотреть результаты" onClick="showResults()" name="SHOW">
		</div>
		<div id="result"></div>
	</div>
	<div id="resultTable"></div>
</body>
</html>
<?
	}
	
	public static function initVars()
	{
		global $DB;
		if (empty($_SESSION["INTELLIGENCE"])) $_SESSION["INTELLIGENCE"] = intval(rand(0, 100));
		list($_SESSION["COMPLEX_FROM"], $_SESSION["COMPLEX_TO"]) = $DB->getComplexValues();

		if (empty($_SESSION["COMPLEX_FROM"]) && empty($_SESSION["COMPLEX_TO"])) {
			$_SESSION["COMPLEX_FROM"] = 0;
			$_SESSION["COMPLEX_TO"] = 100;
			$DB->generateComplexity();
		}
	}
}