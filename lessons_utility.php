<?php

echo <<<EOL
  ____ ____  _____ _____ ____    _     _____ ____ ___  _   _ 
 / ___|  _ \| ____| ____|  _ \  | |   | ____/ ___/ _ \| \ | |
| |   | |_) |  _| |  _| | |_) | | |   |  _|| |  | | | |  \| |
| |___|  _ <| |___| |___|  _ <  | |___| |__| |__| |_| | |\  |
 \____|_| \_\_____|_____|_| \_\ |_____|_____\____\___/|_| \_|
EOL;

// @TODO List categories
// $categories = array_values(array_diff(scandir('content'), array('..', '.')));
// print_r($categories);

// @TODO Let user choose one or create a new one

// @TODO Let user choose a sub-category or create a new one

// @TODO Let user choose the new lesson folder name

exec('mkdir "content/2.grammaire/genre/un test"');
exec('cp -r templates/* "content/2.grammaire/genre/un test/"');