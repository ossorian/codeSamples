<?php
class mightClass
{
	function __construct($cData)
	{
		if (empty($cData['name']) || empty($cData['games'])) {
			$this->might = 0.1;
			return;
		}
		$this->name = $cData['name'];
		$this->games = $cData['games'];
		$this->kWins = $cData['win'] / $this->games;
		$this->kDraw = $cData['draw'] / $this->games;
		$this->kDefeat = $cData['defeat'] / $this->games;
		$this->kScored = $cData['goals']['scored'] / $this->games;
		$this->kSkiped = $cData['goals']['skiped'] / $this->games;
		$this->K1 = ($this->kWins + $this->kDraw) / $this->kDefeat;
		$this->K2 = $this->kScored / $this->kSkiped;
		$this->might = ($this->K1 + $this->K2) / 2;
		
	}
	
	public static function loadData()
	{
		if (empty($GLOBALS["DATA"])) {
			require(dirname(__FILE__).'/data.php');
			$GLOBALS["DATA"] = $data;
		}
		else $data = $GLOBALS["DATA"];
		return $data;
	}
	
	public static function showAll($aComs)
	{
		$aFields = ['name', 'kWins', 'kDraw', 'kDefeat', 'kScored', 'kSkiped', 'K1', 'K2', 'might'];
		echo '<table>';
			foreach ($aFields as $field) {
				echo "<td>$field</td>";
				}
		foreach ($aComs as $com) {
			echo '<tr>';
				foreach ($aFields as $field) {
					echo '<td>'.$com->$field.'</td>';
				}
			echo '</tr>';	
		}
		echo '</table>';
	}
	
	public static function showStatTable()
	{
		$data = self::loadData();
		echo '<table>';
		echo '<tr><td>Название</td>';
		foreach ($data as $command) {
			echo '<td>'.$command['name'].'</td>';
		}
		echo '<td>Мощность / Кол-во выигрышей</td></tr>';

		foreach ($data as $rowComKey => $rowCom) {
			$wins = 0;
			echo '<tr><td>'.$rowCom['name'].'</td>';
			foreach ($data as $columComKey => $loumnCom) {
				if ($rowComKey == $columComKey) echo '<td>===</td>';
				else {
					$result = match($rowComKey, $columComKey);
					echo '<td>'.$result[0].'/'.$result[1].'</td>';
					if ($result[0] > $result[1]) $wins++;
				}
			}
			$oCommand = new mightClass($rowCom);
			echo "<td>{$oCommand->might}<br>$wins<br></td>";
			echo '</tr>';
			
			//correlation calculation;
			$aWins[$rowComKey] = $wins;
			$aMights[$rowComKey] = $oCommand->might;
		}
		echo '</table>';

		/** Получаемые разультаты отношения мощности комманды к кол-ву выигрышей 
			в диапазоне  0.84 < x < 0.94, что весьма неплохо.
		*/
		echo "Коэффициент корреляции:<br>".self::getCorrelation($aMights, $aWins);
	}

	private static function getCorrelation($x, $y)
	{
		$x=array_values($x);
		$y=array_values($y);    
		$xs=array_sum($x)/count($x);
		$ys=array_sum($y)/count($y);    
		$a=0;$bx=0;$by=0;
		for($i=0;$i<count($x);$i++){     
			$xr=$x[$i]-$xs;
			$yr=$y[$i]-$ys;     
			$a+=$xr*$yr;        
			$bx+=pow($xr,2);
			$by+=pow($yr,2);
		}   
		$b = sqrt($bx*$by);
		return $a/$b;	
	}
	
	public static function timeTesting()
	{
		$start = microtime(true);
		for ($i=0; $i <= 10000; $i++) {
			$x1 = rand(0, 31);
			$x2 = rand(0, 31);
		}
		echo microtime(true) - $start;
	}	
}