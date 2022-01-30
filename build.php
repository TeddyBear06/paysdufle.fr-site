<?php

$debut_build = microtime(true);

# Permet de connaître l'environnement de build
$app_env = $_ENV["APP_ENV"] ?? 'production';
$tts_domain = $_ENV["TTS_DOMAIN"] ?? 'tts.localhost';
$mp3_domain = $_ENV["MP3_DOMAIN"] ?? 'mp3.localhost';
$meilisearch_master_key = $_ENV["MEILISEARCH_MASTER_KEY"] ?? null;
$meilisearch_instance = $_ENV["MEILISEARCH_INSTANCE"] ?? null;
$meilisearch_endpoint = $_ENV["MEILISEARCH_ENDPOINT"] ?? null;
$meilisearch_api_key = $_ENV["MEILISEARCH_MASTER_KEY"] ?? null;
$rgpdcvc_endpoint = $_ENV["RGPDCVC_ENDPOINT"] ?? null;
$base_domain = 'https://paysdufle.fr';

################################
# Useful variables for templates
################################
$usefulVariablesForTemplates = [
    'meilisearch_instance' => $meilisearch_instance,
    'meilisearch_endpoint' => $meilisearch_endpoint,
    'meilisearch_api_key' => $meilisearch_api_key,
    'rgpdcvc_endpoint' => $rgpdcvc_endpoint,
];

# On charge les librairies
require __DIR__ . '/vendor/autoload.php';

# On inclue le fichier avec les fonctions necéssaires au site
require_once 'functions.php';

use Illuminate\Support\Str;

use Spatie\YamlFrontMatter\YamlFrontMatter;
use Spatie\SchemaOrg\Schema;

use Carbon\Carbon;

use MeiliSearch\Client;

if ($meilisearch_master_key !== null) {
    $client = new Client('http://meilisearch:7700', $meilisearch_master_key);
    $subCategoriesIndex = $client->index('subCategories');
    $lessonsIndex = $client->index('lessons');
    # On ajoute les facettes
    $promise = $lessonsIndex->updateFilterableAttributes(['categorie', 'sous-categorie', 'tag']);
    $lessonsIndex->waitForTask($promise['updateId']);
}

# On instancie Twig avec le répertoire contenant les templates
$loader = new \Twig\Loader\FilesystemLoader(dirname(__FILE__) . '/views');
$twig = new \Twig\Environment($loader, [
    'auto_reload' => true
]);

# On instancie le parseur de Markdown
$p = new Parsedown();

if (isset($argv[1]) && $argv[1] == 'local') {
    $repertoire_source = '/usr/paysdufle.fr/';
} else {
    $repertoire_source = '/usr/paysdufle.fr/src/';
}

$repertoire_build = '/usr/paysdufle.fr/build/';

###############################
# On vide le répertoire "build"
# https://stackoverflow.com/questions/4594180/deleting-all-files-from-a-folder-using-php#answer-24563703
###############################
if (file_exists($repertoire_build)) {
    $dir = $repertoire_build;
    $di = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
    $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($ri as $file) {
        $file->isDir() ? rmdir($file) : unlink($file);
    }
} else {
    mkdir($repertoire_build, 0775);
}

##############################################
# Copie des assets dans le répertoire de build
##############################################
if (! file_exists($repertoire_build.'assets/')) {
    mkdir($repertoire_build.'assets/', 0775);
}
recursive_copy($repertoire_source.'assets/', $repertoire_build);

###################################################
# Création du fichier contenant la liste des leçons
###################################################
if (! file_exists($repertoire_build.'assets/json/liste_lecons.json')) {
    mkdir($repertoire_build . 'assets/json/', 0775);
    file_put_contents($repertoire_build.'assets/json/liste_lecons.json', '{}');
}
$liste_lecons = json_decode(file_get_contents($repertoire_build.'assets/json/liste_lecons.json'), true);
$liste_lecons_complete = [];

