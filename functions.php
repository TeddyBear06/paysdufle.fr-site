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

/**
 * Permet de supprimer les mots "inutiles" d'un texte.
 * 
 * La liste des mots provient de https://raw.githubusercontent.com/stopwords-iso/stopwords-fr/master/stopwords-fr.json
 */
function remove_stop_words($text)
{
    $stopWords = ["a","abord","absolument","afin","ah","ai","aie","aient","aies","ailleurs","ainsi","ait","allaient","allo","allons","allô","alors","anterieur","anterieure","anterieures","apres","après","as","assez","attendu","au","aucun","aucune","aucuns","aujourd","aujourd'hui","aupres","auquel","aura","aurai","auraient","aurais","aurait","auras","aurez","auriez","aurions","aurons","auront","aussi","autant","autre","autrefois","autrement","autres","autrui","aux","auxquelles","auxquels","avaient","avais","avait","avant","avec","avez","aviez","avions","avoir","avons","ayant","ayez","ayons","b","bah","bas","basee","bat","beau","beaucoup","bien","bigre","bon","boum","bravo","brrr","c","car","ce","ceci","cela","celle","celle-ci","celle-là","celles","celles-ci","celles-là","celui","celui-ci","celui-là","celà","cent","cependant","certain","certaine","certaines","certains","certes","ces","cet","cette","ceux","ceux-ci","ceux-là","chacun","chacune","chaque","cher","chers","chez","chiche","chut","chère","chères","ci","cinq","cinquantaine","cinquante","cinquantième","cinquième","clac","clic","combien","comme","comment","comparable","comparables","compris","concernant","contre","couic","crac","d","da","dans","de","debout","dedans","dehors","deja","delà","depuis","dernier","derniere","derriere","derrière","des","desormais","desquelles","desquels","dessous","dessus","deux","deuxième","deuxièmement","devant","devers","devra","devrait","different","differentes","differents","différent","différente","différentes","différents","dire","directe","directement","dit","dite","dits","divers","diverse","diverses","dix","dix-huit","dix-neuf","dix-sept","dixième","doit","doivent","donc","dont","dos","douze","douzième","dring","droite","du","duquel","durant","dès","début","désormais","e","effet","egale","egalement","egales","eh","elle","elle-même","elles","elles-mêmes","en","encore","enfin","entre","envers","environ","es","essai","est","et","etant","etc","etre","eu","eue","eues","euh","eurent","eus","eusse","eussent","eusses","eussiez","eussions","eut","eux","eux-mêmes","exactement","excepté","extenso","exterieur","eûmes","eût","eûtes","f","fais","faisaient","faisant","fait","faites","façon","feront","fi","flac","floc","fois","font","force","furent","fus","fusse","fussent","fusses","fussiez","fussions","fut","fûmes","fût","fûtes","g","gens","h","ha","haut","hein","hem","hep","hi","ho","holà","hop","hormis","hors","hou","houp","hue","hui","huit","huitième","hum","hurrah","hé","hélas","i","ici","il","ils","importe","j","je","jusqu","jusque","juste","k","l","la","laisser","laquelle","las","le","lequel","les","lesquelles","lesquels","leur","leurs","longtemps","lors","lorsque","lui","lui-meme","lui-même","là","lès","m","ma","maint","maintenant","mais","malgre","malgré","maximale","me","meme","memes","merci","mes","mien","mienne","miennes","miens","mille","mince","mine","minimale","moi","moi-meme","moi-même","moindres","moins","mon","mot","moyennant","multiple","multiples","même","mêmes","n","na","naturel","naturelle","naturelles","ne","neanmoins","necessaire","necessairement","neuf","neuvième","ni","nombreuses","nombreux","nommés","non","nos","notamment","notre","nous","nous-mêmes","nouveau","nouveaux","nul","néanmoins","nôtre","nôtres","o","oh","ohé","ollé","olé","on","ont","onze","onzième","ore","ou","ouf","ouias","oust","ouste","outre","ouvert","ouverte","ouverts","o","où","p","paf","pan","par","parce","parfois","parle","parlent","parler","parmi","parole","parseme","partant","particulier","particulière","particulièrement","pas","passé","pendant","pense","permet","personne","personnes","peu","peut","peuvent","peux","pff","pfft","pfut","pif","pire","pièce","plein","plouf","plupart","plus","plusieurs","plutôt","possessif","possessifs","possible","possibles","pouah","pour","pourquoi","pourrais","pourrait","pouvait","prealable","precisement","premier","première","premièrement","pres","probable","probante","procedant","proche","près","psitt","pu","puis","puisque","pur","pure","q","qu","quand","quant","quant-à-soi","quanta","quarante","quatorze","quatre","quatre-vingt","quatrième","quatrièmement","que","quel","quelconque","quelle","quelles","quelqu'un","quelque","quelques","quels","qui","quiconque","quinze","quoi","quoique","r","rare","rarement","rares","relative","relativement","remarquable","rend","rendre","restant","reste","restent","restrictif","retour","revoici","revoilà","rien","s","sa","sacrebleu","sait","sans","sapristi","sauf","se","sein","seize","selon","semblable","semblaient","semble","semblent","sent","sept","septième","sera","serai","seraient","serais","serait","seras","serez","seriez","serions","serons","seront","ses","seul","seule","seulement","si","sien","sienne","siennes","siens","sinon","six","sixième","soi","soi-même","soient","sois","soit","soixante","sommes","son","sont","sous","souvent","soyez","soyons","specifique","specifiques","speculatif","stop","strictement","subtiles","suffisant","suffisante","suffit","suis","suit","suivant","suivante","suivantes","suivants","suivre","sujet","superpose","sur","surtout","t","ta","tac","tandis","tant","tardive","te","tel","telle","tellement","telles","tels","tenant","tend","tenir","tente","tes","tic","tien","tienne","tiennes","tiens","toc","toi","toi-même","ton","touchant","toujours","tous","tout","toute","toutefois","toutes","treize","trente","tres","trois","troisième","troisièmement","trop","très","tsoin","tsouin","tu","té","u","un","une","unes","uniformement","unique","uniques","uns","v","va","vais","valeur","vas","vers","via","vif","vifs","vingt","vivat","vive","vives","vlan","voici","voie","voient","voilà","voire","vont","vos","votre","vous","vous-mêmes","vu","vé","vôtre","vôtres","w","x","y","z","zut","à","â","ça","ès","étaient","étais","était","étant","état","étiez","étions","été","étée","étées","étés","êtes","être","ô"];

    return preg_replace('/\b('.implode('|', $stopWords).')\b/', '', $text);
}

?>