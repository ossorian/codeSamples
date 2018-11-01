<?php
$arDim = array(
    array(1, 0),
    array(1, 1),
    array(0, 1),
    array(-1, 1),
    array(-1, 0),
    array(-1, -1),
    array(0, -1),
    array(1, -1),
);
$arDist = array();
    
// Complete the queensAttack function below.
function queensAttack($n, $k, $r_q, $c_q, $obstacles) {
    
    //1. board Distance
    global $arDim;
    $qPoint = array($c_q, $r_q);
    foreach($arDim as $dim){
        foreach($dim as $key => $direction){
            if ($direction > 0) $distance = $n - $qPoint[$key];
            elseif ($direction < 0) $distance = $qPoint[$key] - 1;
            else $distance = 0;
            $arCurDist[$key] = $distance;
        }
        //var_dump($dim, $arCurDist);
        if (hasEmpty($dim)) $distance = max($arCurDist); //not null
        else $distance = min($arCurDist);
        $arDist[$dim[0]][$dim[1]] = $distance;
    }
    
    //var_dump($c_q, $r_q);
    //2. Obstacle distance
    
    foreach($obstacles as $ob){
        $oc = $ob[1];
        $or = $ob[0];
        $c = abs($c_q - $oc);
        $r = abs($r_q - $or);
        //var_dump($ob, $c, $r);
        //intersection
        if ($c == $r || $c == 0 || $r == 0){
            $obDimCol = -($c_q <=> $oc);
            $obDimRow = -($r_q <=> $or);
            //var_dump($obDimCol, $obDimRow);
            $arCurDist = array($c, $r);
            if ($c == $r) $dist = $c;
            else $dist = max($arCurDist);
            $arDist[$obDimCol][$obDimRow] = $dist - 1;
            //var_dump($arDist);
        }
        //var_dump($ob);
    }
    
    //3. calculating
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

$fptr = fopen(getenv("OUTPUT_PATH"), "w");

$stdin = fopen("php://stdin", "r");

fscanf($stdin, "%[^\n]", $nk_temp);
$nk = explode(' ', $nk_temp);

$n = intval($nk[0]);

$k = intval($nk[1]);

fscanf($stdin, "%[^\n]", $r_qC_q_temp);
$r_qC_q = explode(' ', $r_qC_q_temp);

$r_q = intval($r_qC_q[0]);

$c_q = intval($r_qC_q[1]);

$obstacles = array();

for ($i = 0; $i < $k; $i++) {
    fscanf($stdin, "%[^\n]", $obstacles_temp);
    $obstacles[] = array_map('intval', preg_split('/ /', $obstacles_temp, -1, PREG_SPLIT_NO_EMPTY));
}

$result = queensAttack($n, $k, $r_q, $c_q, $obstacles);

fwrite($fptr, $result . "\n");

fclose($stdin);
fclose($fptr);
