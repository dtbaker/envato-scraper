<?php

// EXAMPLE USAGE:
require_once 'class.envato_scraper.php';
$my_scraper = new envato_scraper();
$my_scraper->do_login('username','password');
$statement = $my_scraper->get_statement('1/2013');
$items = $my_scraper->get_users_items('dtbaker',array('codecanyon','themeforest')); // doesn't work with debug enabled.

echo "<pre>";
echo "My Statement: \n";
print_r($statement);
echo "My Items: \n";
print_r($items);
echo "</pre>";