<?php
class QuestionGenerator
{
	
	public function __construct()
	{
		global $DB;
		if (!empty($DB) && is_object($DB)) $this->DB = $DB;

		$this->data = static::getMainData();
		$this->maxValue = $this->getMaxValue();
		
	}

	public function getQuestions($amount = 40, $saveStat = true)
	{
		$qNumber = sizeof($this->data);
		$amount = abs(round($amount));
		if ($amount > $qNumber) $amount = $qNumber;
		if ($amount == $qNumber) return range(1, $qNumber);

		$this->aRanges = $this->makeRandomRanges();
		while ($amount-- > 0) {
			$aResult[] = $this->getQuestion($removeQuestion = true);
		}
		
		if ($saveStat) $this->saveStat($aResult);
		return $aResult;
	}

	protected function getQuestion($removeQuestion = true)
	{
		$maxRandomValue = array_sum($this->aRanges);
		$tmpValue = rand(1, $maxRandomValue);
		$questionID = $this->fetchQuestion($tmpValue);
		if ($removeQuestion) unset($this->aRanges[$questionID]);
		return $questionID;
	}
	
	protected function fetchQuestion($fetchValue)
	{
		$n = 0;
		foreach ($this->aRanges as $qID => $qFreq) {
			if (empty($qFreq)) continue;
			$n += $qFreq;
			if ($fetchValue <= $n) return $qID;
		}
		return false;
	}

	protected function saveStat($qIDs)
	{
		foreach ($qIDs as $qID) {
			$this->data[$qID] += 1;
		}
	}

	protected function makeRandomRanges()
	{
		$maxFreq = $this->getMaxValue();
		$minFreq = $this->getMinValue();
		foreach ($this->data as $qNumber => $qFreq) {
			$aResult[$qNumber] = $current = intval(round( $maxFreq - $minFreq + 1 ) / ( $qFreq - $minFreq + 1 ));
		}
		return $aResult;
	}

	protected function getMaxValue()
	{
		return max($this->data);
	}

	protected function getMinValue()
	{
		return min($this->data);
	}
	
	protected function getMainData()
	{
		return self::getTmpData();
	}
	
	protected static function getTmpData()
	{
		for ($i = 1; $i <= 100; $i++) {
			$aData[$i] = 0;
		}
		return $aData;
	}
}