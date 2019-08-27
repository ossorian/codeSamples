<?php
/**
Поиск максимально длинного палиндрома в строке. При этом палиндром может быть как с чётным количеством символов, так и нет.
Удовиченко Юрий, 2019-08-27
*/

class Palyndrom
{
	public function __construct($str)
	{
		$this->iniStr = $str;
		$str = mb_strtolower($str);
		$this->mainStr = preg_replace('/[^a-zа-яё]+/ui', '', $str);
		$this->mainLen = mb_strlen($this->mainStr);
		
		if (empty($this->mainStr) || $this->mainLen < 2) {
			$this->error = true;
			$this->__destruct();
		}
	}
	
	public function fetch()
	{
		for ($i = 0.5; $i <= $this->mainLen - 1.5; $i += 0.5) {
			$evenPosition = ($i != floor($i));
			
			$this->getRanges($i, $evenPosition);
			$step = 0; $result = '';
			$leftPos = $rightPos = $i;
			
			while ($step++ < $this->maxLength) {
				
				if ($evenPosition && $step == 1) {
					$leftPos -= 0.5;
					$rightPos += 0.5;
				}
				else {
					$leftPos--;
					$rightPos++;
				}
				
				$leftChar = $this->getChar($leftPos);
				$rightChar = $this->getChar($rightPos);
				if ($leftChar == $rightChar) $result .= $rightChar;
				else break;
			}
	
			if (mb_strlen($result)) $this->result[] = $this->getResultStr($result, $i, $evenPosition);
		}
	}
	
	public function showLargest()
	{
		if (!is_array($this->result)) {
			self::showNoResult();
			return;
		}
		$length = 0;
		foreach ($this->result as $key => $result) {
			if (($newLength = mb_strlen($result)) > $length) {
				$length = $newLength;
				$resultKey = $key;
			}
		}
		echo "Самый длинный палиндром:<br>";
		echo $this->result[$resultKey];
	}

	public function showAll()
	{
		if (!is_array($this->result)) {
			self::showNoResult();
			return;
		}
		echo "Все найденные палиндромы в строке:<br>";
		foreach ($this->result as $result) {
			echo "$result<br>";
		}
	}

	protected function getRanges($i, $evenPosition)
	{
		$leftDiff = $i - ($evenPosition ? -0.5 : 0);
		$rightDiff = $this->mainLen - $i - ($evenPosition ? 0.5 : 1);
		$this->maxLength = min($leftDiff, $rightDiff);
	}
	
	private function getResultStr($result, $i, $evenPosition)
	{
		return self::mb_strrev($result).($evenPosition ? '' : $this->getChar($i)).$result;
	}
	
	private function getChar($pos)
	{
		return mb_substr($this->mainStr, $pos, 1);
	}

	public function __destruct()
	{
		if ($this->error) echo "Задана слишком короткая строка или в ней отсутствуют буквы.";
	}
	
	private static function mb_strrev($str){
		$r = '';
		for ($i = mb_strlen($str); $i>=0; $i--) {
			$r .= mb_substr($str, $i, 1);
		}
		return $r;
	}
	
	private static function showNoResult()
	{
		echo "Палиндромов в строке не обнаружено";
	}
}

$oPalyndrom = new Palyndrom("Ежу хуже
Лев осовел
Неуч учуен
Утоп в поту
Шику кукиш
Ты сама сыта
Болвана в лоб
Да, гневен гад
Маска как сам
Чем нежен меч
Мат и тут и там
Там холм лохмат
Он рёва наверно
Вид усов осудив
Лев с ума ламу свёл
Кот, сука, за кусток
Уверена я, а не реву
Цени в себе свинец
Отлично кончил-то
Кошмар, срам, шок
Милашка, как шалим
Нахапал фуфла пахан
А вот и харя рахитова
Акт у нимф - минутка
Кот учён, но не чуток
Аргентина манит негра
А роза упала на лапу Азора");

$oPalyndrom->fetch();
$oPalyndrom->showAll();
$oPalyndrom->showLargest();