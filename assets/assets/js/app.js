$('#moteurRecherche').select2({
    placeholder: "Rechercher une thématique ou une leçon...",
    width: '80%',
    ajax: {
        url: 'https://search.paysdufle.fr/search.php',
        dataType: 'json'
    }
});

$('#moteurRecherche').on('select2:selecting', function(e) {
    window.location = e.params.args.data.url;
});

$('.le_truc_a_modifier').each(function () {
    var truc = $(this).text().split('@');
    var machin = truc[1].split('.');
    $(this).text(machin[0] + ' @ ' + truc[0] + '.com (pensez à retirer les espaces avant et après le symbole @)');
});

$('.traduirePage').on('click', function() {
    if(confirm("Vous allez accéder à la traduction automatique de Google. Elle peut contenir des erreurs et certaines imprécisions. Voulez-vous continuer ?")) {
        var url_page = window.location.href;
        var url_gg_translate = 'https://translate.google.com/translate?hl=en&sl=fr&tl=en&u=';
        var url = url_gg_translate + url_page;
        var win = window.open(url, '_blank');
        win.focus();
    }
    return false;
});

$("a[href='#suivez-moi-surprise']").on('click', function() {
    $.getJSON('/assets/json/liste_lecons.json', function(liste_lecons) {
        var leconSelectionne = liste_lecons[Math.floor(Math.random()*liste_lecons.length)];
        window.location.href = leconSelectionne.url;
    });
});