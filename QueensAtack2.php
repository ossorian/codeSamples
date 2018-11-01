<?php
/* Это решение задачи, описанной здесь: https://www.hackerrank.com/challenges/queens-attack-2/problem
Это пока одна из самых сложных, решённых мной на этом сайте. 
Используется процедурный стиль, однако я привёл его в более красивую форму
Для проверки можно встааить весь этот код вместо фнкции function queensAttack($n, $k, $r_q, $c_q, $obstacles) {}
И проверить его работу
 */
 
//
$arDim = array(
    array(1, 0), array(1, 1), array(0, 1), 
	array(-1, 1), array(-1, 0),
	array(-1, -1), array(0, -1), array(1, -1)
);
$arDist = array();
    
// Complete the queensAttack function below.
function queensAttack($n, $k, $r_q, $c_q, $obstacles) {
    
    //1. Board free distance calculation
    global $arDim;
    $qPoint = array($c_q, $r_q);
	
    foreach($arDim as $dim){
        foreach($dim as $key => $direction){
			
            if ($direction > 0) $distance = $n - $qPoint[$key];
            elseif ($direction < 0) $distance = $qPoint[$key] - 1;
            else $distance = 0;
            $arCurDist[$key] = $distance;
			
        }
        if (hasEmpty($dim)) $distance = max($arCurDist); //not null
        else $distance = min($arCurDist);
        $arDist[$dim[0]][$dim[1]] = $distance;
    }
    
    //2. Obstacle distance calculating
    
    foreach($obstacles as $obstacle){
        $obColumn = $obstacle[1];
        $obRow = $obstacle[0];
        $column = abs($c_q - $obColumn);
        $row = abs($r_q - $obRow);
        //var_dump($ob, $column, $row);
        //intersection
        if ($column == $row || $column == 0 || $row == 0){
            $obDimCol = -($c_q <=> $obColumn);
            $obDimRow = -($r_q <=> $obRow);
            //var_dump($obDimCol, $obDimRow);
            $arCurDist = array($column, $row);
            
            if ($column == $row) $dist = $column;
            else $dist = max($arCurDist);
            $dist -= 1;
            
            if ($arDist[$obDimCol][$obDimRow] > $dist) $arDist[$obDimCol][$obDimRow] = $dist;
        }
    }
    
    //3. Calculating all dimensions
    $amount = calculate($arDist);
    echo $amount.PHP_EOL;
    return $amount;
}

function hasEmpty($ar){
    if ($ar[0] == 0 || $ar[1] == 0) return true;
    return false;
}

function calculate($arDist){
    $amount = 0;
    foreach($arDist as $column)
        $amount += array_sum($column);
    return $amount;    
}