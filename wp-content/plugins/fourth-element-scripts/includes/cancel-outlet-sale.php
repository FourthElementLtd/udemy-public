<?php 
include(__DIR__ . '../../../../../wp-config.php');
global $wpdb;

    //*****************************************************************************/
    //UPDATE ALL THE RELEVANT CATEGORIES
    //*****************************************************************************/
    
    $clearanceIdsTerm = array(
            '698'=>'Outlet',
            '754'=>'Technical Outlet',
            '912'=>'Men&#39;s Outlet',
            '917'=>'T-Shirt Outlet',
            '921'=>'Swimwear Outlet',
            '916'=>'Hoodies &amp; Fleeces Outlet',
            '913'=>'Technical Outlet',
            '918'=>'Women&#39;s Outlet',
            '920'=>'T-Shirt Outlet',
            '943'=>'Swimwear Outlet',
            '919'=>'Hoodies &amp; Fleeces Outlet',
            '952'=>'Technical Outlet'
        );

    foreach ( $clearanceIdsTerm as $key => $value ){
        $query = $wpdb->query("UPDATE wp_terms SET name = '".$value."' WHERE term_id = ".$key."");
        $query = $wpdb->query("UPDATE wp_termmeta SET meta_value = '29579' WHERE term_id = ".$key." AND meta_key = 'thumbnail_id'");
    }
    
    $catdesc = '<p class="mobtxt" style="clear:both;">Looking to treat yourself or a loved one? Enjoy a variety of products, from cotton t-shirts to recycled swimsuits. Whatever adventures you plan to have, take Fourth Element with you.</p>';

    $clearanceIdsCont = array(
            '698'=>'<h1 class="h2">Outlet</h1>'.$catdesc,
            '754'=>'<h1 class="h2">Technical Outlet</h1>'.$catdesc,
            '912'=>'<h1 class="h2">Men&#39;s Outlet</h1>'.$catdesc,
            '917'=>'<h1 class="h2">Men&#39;s T-Shirt Outlet</h1>'.$catdesc,
            '921'=>'<h1 class="h2">Men&#39;s Swimwear Outlet</h1>'.$catdesc,
            '916'=>'<h1 class="h2">Men&#39;s Hoodies &amp; Fleeces Outlet</h1>'.$catdesc,
            '913'=>'<h1 class="h2">Men&#39;s Technical Outlet</h1>'.$catdesc,
            '918'=>'<h1 class="h2">Women&#39;s Outlet</h1>'.$catdesc,
            '920'=>'<h1 class="h2">Women&#39;s T-Shirt Outlet</h1>'.$catdesc,
            '943'=>'<h1 class="h2">Women&#39;s Swimwear Outlet</h1>'.$catdesc,
            '919'=>'<h1 class="h2">Women&#39;s Hoodies &amp; Fleeces Outlet</h1>'.$catdesc,
            '952'=>'<h1 class="h2">Women&#39;s Technical Outlet</h1>'.$catdesc
        );


    foreach ( $clearanceIdsCont as $key => $value ){
        $query = $wpdb->query("UPDATE wp_taxonomymeta SET meta_value = 'a:2:{i:0;b:0;s:10:\"cat_header\";s:".strlen($value).":\"".$value."\";}' WHERE taxonomy_id = ".$key."");
    }

    //*****************************************************************************/
    //UPDATE RELEVANT MENU ITEMS
    //*****************************************************************************/
    
    $query = $wpdb->query("UPDATE wp_posts SET post_title = 'Outlet' WHERE ID = 12307");
    $query = $wpdb->query("UPDATE wp_posts SET post_title = 'Outlet' WHERE ID = 12366");

    update_option('fe_outlet_to_sale',0); //set sale flag to 0