###################################
# Construction de la page d'accueil
###################################
$listeCategories = array_diff(scandir($repertoire_source.'content/'), array('..', '.'));
foreach ($listeCategories as $categorie) {
    $categorie = explode('.', $categorie)[1];
    $slug_categorie = Str::slug($categorie);
    $url_categorie = $slug_categorie.'/index.html';
    $label_categorie = ucfirst($categorie);
    $categorieArray = [
        'raw_categorie' => $categorie,
        'slug_categorie' => $slug_categorie,
        'cover_categorie' => str_replace(' ', '-', $categorie),
        'url_categorie' => $url_categorie,
        'label_categorie' => $label_categorie
    ];
    $categories[] = $categorieArray;
    $categoriesIndex[] = $categorieArray;
    $sitemap[$slug_categorie] = [
        'url' => $url_categorie,
        'label' => $label_categorie
    ];
}
// On ajoute une catégorie surprise
$categoriesIndex[] = [
    'raw_categorie' => 'Suivez moi',
    'slug_categorie' => 'suivez-moi',
    'cover_categorie' => 'suivez-moi',
    'url_categorie' => '#suivez-moi-surprise',
    'label_categorie' => 'Suivez moi'
];
$accueil = $twig->load('index.html')->render(
    array_merge(
        $usefulVariablesForTemplates, 
        [
            'categories' => $categoriesIndex,
        ]
    )
);
file_put_contents($repertoire_build . 'index.html', $accueil);
$accueil = null;
$slug_categorie = null;
$url_categorie = null;
$label_categorie = null;
$categoriesIndex = null;

###########
# Les index
###########
$indexSousCategorie = 1;
$indexLecon = 1;

