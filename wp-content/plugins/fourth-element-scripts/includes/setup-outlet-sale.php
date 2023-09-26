<?php 
include(__DIR__ . '../../../../../wp-config.php');
global $wpdb;
$form_fields = array('imgid','strapline','description');

// Entify each field value to go some way to prevent oddities and hacks
foreach ( $form_fields as $this_field_name ) {
    $this_field_value = trim($_POST[$this_field_name]);
        if ( $this_field_value ) {
            $GLOBALS[$this_field_name] = htmlentities(str_replace('\\','',$this_field_value),ENT_QUOTES,'UTF-8');
        } else {
            $GLOBALS[$this_field_name] = NULL;
        }
}


//-----------------------------------------------------------------------------/
//UPDATE ALL THE RELEVANT CATEGORIES
//-----------------------------------------------------------------------------/

$thumbpic = $imgid;

$clearanceIdsTerm = array(
    '698'=>'End of Season Sale',
    '754'=>'Technical End of Season  Sale',
    '912'=>'Men&#39;s End of Season Sale',
    '917'=>'Men&#39;s End of Season T-Shirt Sale',
    '921'=>'Men&#39;s End of Season Swimwear Sale',
    '916'=>'Men&#39;s End of Season Hoodies &amp; Fleeces Sale',
    '913'=>'Men&#39;s End of Season Technical Sale',
    '918'=>'Women&#39;s End of Season Sale',
    '920'=>'Women&#39;s End of Season T-Shirt Sale',
    '943'=>'Women&#39;s End of Season Swimwear  Sale',
    '919'=>'Women&#39;s End of Season Hoodies &amp; Fleeces Sale',
    '952'=>'Women&#39;s End of Season Technical Sale'
);

foreach ( $clearanceIdsTerm as $key => $value ){
    $query = $wpdb->query("UPDATE wp_terms SET name = '".$value."' WHERE term_id = ".$key."");
    $query = $wpdb->query("UPDATE wp_termmeta SET meta_value = '".$thumbpic."' WHERE term_id = ".$key." AND meta_key = 'thumbnail_id'");
}
if ($strapline) {
	$strapline = '<h2 class="h3">'.$strapline.'</h2>';
}
$catdesc = $strapline.'<p class="mobtxt" style="clear:both;">'.str_replace("'","''",$description).'</p>';
$period = 'End of Season';

$clearanceIdsCont = array(
    '698'=>'<h1 class="h2">'.$period.' Sale</h1>'.$catdesc,
    '754'=>'<h1 class="h2">Technical '.$period.' Sale</h1>'.$catdesc,
    '912'=>'<h1 class="h2">Men&#39;s '.$period.' Sale</h1>'.$catdesc,
    '917'=>'<h1 class="h2">T-Shirt '.$period.' Sale</h1>'.$catdesc,
    '921'=>'<h1 class="h2">Swimwear '.$period.' Sale</h1>'.$catdesc,
    '916'=>'<h1 class="h2">Hoodies &amp; Fleeces '.$period.' Sale</h1>'.$catdesc,
    '913'=>'<h1 class="h2">Technical '.$period.' Sale</h1>'.$catdesc,
    '918'=>'<h1 class="h2">Women&#39;s '.$period.' Sale</h1>'.$catdesc,
    '920'=>'<h1 class="h2">T-Shirt '.$period.' Sale</h1>'.$catdesc,
    '943'=>'<h1 class="h2">Swimwear '.$period.' Sale</h1>'.$catdesc,
    '919'=>'<h1 class="h2">Hoodies &amp; Fleeces '.$period.' Sale</h1>'.$catdesc,
    '952'=>'<h1 class="h2">Technical '.$period.' Sale</h1>'.$catdesc
);


foreach ( $clearanceIdsCont as $key => $value ){
    $query = $wpdb->query("UPDATE wp_taxonomymeta SET meta_value = 'a:2:{i:0;b:0;s:10:\"cat_header\";s:".strlen($value).":\"".$value."\";}' WHERE taxonomy_id = ".$key."");
}

//-----------------------------------------------------------------------------/
//UPDATE RELEVANT MENU ITEMS
//-----------------------------------------------------------------------------/

$query = $wpdb->query("UPDATE wp_posts SET post_title = 'Mens Sale' WHERE ID = 12307");
$query = $wpdb->query("UPDATE wp_posts SET post_title = 'Womens Sale' WHERE ID = 12366");


update_option('fe_outlet_to_sale',1); //set flag to on 