<?php
$collection = trim(htmlspecialchars(urldecode($_GET['collection'])));

//Имитация ответа сервера
sleep(1);

if (empty($collection)) die('error');

$arData = [
	'Алые Паруса' => 'Платья созданные по мотивам повести Александра Грина',
	'Амальтея' => 'Платья с фантастическим уклоном',
	'Третья планета' => 'Тоже классные платья'
];

if (!$arData[$collection]) die('error');
echo $arData[$collection];