###################################
# Construction du contenu dynamique
###################################
foreach ($categories as $numero => $categorie) {
    $numero++;
    if (! file_exists($repertoire_build.$categorie['slug_categorie'].'/')) {
        mkdir($repertoire_build.$categorie['slug_categorie'].'/', 0775);
    }
    ###########################################
    # 1/3 Création des pages de sous-catégories
    ###########################################
    $listeSousCategories = array_diff(scandir($repertoire_source.'content/'.$numero.'.'.$categorie['raw_categorie'].'/'), array('..', '.'));
    foreach ($listeSousCategories as $sousCategorie) {
        $slug_sousCategorie = \Illuminate\Support\Str::slug($sousCategorie);
        $url_sousCategorie = $categorie['slug_categorie'].'/'.$slug_sousCategorie.'/index.html';
        $label_sousCategorie = ucfirst($sousCategorie);
        $sousCategories[] = [
            'raw_sousCategorie' => $sousCategorie,
            'url_sousCategorie' => $url_sousCategorie,
            'slug_sousCategorie' => $slug_sousCategorie,
            'label_sousCategorie' => $label_sousCategorie
        ];
        $sitemap[$categorie['slug_categorie']]['sous_categories'][$slug_sousCategorie] = [
            'url' => $url_sousCategorie,
            'label' => $label_sousCategorie
        ];
    }
    $items = [
        [
            'active' => false,
            'url' => '/',
            'label' => '<i class="fas fa-home"></i>'
        ],
        [
            'active' => true,
            'label' => $categorie['label_categorie']
        ]
    ];
    $contenu = $twig->load('sous-categories.html')->render(
        array_merge(
            $usefulVariablesForTemplates, 
            [
                'slug_categorie' => $categorie['slug_categorie'],
                'cover_categorie' => str_replace(' ', '-', $categorie['raw_categorie']),
                'label_categorie' => $categorie['label_categorie'],
                'sousCategories' => $sousCategories,
                'items' => $items,
                'meta_url' => $base_domain.'/'.$categorie['slug_categorie'].'/index.html'
            ]
        )
    );
    file_put_contents($repertoire_build.$categorie['slug_categorie'].'/index.html', $contenu);
    ##############################################
    # 2/3 Création des pages de listing des leçons
    ##############################################
    foreach ($listeSousCategories as $sousCategorie) {
        $slug_sousCategorie = \Illuminate\Support\Str::slug($sousCategorie);
        $url_sousCategorie = $categorie['slug_categorie'].'/'.$slug_sousCategorie.'/index.html';
        $label_sousCategorie = ucfirst($sousCategorie);
        if (! file_exists($repertoire_build.$categorie['slug_categorie'].'/'.$slug_sousCategorie.'/')) {
            mkdir($repertoire_build.$categorie['slug_categorie'].'/'.$slug_sousCategorie.'/', 0775);
        }
        $listeLecons = array_diff(scandir($repertoire_source.'content/'.$numero.'.'.$categorie['raw_categorie'].'/'.$sousCategorie.'/'), array('..', '.'));
        foreach ($listeLecons as $lecon) {
            $slug_lecon = \Illuminate\Support\Str::slug($lecon);
            $url_lecon = $categorie['slug_categorie'].'/'.$slug_sousCategorie.'/'.$slug_lecon.'/index.html';
            $label_lecon = ucfirst($lecon);
            $lecons[] = [
                'raw_lecon' => $lecon,
                'slug_lecon' => $slug_lecon,
                'url_lecon' => $url_lecon,
                'label_lecon' => $label_lecon
            ];
            $sitemap[$categorie['slug_categorie']]['sous_categories'][$slug_sousCategorie]['lecons'][] = [
                'url' => $url_lecon,
                'label' => $label_lecon
            ];
        }
        $items = [
            [
                'active' => false,
                'url' => '/',
                'label' => '<i class="fas fa-home"></i>'
            ],
            [
                'active' => false,
                'url' => '/' . $categorie['slug_categorie'] . '/index.html',
                'label' => $categorie['label_categorie']
            ],
            [
                'active' => true,
                'label' => $label_sousCategorie
            ]
        ];
        $contenu = $twig->load('listing-lecons.html')->render(
            array_merge(
                $usefulVariablesForTemplates, 
                [
                    'slug_categorie' => $categorie['slug_categorie'],
                    'cover_categorie' => str_replace(' ', '-', $categorie['raw_categorie']),
                    'label_categorie' => $categorie['label_categorie'],
                    'label_sousCategorie' => $label_sousCategorie,
                    'lecons' => $lecons,
                    'items' => $items,
                    'meta_url' => $base_domain.'/'.$categorie['slug_categorie'].'/'.$slug_sousCategorie.'/index.html'
                ]
            )
        );
        $subCategoriesIndexDocuments[] = [
            'id' => $indexSousCategorie,
            'categorie' => $categorie['label_categorie'],
            'categorie_tokenized' => prepare_tokenisation($categorie['label_categorie']),
            'label' => $label_sousCategorie,
            'label_tokenized' => prepare_tokenisation($label_sousCategorie),
            'url' => $base_domain.'/'.$categorie['slug_categorie'].'/'.$slug_sousCategorie.'/index.html',
        ];
        file_put_contents($repertoire_build.$categorie['slug_categorie'].'/'.$slug_sousCategorie.'/index.html', $contenu);
        #################################
        # 3/3 Création de pages de leçons
        #################################
        foreach ($lecons as $lecon) {
            // Si le répertoire de la leçon n'existe pas, on le créer
            if (! file_exists($repertoire_build.$categorie['slug_categorie'].'/'.$slug_sousCategorie.'/'.$lecon['slug_lecon'].'/')) {
                mkdir($repertoire_build . $categorie['slug_categorie'] . '/' . $slug_sousCategorie . '/' . $lecon['slug_lecon'] . '/', 0775);
            }
            // Si le répertoire des images de la leçon n'existe pas, on le créer
            if (! file_exists($repertoire_build . $categorie['slug_categorie'] . '/' . $slug_sousCategorie . '/' . $lecon['slug_lecon'] . '/images/')) {
                mkdir($repertoire_build . $categorie['slug_categorie'] . '/' . $slug_sousCategorie . '/' . $lecon['slug_lecon'] . '/images/', 0775);
            }
            recursive_copy(
                $repertoire_source . 'content/' . $numero . '.' . $categorie['raw_categorie'] . '/' . $sousCategorie . '/' . $lecon['raw_lecon'] . '/images/', 
                $repertoire_build . $categorie['slug_categorie'] . '/' . $slug_sousCategorie . '/' . $lecon['slug_lecon'] . '/images/'
            );
            if (is_dir($repertoire_source . 'content/' . $numero . '.' . $categorie['raw_categorie'] . '/' . $sousCategorie . '/' . $lecon['raw_lecon'] . '/exercices/')) {
                $listeExercices = array_diff(scandir($repertoire_source . 'content/' . $numero . '.' . $categorie['raw_categorie'] . '/' . $sousCategorie . '/' . $lecon['raw_lecon'] . '/exercices/'), array('..', '.'));
                foreach ($listeExercices as $exercice) {
                    $contenuBrutExercice = file_get_contents($repertoire_source . 'content/' . $numero . '.' . $categorie['raw_categorie'] . '/' . $sousCategorie . '/' . $lecon['raw_lecon'] . '/exercices/' . $exercice);
                    $numeroExercice = explode('.', $exercice)[0];
                    $typeExercice = explode('.', $exercice)[1];
                    $exercices[] = $typeExercice($numeroExercice, $contenuBrutExercice);
                }
            } else {
                $exercices = null;
            }
            $leconParsee = YamlFrontMatter::parse(file_get_contents($repertoire_source . 'content/' . $numero . '.' . $categorie['raw_categorie'] . '/' . $sousCategorie . '/' . $lecon['raw_lecon'] . '/lecon.md'));
            $contenuLecon = $p->text($leconParsee->body());
            $items = [
                [
                    'active' => false,
                    'url' => '/',
                    'label' => '<i class="fas fa-home"></i>'
                ],
                [
                    'active' => false,
                    'url' => '/' . $categorie['slug_categorie'] . '/index.html',
                    'label' => $categorie['label_categorie']
                ],
                [
                    'active' => false,
                    'url' => '/' . $categorie['slug_categorie'] . '/' . $slug_sousCategorie . '/index.html',
                    'label' => $label_sousCategorie
                ],
                [
                    'active' => true,
                    'label' => $leconParsee->titre
                ]
            ];
            $base_lecon_url = $base_domain.'/' . $categorie['slug_categorie'] . '/' . $slug_sousCategorie . '/' . $lecon['slug_lecon'] . '/';

            $resultatEnrichi = Schema::course()
                ->name($leconParsee->titre)
                ->description($leconParsee->description)
                ->provider(
                    Schema::organization()
                        ->name('Pays du FLE')
                        ->sameAs($base_domain)
                );

            $contenu = $twig->load('lecon.html')->render(
                array_merge(
                    $usefulVariablesForTemplates, 
                    [
                        'resultatEnrichi' => $resultatEnrichi->toScript(),
                        'titre' => $leconParsee->titre,
                        'description' => $leconParsee->description,
                        'couverture' => $leconParsee->couverture,
                        'lecon' => $contenuLecon,
                        'exercices' => $exercices,
                        'items' => $items,
                        'meta_url' => $base_lecon_url . 'index.html',
                        'meta_titre' => $leconParsee->titre,
                        'meta_description' => $leconParsee->description,
                        'meta_image' => $base_lecon_url . $leconParsee->couverture
                    ]
                )
            );
            $contenu = str_replace('[TTSdomain]', $tts_domain, $contenu);
            $contenu = str_replace('[MP3domain]', $mp3_domain, $contenu);
            if (
                $categorie['slug_categorie'] !== 'jardin-dalicja' 
                && $categorie['slug_categorie'] !== 'fle-ludique' 
                && $categorie['slug_categorie'] !== 'coups-de-coeur')
            {
                $liste_lecons[] = [
                    'url' => '/' . $categorie['slug_categorie'] . '/' . $slug_sousCategorie . '/' . $lecon['slug_lecon'] . '/index.html',
                    'label' => $leconParsee->titre
                ];
            }
            $liste_lecons_complete[] = [
                'url' => '/' . $categorie['slug_categorie'] . '/' . $slug_sousCategorie . '/' . $lecon['slug_lecon'] . '/index.html',
                'label' => $leconParsee->titre
            ];
            $document = [
                'id' => $indexLecon,

                'categorie' => $categorie['label_categorie'],
                'categorie_tokenized' => prepare_tokenisation($categorie['label_categorie']),

                'sous-categorie' =>  $label_sousCategorie,
                'sous-categorie_tokenized' => prepare_tokenisation( $label_sousCategorie),

                'titre' => $leconParsee->titre,
                'titre_tokenized' => prepare_tokenisation($leconParsee->titre),

                'contenu' => strip_tags($contenuLecon),
                'contenu_tokenized' => prepare_tokenisation(strip_tags($contenuLecon)),

                'url' => $base_lecon_url.'index.html',
                'image' => $base_lecon_url.$leconParsee->couverture,
            ];
            if ($leconParsee->tag !== null) {
                $document['contenu_tokenized'] = inject_tag($document['contenu_tokenized'], $leconParsee->tag);
                $document['tag'] = $leconParsee->tag;
            }
            $lessonsIndexDocuments[] = $document;
            file_put_contents($repertoire_build . $categorie['slug_categorie'] . '/' . $slug_sousCategorie . '/' . $lecon['slug_lecon'] . '/index.html', $contenu);
            $listeExercices = null;
            $exercices = null;
            $document = null;
            $indexLecon++;
        }
        $lecons = null;
        $leconsFormatees = null;
        $indexSousCategorie++;
    }
    if ($meilisearch_master_key !== null) {
        $subCategoriesIndex->addDocuments($subCategoriesIndexDocuments);
        $subCategoriesIndexDocuments = null;
    }
    $sousCategories = null;
    $sousCategoriesFormatees = null;
    $contenu = null;
    $categorie = $categorie['label_categorie'];
}
if ($meilisearch_master_key !== null) {
    $lessonsIndex->addDocuments($lessonsIndexDocuments);
    $lessonsIndexDocuments = null;
}
file_put_contents($repertoire_build . 'assets/json/liste_lecons.json', json_encode($liste_lecons, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE));

