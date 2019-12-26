$(document).ready(function(){
	$('#actualizer_start').click(function(){
		$('#actualizer_result').show('slow', function(){
			startActualizer();
		});
		
	});
});

var startActualizer = function ()
{
	var total = parseInt($('#actualizer_result').find('span.total').html());
	var progress = $('#actualizer_result').find('.currentPosition');
	
	for (var position = 1; position <= total; position++)
	{
		console.log('Загружаю позицию ' + position);
		$.ajax({
			url : '', 
			data: {'position' : position},
			success : function (data){
				if (data.error) {
					alert("Ошибка: " + data.error);
					position = total + 2;
				}
				else {
					if (data.result) {
						console.log(data.result);
						progress.text(position);
					} else {
						alert("Неизвестная ошибка");
					}
				}
			},
			'dataType' : 'json',
			'async' : false
		});
	}
	if (position == (total + 1)) {
		$('#actualizer_result').hide('slow');
		$('#actualizer_done').show('slow');
	}
};