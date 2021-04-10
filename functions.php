<?php

# On charge les librairies
require __DIR__ . '/vendor/autoload.php';

use Spatie\YamlFrontMatter\YamlFrontMatter;

/**
 * Permet d'ordonner les catégories dans l'ordre croissant.
 */
function tri_categories($a, $b) : array {
    $numero_a = explode('.', $a)[0];
    $numero_b = explode('.', $b)[0];
    var_dump($numero_a, $numero_b);
}

/**
 * Permet de gérer un exercice de type Quizlet.
 */
function quizlet($numero, $contenu) : array {
    $contenuParse = YamlFrontMatter::parse($contenu);

    if ($contenuParse->iframe) {

        $message = "<p>Si vous avez des problèmes avec l'affichage de cette activité, vous pouvez la faire directement sur le site Quizlet en cliquant sur le lien suivant : <a href='{$contenuParse->iframe}' target='_blank'>{$contenuParse->iframe}</a></p>";

        $contenu = $message . "<div class='embed-responsive embed-responsive-16by9'><iframe class='embed-responsive-item' src='$contenuParse->iframe' frameborder='0' allowfullscreen='true' mozallowfullscreen='true' webkitallowfullscreen='true'></iframe></div>";
    }

    return [
        'type' => 'quizlet',
        'numero' => $numero,
        'contenu' => nl2br($contenu),
        'afficher_bouton_reponses' => false,
        'afficher_bouton_corriger' => false,
        'consigne' => 'Apprenez le vocabulaire avec Quizlet.'
    ];
}

/**
 * Permet d'afficher un mot ainsi qu'un ensemble de propositions. L'utilisateur
 * doit cliquer sur la proposition correspondant au mot.
 * 
 * Ex. : Les mot est "nez", les propositions sont "le", "la" et "les".
 * 
 * L'utilisateur doit cliquer sur "le" pour obtenir une réponse correcte.
 * 
 * Voici le contenu que le rédacteur doit créer :
 * 
 * Le:nez,pied;La:tête,main;L':oeil,épaule
 * 
 * Chaque bloc est découpé selon les points-virgules.
 */
function association_categorie($numero, $contenu) : array {
    $categories = explode(';', $contenu);
    $liPropositions = null;
    $liCategories = null;
    foreach ($categories as $contenuCategorie) {
        $categorie = explode(':', $contenuCategorie)[0];
        $sluggedCategorie = \Illuminate\Support\Str::slug($categorie);
        $propositions = explode(',', 
            explode(':', $contenuCategorie)[1]
        );
        foreach ($propositions as $index => $proposition) {
            $liPropositions[] = [
                'sluggedCategorie' => $sluggedCategorie,
                'proposition' => $proposition
            ];
        }
        $liCategories[] = [
            'sluggedCategorie' => $sluggedCategorie,
            'categorie' => $categorie
        ];
    }
    $ulPropositions = '<ul class="list-inline propositions text-center">' 
        . implode('', array_map(function($proposition) use($numero) {
        return '<li class="list-inline-item propositions-' . $numero . '" data-cible="propositions-' . $numero . '" data-categorie="' . $proposition['sluggedCategorie'] . '">' . $proposition['proposition'] . '</li>';
    }, $liPropositions))
        . '</ul>';
    $ulCategories = '<ul class="list-inline categories text-center">' 
        . implode('', array_map(function($categorie) use($numero) {
        return '<li class="list-inline-item btn btn-primary boutonCategorie" data-cible="propositions-' . $numero . '" data-categorie="' . $categorie['sluggedCategorie'] . '">' . $categorie['categorie'] . '</li>';
    }, $liCategories))
        . '</ul>';
    $contenu = $ulPropositions
        . $ulCategories;
    return [
        'type' => 'association_categorie',
        'numero' => $numero,
        'contenu' => nl2br($contenu),
        'afficher_bouton_reponses' => false,
        'afficher_bouton_corriger' => false,
        'consigne' => 'Lisez le mot et cliquez sur la bonne catégorie.'
    ];
}

/**
 * Permet de transformer un texte vers un texte lacunaire à choix multiple.
 * 
 * Les crochets déterminent le début et la fin du champs déroulant.
 * Les pipes délimitent les propositions.
 * L'astérisque permet d'indiquer la réponse correcte. 
 * 
 * Ex : Bonjour je suis [un professeur|un détective|*un chien].
 * 
 * Deviendra :
 *      Bonjour je suis <select id="exercice-$numero-$index">
 *      <option data-correct="false">un professeur</option>
 *      [...]
 *      <option data-correct="true">un chien</option>
 *      </select>.
 * 
 * @param int $numero Le numéro de l'exercice dans la leçon.
 * @param string $contenu Le contenu de l'exercice
 * 
 * @return array
 *  
 */
