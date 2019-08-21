/* Небольшая ремарка: можно было сделать через, как вариант, запрос к единому файлу или какой-нибудь REST */

function generateComplexity()
{
	var min = $('input[name="COMPLEX_FROM"]').val();
	var max = $('input[name="COMPLEX_TO"]').val();
	$.get(
		'./ajax/generateComplexity.php',
		{'min' : min, 'max' : max},
		function(result){
			$('#from').text(result[0]);
			$('#to').text(result[1]);
			alert('Сгенерирована новая сложность вопросов');
		},
		'json'
	);
}

function startCalculation()
{
	var intell = $('input[name="INTELLIGENCE"]').val();
	$.get(
		'./ajax/startCalculation.php',
		{'intell' : intell},
		function(result){
			$('div#result').html(result);
		},
		'html'
	);
}

function showResults()
{
	$('div#main').slideUp('slow');
	$.get(
		'./ajax/showResults.php',
		function(result){
			$('div#resultTable').html(result).show('fast');
		},
		'html'
	);
}

function showCalculations()
{
	$('div#main').slideDown('slow');
	$('div#resultTable').hide('fast');
}

function clearResults()
{
		$.get(
		'./ajax/clearResults.php',
		function(result){
			showCalculations();
		},
		'html'
	);
}

function clearFreq()
{
		$.get(
		'./ajax/clearFreq.php',
		function(result){
			alert('Частотность вопросов обнулена');
		},
		'html'
	);
}