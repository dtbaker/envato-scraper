<?php

/**
 *
 * Very Basic Envato Commission Split Calculator
 * Version 0.01
 *
 * Instructions!
 *
 * Change the values in the configuration area below.
 * Upload these files to your website.
 * Visit this script in your browser with the item id in the url, like this:
 *    http://yourwebsite.com/commission-splits.php?item_id=12345678
 * Enter the envato recaptcha code if needed.
 * Send this URL to the other author so they can track sales and their expected commissions.
 * Yew!
 *
 *
 */

/** START CONFIGURATION AREA **/


// warning: make sure your server is secure before using this.
// this script makes use of features that are not possible with the Envato API, so an account password is required.
define('_ENVATO_USERNAME','your_envato_username');
define('_ENVATO_PASSWORD','your_envato_password');

$splits = array(
    12345678 => array( // REPLACE THIS WITH THE ITEM ID YOU WISH TO CALCULATE COMMISSION SPLITS ON
        'start_date' => '2/2013', // WHAT MONTH TO START COMMISSION CALCULATIONS FROM ( in m/Y format, eg: 1/2012 or 12/2011 )
        'authors' => array(
            'your_author_name_here' => array(
                'split' => 0.5, // percentage here (eg: 50% is 0.5)
                'total' => 0,
            ),
            'other_author_name_here' => array(
                'split' => 0.5,  // percentage here (eg: 50% is 0.5)
                'total' => 0,
            ),
            // add more authors here if you need to split 3 or more ways.
        ),
    ),
    // add more item configurations here if needed.
);


/** END CONFIGURATION AREA **/


$item_id = isset($_REQUEST['item_id']) ? (int)$_REQUEST['item_id'] : false;
if(!$item_id || !isset($splits[$item_id]))exit;
require_once 'class.envato_scraper.php';
$my_scraper = new envato_scraper();
$my_scraper->do_login(_ENVATO_USERNAME,_ENVATO_PASSWORD);
$statement = $my_scraper->get_statement($splits[$item_id]['start_date']);
$menu_months = array();
foreach($statement as $item){
    if(isset($item['item_id']) && $item['item_id'] == $item_id){
        $key = date('Y/m',$item['time']);
        if(!isset($menu_months[$key]))$menu_months[$key] = array(
            'sales'=> array(),
            'label'=>date('F Y',$item['time']),
        );
        $menu_months[$key]['sales'][] = $item;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Commission Split</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Le styles -->
    <link href="http://twitter.github.com/bootstrap/assets/css/bootstrap.css" rel="stylesheet">
    <style type="text/css">
      body {
        padding-top: 60px;
        padding-bottom: 40px;
      }
      .sidebar-nav {
        padding: 9px 0;
      }

      @media (max-width: 980px) {
        /* Enable use of floated navbar text */
        .navbar-text.pull-right {
          float: none;
          padding-left: 5px;
          padding-right: 5px;
        }
      }
    </style>
    <link href="http://twitter.github.com/bootstrap/assets/css/bootstrap-responsive.css" rel="stylesheet">

  </head>

  <body>

    <div class="navbar navbar-inverse navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container-fluid">
          <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="brand" href="#">Commission Split</a>
          <div class="nav-collapse collapse">


          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>

    <div class="container-fluid">
      <div class="row-fluid">
        <div class="span3">
          <div class="well sidebar-nav">
            <ul class="nav nav-list">
              <li class="nav-header">Months</li>
                <?php foreach($menu_months as $key=>$menu_month){ ?>
                    <li><a href="#month<?php echo $key;?>"><?php echo $menu_month['label'];?></a></li>
                <?php } ?>
            </ul>
          </div><!--/.well -->
        </div><!--/span-->
        <div class="span9">
          <div class="hero-unit">
            <h2>Commission Split Calculator!</h2>
              <ul>
              <?php foreach($splits[$item_id]['authors'] as $author_id => $split_data){ ?>
                  <li>
                      <?php echo $author_id;?> gets <?php echo $split_data['split']*100;?>%
                  </li>
                  <?php } ?>
              </ul>
          </div>
          <div class="row-fluid">
              <?php foreach($menu_months as $key=>$menu_month){
                  $month_totals = array();
                  ?>
                  <h2 class="page-header" id="#month<?php echo $key;?>"><?php echo $menu_month['label'];?></h2>
                  <ul>
                  <?php foreach($menu_month['sales'] as $item){
                      foreach($splits[$item_id]['authors'] as $author_id => $split_data){
                          if(!isset($month_totals[$author_id]))$month_totals[$author_id]=0;
                          $month_totals[$author_id]+= $item['earnt'] * $split_data['split'];
                          $splits[$item_id]['authors'][$author_id]['total'] += $item['earnt'] * $split_data['split'];
                      }
                      ?>
                      <li>
                          <?php echo date('Y-m-d H:i:s',$item['time']);?> sold for $<?php echo $item['amount'];?> at rate of <?php echo $item['rate'];?>% earning a total of <strong>$<?php echo $item['earnt'];?></strong>.
                      </li>
                  <?php } ?>
                      <?php foreach($month_totals as $author_id => $total){ ?>
                      <li><strong><?php echo $author_id;?> earnt $<?php echo $total;?></strong></li>
                      <?php } ?>
                  </ul>

                <?php } ?>
          </div><!--/row-->
        </div><!--/span-->
      </div><!--/row-->

      <hr>


    </div><!--/.fluid-container-->

    <script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>

  </body>
</html>
