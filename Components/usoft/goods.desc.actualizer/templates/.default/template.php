<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>

<div id="actualizer_block">
		<p>Внимание! Этот блок видит только администратор.</p>
	<?if (!$arResult["TOTAL"]):?>
		<p>Не найдено данных для обработки! Проверьте параметры компонента.</p>
	<?else:?>
		<p>При нажатии на кнопку &laquo;Обновить&raquo; запустится механизм обновления всех описаний товаров.</p>
		<input id="actualizer_start" type="button" value="Обновить">
		<div id="actualizer_result">
			<p>Обновлено <span class="currentPosition">0</span> из <span class="total"><?=$arResult["TOTAL"]?></span> записей описаний коллекций.</p>
			<p>Не перезагружайте страницу!</p>
		</div>
		<div id="actualizer_done">
			<p>Данные полностью обновлены.</p>
		</div>
	<?endif?>
</div>