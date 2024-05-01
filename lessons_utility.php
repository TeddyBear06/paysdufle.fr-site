<?php

error_reporting(E_ALL ^ E_DEPRECATED);

# On charge les librairies
require __DIR__ . '/vendor/autoload.php';

$climate = new League\CLImate\CLImate;

const NOUVELLE_CATEGORIE = 'Nouvelle catégorie';
const NOUVELLE_SOUS_CATEGORIE = 'Nouvelle sous-catégorie';
const QUITTER = 'Quitter';
const OUI_NON_ARRAY = ['Oui', 'Non'];
const OUI = 'Oui';

# Configuration
$climate->clear();
$climate->border('*');
$climate->backgroundWhite()->blue('Outil de création de leçon');
$climate->border('*');

# Sélection ou création d'une catégorie
$categories = array_values(array_diff(scandir('content'), array('..', '.')));
$categories[] = NOUVELLE_CATEGORIE;
$categories[] = QUITTER;
$options = $categories;
$input = $climate->radio('Veuillez sélectionner la catégorie dans laquelle créér la leçon :', $options);
$categorie = $input->prompt();

if ($categorie === NOUVELLE_CATEGORIE) {
    $climate->clear();
    # Saisie du nom de la nouvelle catégorie
    $input = $climate->input('Quelle est le nom de votre nouvelle catégorie :');
    $categorie = $input->prompt();
    # Saisie du nom de la nouvelle sous-catégorie
    $input = $climate->input('Quelle est le nom de votre nouvelle sous-catégorie :');
    $sousCategorie = $input->prompt();
} elseif ($categorie === QUITTER) {
    exit();
}else {
    # Sélection ou création d'une sous-catégorie
    $sousCategories = array_values(array_diff(scandir('content/'.$categorie), array('..', '.')));
    $sousCategories[] = NOUVELLE_SOUS_CATEGORIE;
    $sousCategories[] = QUITTER;    
    $options = $sousCategories;
    $input = $climate->radio('Veuillez sélectionner la sous-catégorie dans laquelle créér la leçon :', $options);
    $sousCategorie = $input->prompt();

    if ($sousCategorie === NOUVELLE_SOUS_CATEGORIE) {
        # Saisie du nom de la nouvelle sous-catégorie
        $input = $climate->input('Quelle est le nom de votre nouvelle sous-catégorie :');
        $sousCategorie = $input->prompt();
    } elseif ($sousCategorie === QUITTER) {
        exit();
    }
}

# Saisie du titre de la leçon
$input = $climate->input('Quel est le titre de votre nouvelle leçon :');
$titreLecon = $input->prompt();

$data = [
    [
  		'catégorie' => $categorie,
  		'Sous-catégorie' => $sousCategorie,
  		'Titre de la leçon' => $titreLecon,
    ]
];

$climate->table($data);

$options = OUI_NON_ARRAY;
$input = $climate->radio('Les informations contenues dans le tableau ci-dessus sont-elles correctes ?', $options);
$reponse = $input->prompt();

if ($reponse === OUI) {

    $chemin = 'content/'.$categorie.'/'.$sousCategorie.'/'.$titreLecon;

    if (file_exists($chemin) && is_dir($chemin)) {
        $options = OUI_NON_ARRAY;
        $input = $climate->radio("Une leçon avec le même nom existe déjà, je dois l'écraser ?", $options);
        $reponse = $input->prompt();
    }

    if ($reponse === OUI) {
        # Création de la leçon à partir des templates
        exec('mkdir -p "'.$chemin.'"');
        exec('cp -r templates/* "'.$chemin.'/"');

        # Simulation d'une action
        $climate->clear();
        $climate->border('*');
        $climate->backgroundWhite()->blue('Création de la leçon en cours...');
        $progress = $climate->progress()->total(100);
        for ($i = 0; $i <= 100; $i++) {
            $progress->current($i);
            usleep(30000);
        }
    }

} else {
    exit();
}