##################################
# Construction des pages statiques
##################################
if (! file_exists($repertoire_build . 'pages/')) {
    mkdir($repertoire_build . 'pages/', 0775);
}
$pages = array_diff(scandir($repertoire_source . 'views/pages/', 1), array('..', '.'));
foreach($pages as $key => $page) {
    $pageParsee = YamlFrontMatter::parse(file_get_contents($repertoire_source.'views/pages/'.$page));
    $template = $twig->createTemplate($pageParsee->body());
    $items = [
        [
            'active' => false,
            'url' => '/',
            'label' => '<i class="fas fa-home"></i>'
        ],
        [
            'active' => true,
            'label' => $pageParsee->nom
        ]
    ];
    $contenu = $template->render(
        array_merge(
            $usefulVariablesForTemplates, 
            [
                'sitemap' => $sitemap ?? null,
                'items' => $items
            ]
        )
    );
    file_put_contents($repertoire_build . "pages/$page", $contenu);
}

#######################
# Génération du sitemap
#######################

$sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
$sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
$sitemap .= '<url><loc>'.$base_domain.'</loc><lastmod>'.Carbon::now()->format('Y-m-d').'</lastmod></url>';
foreach ($pages as $page) {
    $sitemap .= '<url><loc>'.$base_domain.'/pages/'.$page.'</loc><lastmod>'.Carbon::now()->format('Y-m-d').'</lastmod></url>';
}
foreach ($liste_lecons_complete as $lecon) {
    $sitemap .= '<url><loc>'.$base_domain.$lecon['url'].'</loc><lastmod>'.Carbon::now()->format('Y-m-d').'</lastmod></url>';
}
$sitemap .= '</urlset>';
file_put_contents($repertoire_build.'sitemap.xml', $sitemap);

$liste_lecons = null;
$liste_lecons_complete = null;

#######################
# Ajout des filigranes
#######################
$Directory = new RecursiveDirectoryIterator($repertoire_build);
$Iterator = new RecursiveIteratorIterator($Directory);
$Regex = new RegexIterator($Iterator, '/^.+(.jpe?g|.png)$/i', RecursiveRegexIterator::GET_MATCH);

foreach($Regex as $name => $Regex){
    if (strpos($name, 'wm_') !== false) {
        $stamp = imagecreatefrompng($repertoire_source . 'assets/assets/img/template/watermark.png');
        $im = imagecreatefromjpeg($name);
        $marge_right = 0;
        $marge_bottom = 0;
        $sx = imagesx($stamp);
        $sy = imagesy($stamp);
        imagecopy($im, $stamp, imagesx($im) - $sx - $marge_right, imagesy($im) - $sy - $marge_bottom, 0, 0, imagesx($stamp), imagesy($stamp));
        imagejpeg($im, $name, 100);
        imagedestroy($im);
    }
}

$fin_build = microtime(true);
$duree_execution = $fin_build - $debut_build;

echo '[OK] Build terminé en ' . round($duree_execution, 2) . ' secondes.';

return 0;

?>
