<?php
	require (dirname(dirname(__FILE__)).'/options.php');
	$aResult = $DB->getResults();
	$aFields = ["ID результата", "Уровень интеллекта", "Уровень сложности от", "Уровень сложности до", "Количество правильных ответов"];
?>
<table id="mainResults" border="1">
	<tr>
		<?foreach ($aFields as $field):?>
			<th><?=$field?></th>
		<?endforeach?>
	</tr>
	<tr>
		<td colspan="5" style="padding:20px">
			<input style="margin-right: 100px;" type="button" onClick="showCalculations()" value="Вернуться к расчетам">
			<input type="button" onClick="clearResults()" value="Очистить результаты">
			<input type="button" onClick="clearFreq()" value="Очистить частотность вопросов">
		</td>
	</tr>
	<?if ($aResult):?>
		<?foreach ($aResult as $result):?>
			<tr>
			<?foreach ($aFields as $key => $field):?>
				<td><?=$result[$key]?></td>
			<?endforeach?>
			</tr>
		<?endforeach?>
	<?else:?>
		<tr><td colspan="5" style="padding:30px; color: blue;">Результатов тестирования ещё нет</td></tr>
	<?endif?>
</table>