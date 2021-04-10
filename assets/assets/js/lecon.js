$('.corrigerExercice').on('click', function(e) {
    e.preventDefault();
    var target = $(this).attr('data-target');
    switch ($(this).attr('data-type')) {
        case 'texte_lacunaire_choix_multiple':
            corriger_texte_lacunaire_choix_multiple(target);
        break;
        case 'texte_lacunaire_reponse_libre':
            corriger_texte_lacunaire_reponse_libre(target);
        break;
    }
});

$('.afficherCorrectionExercice').on('click', function(e) {
    e.preventDefault();
    var target = $(this).attr('data-target');
    switch ($(this).attr('data-type')) {
        case 'texte_lacunaire_reponse_libre':
            reponses_texte_lacunaire_reponse_libre(target);
        break;
    }
});