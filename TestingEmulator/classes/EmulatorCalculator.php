<?php
class EmulatorCalculator
{
	const ID_FIELD = 'ID вопроса';
	const ADDITIONAL_FIELD = 'Количество<br>повторений вопроса';
	const SOLVED_FIELD = 'Вопрос решён';
	
	public static function makeCalculation($data)
	{
		$solved = 0;
		foreach($data as $qID => $complexity) {
			$oneResult = self::makeOneCalculation($complexity);
			$aResult[] = [
				self::ID_FIELD => $qID,
				self::ADDITIONAL_FIELD => '',
				'Сложность' => $complexity,
				self::SOLVED_FIELD => $oneResult
			];
		}
		return $aResult;
	}
	
	public static function makeOneCalculation($complexity)
	{
		$C = &$complexity;
		$I = $_SESSION["INTELLIGENCE"];

//1. Первым рассчитываем на сколько человек образован, чтобы отвечать уверенно, а не угадывать, в зависимости от сложности вопроса.
//		$knowK = ($I * ( 100 - $C )) / 10000;
		$knowK = $I / 100;
		
//2. Рассчитываем вероятность правильного ответа. Если человек знает, то он знает всегда.
//		$dif = $I - $C;
		$knowVariant = (($I - $C) > 0 ? 1 : 0);

//3. Рассчитываем вероятность угадывания. В условии не сказано, сколько вариантов ответа, поэтому делаю два. Тут появляется вероятность.
		$guesVariant = rand(0, 1);

//4. Рассчитываем соотношение между знанием и угадыванием
		$result = $knowVariant * $knowK + $guesVariant * ( 1 - $knowK );
//		echo "$dif : $result = $knowVariant * $knowK + $guesVariant * ( 1 - $knowK )<br>"; 
		$result = round($result);
		return boolval($result);
	}
	
	public static function getSolvedAmount($aResult)
	{
		return array_sum(array_column($aResult, self::SOLVED_FIELD));
	}
}