function texte_lacunaire_choix_multiple($numero, $contenu) : array {
    $contenuParse = YamlFrontMatter::parse($contenu);
    $contenu = $contenuParse->body();
    $re = '/\[(.*?)\]/m';
    preg_match_all($re, $contenu, $groupesPropositions, PREG_SET_ORDER, 0);
    foreach ($groupesPropositions as $index => $groupePropositions) {
        $propositions = explode('|', $groupePropositions[1]);
        $options[] = '<option></option>';
        foreach ($propositions as $proposition) {
            if(strpos($proposition, '*') === false){
                $correct = "false";
            } else {
                $correct = "true";
                $proposition = str_replace('*', '', $proposition);
            }
            $options[] = '<option data-correct="' 
                . $correct . '">' 
                . $proposition 
                . '</option>';
        }
        $select = '<select class="exercice-' 
            . $numero . '" id="exercice-' 
            . $numero . '-' 
            . $index . '">' 
            . implode('', $options) 
            . '</select>';
        $contenu = str_replace($groupePropositions[0], $select, $contenu);
        $select = null;
        $options = null;
    }

    if ($contenuParse->image) {
        $contenu = "<img src='$contenuParse->image' alt='Illustration'>" . $contenu;
    } elseif ($contenuParse->iframe) {
        $contenu = "<div class='embed-responsive embed-responsive-16by9'><iframe class='embed-responsive-item' src='$contenuParse->iframe' frameborder='0' allowfullscreen='true' mozallowfullscreen='true' webkitallowfullscreen='true'></iframe></div>" . $contenu;
    }

    return [
        'type' => 'texte_lacunaire_choix_multiple',
        'numero' => $numero,
        'contenu' => nl2br($contenu),
        'afficher_bouton_reponses' => false,
        'afficher_bouton_corriger' => true,
        'consigne' => 'Sélectionnez la réponse correcte dans le champ déroulant.'
    ];
}

/**
 * Permet de remplacer un mot ou groupe de mot par un input.
 * 
 * Ex : Bonjour je suis [un chien].
 */
function texte_lacunaire_reponse_libre($numero, $contenu) {
    $contenuParse = YamlFrontMatter::parse($contenu);
    $contenu = $contenuParse->body();
    $re = '/\[(.*?)\]/m';
    preg_match_all($re, $contenu, $groupesPropositions, PREG_SET_ORDER, 0);
    foreach ($groupesPropositions as $index => $groupePropositions) {
        $input = '<input type="text" data-answer="' 
            . $groupePropositions[1] 
            . '" />';
        $contenu = str_replace($groupePropositions[0], $input, $contenu);
    }

    if ($contenuParse->image) {
        $contenu = "<img src='$contenuParse->image' alt='Illustration'>" . $contenu;
    } elseif ($contenuParse->iframe) {
        $contenu = "<div class='embed-responsive embed-responsive-16by9'><iframe class='embed-responsive-item' src='$contenuParse->iframe' frameborder='0' allowfullscreen='true' mozallowfullscreen='true' webkitallowfullscreen='true'></iframe></div>" . $contenu;
    }

    return [
        'type' => 'texte_lacunaire_reponse_libre',
        'numero' => $numero,
        'contenu' => nl2br($contenu),
        'afficher_bouton_reponses' => true,
        'afficher_bouton_corriger' => true,
        'consigne' => 'Saisissez la réponse correcte dans les champs vides.'
    ];
}

/**
 * WIP
 */
function remettre_dans_ordre($numero, $contenu) {
    $re = '/\[(.*?)\]/m';
    preg_match_all($re, $contenu, $groupesPropositions, PREG_SET_ORDER, 0);
    foreach ($groupesPropositions as $indexGroupePropositions => $groupePropositions) {
        $propositions = explode('|', $groupePropositions[1]);
        $nombrePropositions = count($propositions);
        $propositionsMelangees = null;
        foreach ($propositions as $index => $proposition) {
            $propositionsMelangees[] = [
                'proposition' => $proposition,
                'index' => $index
            ];
        }
        shuffle($propositionsMelangees);
        $span[] = '<span id="blocs-' . $numero . '-' . $indexGroupePropositions . '">';
        foreach ($propositionsMelangees as $propositionMelangee) {
            $span[] = '<span class="badge badge-light" data-index="' 
                . $propositionMelangee['index'] 
                . '">'
                . $propositionMelangee['proposition'] 
                . '</span>';
        }
        $span[] = '</span>';
        $contenu = str_replace($groupePropositions[0], implode('', $span), $contenu);
        $span = null;
    }
    return [
        'type' => 'remettre_dans_ordre',
        'numero' => $numero,
        'contenu' => nl2br($contenu),
        'afficher_bouton_reponses' => true,
        'afficher_bouton_corriger' => true,
        'consigne' => 'Remettez les mots dans le bon ordre.'
    ];
}

/**
 * Permet de copier récursivement l'ensemble des fichiers contenus
 * dans un répertoire.
 * 
 * C.f : https://gist.github.com/gserrano/4c9648ec9eb293b9377b
 * 
 * @return void
 */
function recursive_copy($src, $dst) {
	$dir = opendir($src);
	@mkdir($dst);
	while(( $file = readdir($dir)) ) {
		if (( $file != '.' ) && ( $file != '..' )) {
			if ( is_dir($src . '/' . $file) ) {
				recursive_copy($src .'/'. $file, $dst .'/'. $file);
			}
			else {
				copy($src .'/'. $file,$dst .'/'. $file);
			}
		}
	}
	closedir($dir);
}

?>