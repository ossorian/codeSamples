<?php
class QuestionGeneratorDB extends QuestionGenerator
{
	protected function getMainData()
	{
		$aQuestions = $this->DB->getQuestionsFreq();
		if (empty($aQuestions)) {
			$_SESSION["COMPLEX_FROM"] = 0;
			$_SESSION["COMPLEX_TO"] = 100;
			$this->DB->generateInitialData();
			$aQuestions = $this->DB->getQuestionsFreq();
		}
		return $aQuestions;
	}
	
	protected function saveStat($qIDs)
	{
		$this->DB->increaseFrequency($qIDs);
	}	
}