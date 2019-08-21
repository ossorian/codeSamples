<?php
	require (dirname(dirname(__FILE__)).'/options.php');
	$_SESSION["INTELLIGENCE"] = intval($_GET['intell']);

	$freqData = $DB->getQuestionsFreq();
	$oQuestions = new QuestionGeneratorDB;
	$qIDs = $oQuestions->getQuestions();
	$mainQuestData = $DB->getQuestionsComplexity($qIDs);

	$aResult = EmulatorCalculator::makeCalculation($mainQuestData);
	$solved = EmulatorCalculator::getSolvedAmount($aResult);
	$DB->saveResult($solved);


?>
<p>Количество правильных ответов: <?=$solved?> из 40 с учётом угадывания, в зависимости от уровня знаний пользователя.</p>
<table border="1" width="100%">
	<tr>
		<th>Порядковый<br>номер</th>
		<?foreach($aResult[0] as $fieldName => $field):?>
			<th><?=$fieldName?></th>
		<?endforeach?>
	</tr>
<?foreach ($aResult as $row):?>
	<tr>
		<td><?=++$i?></td>
		<?foreach($row as $field => $value):?>
			<td>
			<?
				if ($field == EmulatorCalculator::SOLVED_FIELD) {
					echo $value == 1 ? "Да" : "-";
				} 
				elseif ($field == EmulatorCalculator::ADDITIONAL_FIELD) {
					echo $freqData[$row[EmulatorCalculator::ID_FIELD]];
				}
				else {
					echo intval($value);
				}
			?>
			</td>
		<?endforeach?>
	</tr>
<?endforeach?>
</table>
