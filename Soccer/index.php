<?php
function match (int $c1, int $c2, $checkEmptyCommand = false) : array
{
	if ($c1 == $c2) return [];
	require_once(dirname(__FILE__).'/mightClass.php');
	$data = mightClass::loadData();
	if (empty($data) || !is_array($data)) return [];
	
	$com[1] = new mightClass($data[$c1]);
	$com[2] = new mightClass($data[$c2]);
	
	for ($i = 1; $i <=2; $i++) {
		$j = ($i == 1) ? 2 : 1;
		if ($checkEmptyCommand && empty($com[$j]->kSkiped)) return [];
		if (empty($com[$j]->kSkiped)) {
			$x = ceil($com[$i]->might / $com[$j]->might); //for any empty command the might level is set for very low.
		}
		else $x = ceil($com[$i]->kScored / $com[$j]->kSkiped * $com[$i]->might / $com[$j]->might);//main calculation
		
		//the main random function without specific correlation coefficients if needed in future.
		$result[$i - 1] = intval(
			round(
				sqrt(
					rand(0, $x * $x)
				)
			)
		);
	}
	
	return $result;
}

/**
	Turn it on to see all soccer match statistics with total data correlations
*/
//require('mightClass.php');mightClass::showStatTable();


//Just testing information
function getCoefs()
{
	$data = mightClass::loadData();
	foreach ($data as $key => $command) {
		$oCom = new mightClass($command);
		$aComs[] = $oCom;
	}
	mightClass::showAll($aComs);
}
