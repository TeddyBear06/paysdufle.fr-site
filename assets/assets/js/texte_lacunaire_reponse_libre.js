function corriger_texte_lacunaire_reponse_libre(target){
    var number_inputs = 0;
    var number_correct_answers = 0;
    $('#' + target + ' input').each(function () {
        if($(this).attr('data-answer') == $(this).val()) {
            $(this).css('background-color', '#02b875').css('color', 'white');
            number_correct_answers++;
        } else {
            $(this).css('background-color', '#d9534f').css('color', 'white');
        }
        number_inputs++;
    });
    if (number_correct_answers == number_inputs) {
        alert("Bravo ! C'est correct ! ");
    } else {
        alert("Certaines réponses ne sont pas correctes. Essaie encore une fois.");
    }
}

function reponses_texte_lacunaire_reponse_libre(target) {
    $('#' + target + ' input').each(function () {
        $(this).val($(this).attr('data-answer')).css('background-color', '#4582ec').css('color', 'white');
    });
}