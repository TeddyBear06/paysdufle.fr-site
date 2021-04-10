// See : https://www.htmllion.com/shuffle-list-items.html
$.fn.shufflelistitems = function() {
    $.each(this.get(), function(index, el) {
        var $el = $(el);
        var $find = $el.children();
 
        $find.sort(function() {
            return 0.5 - Math.random();
        });
 
        $el.empty();
        $find.appendTo($el);
    });
};

$("ul.propositions").each(function () {
    $(this).shufflelistitems();
});

$('ul.propositions').each(function () {
    var lis= $(this).find('li');
    lis.each(function (index, element) {
        if (index == 0) {
            $(this).addClass('active');
        } else {
            $(this).addClass('d-none');
        }
    });
});

$('.boutonCategorie').on('click', function () {
    var categorie = $(this).attr('data-categorie');
    var cible = $(this).attr('data-cible');
    var numero = cible.split('-')[1];
    var proposition = $('li.' + cible + '.active');
    if (proposition.attr('data-categorie') == categorie) {
        proposition.removeClass('active').addClass('d-none');
        var propositionSuivante = proposition.next();
        if (propositionSuivante.is('li')) {
            propositionSuivante.addClass('active').removeClass('d-none');
        } else {
            var contenuExercice = $('#contenuExercice' + numero);
            var ul = $(this).parent().children();
            ul.each(function () {
                $(this).addClass('d-none');
                var cibleCategorie = $(this).attr('data-categorie');
                var categorieLabel = $(this).text();
                var resultats = '<p><strong>' + categorieLabel + '</strong> : ';
                var detailsResultats = [];
                $('li.' + cible).each(function () {
                    if ($(this).attr('data-categorie') == cibleCategorie) {
                        detailsResultats.push($(this).text());
                    }
                });
                contenuExercice.append(resultats + detailsResultats.join(', ') + '</p>');
            });
        }
    } else {
        alert('Non, essayez encore...');
    }
});

