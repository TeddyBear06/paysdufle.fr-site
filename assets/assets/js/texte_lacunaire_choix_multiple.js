function corriger_texte_lacunaire_choix_multiple(target) {
    var number_selects = 0;
    var number_correct_answers = 0;
    $('#' + target + ' select option:selected').each(function () {
        if($(this).attr('data-correct') == 'true') {
            $(this).parent().css('background-color', '#02b875').css('color', 'white');
            number_correct_answers++;
        } else {
            $(this).parent().css('background-color', '#d9534f').css('color', 'white');
        }
        number_selects++;
    }).promise().done(function(){ 
        if (number_correct_answers == number_selects) {
            alert("Bravo ! C'est correct.");
        } else {
            alert("Certaines réponses ne sont pas correctes. Essaie encore une fois.");
        }
    });
}