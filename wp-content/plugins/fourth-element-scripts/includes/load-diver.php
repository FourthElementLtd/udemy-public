<?php
include(__DIR__ . '../../../../../wp-config.php');
global $wpdb;
$getDiver = $wpdb->get_row("SELECT * FROM wp_fe_divers WHERE id = ".$_GET['diver']."");

$products = strtr($getDiver->products,array('['=>'',']'=>'','"'=>'',','=>'!'));

$sendResult = $getDiver->divertype.'|'.$getDiver->firstname.'|'.$getDiver->lastname.'|'.$getDiver->bio.'|'.$getDiver->website.'|'.$getDiver->facebook.'|'.$getDiver->instagram.'|'.$getDiver->twitter.'|'.$getDiver->email.'|'.$getDiver->id.'|'.$products;
print_r($sendResult);