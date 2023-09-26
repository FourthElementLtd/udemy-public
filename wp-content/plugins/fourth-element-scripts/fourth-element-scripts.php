<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.fourthelement.com
 * @since             1.0.0
 * @package           Fourth_Element_Scripts
 *
 * @wordpress-plugin
 * Plugin Name:       Fourth Element Woo Scripts
 * Plugin URI:        https://fourthelement.com
 * Description:       This plugin contains scripts that will be used in conjunction with Woocommerce on the Life site.
 * Version:           1.1.1
 * Author:            Fourth Element Devs
 * Author URI:        https://www.fourthelement.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       fourth-element-scripts
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PLUGIN_NAME_VERSION', '1.0.3' );
define ("OCMAPKEY","f44ae06cc69e4c029d00b284ab0497f6");

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-fourth-element-scripts-activator.php
 */
function activate_fourth_element_scripts() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-fourth-element-scripts-activator.php';
    Fourth_Element_Scripts_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-fourth-element-scripts-deactivator.php
 */
function deactivate_fourth_element_scripts() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-fourth-element-scripts-deactivator.php';
    Fourth_Element_Scripts_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_fourth_element_scripts' );
register_deactivation_hook( __FILE__, 'deactivate_fourth_element_scripts' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-fourth-element-scripts.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_fourth_element_scripts() {

    $plugin = new Fourth_Element_Scripts();
    $plugin->run();

}
run_fourth_element_scripts();

// ADD ADMIN MENU ITEM
function fe_register_fourth_element_menu() {
    add_menu_page(
        __('Fourth Element', 'textdomain'),
        'Fourth Element',
        'administrator',
        'fourth-element-scripts/fe.php',
        '',
        plugins_url( 'fourth-element-scripts/images/fe-logo.png'),
        2
    );

}
add_action('admin_menu','fe_register_fourth_element_menu');

// ADD ADMIN MENU ITEM
function fe_register_fourth_element_marketing_menu() {
    add_menu_page(
        __('FE Marketing', 'textdomain'),
        'FE Marketing',
        'administrator',
        'fourth-element-scripts/fe-market.php',
        '',
        plugins_url( 'fourth-element-scripts/images/fe-logo.png'),
        2
    );

}
add_action('admin_menu','fe_register_fourth_element_marketing_menu');

// ADD SUBMENU ITEMS
function fe_add_menu(){

    //Marketing Menu
    add_submenu_page( 'fourth-element-scripts/fe-market.php', 'Manage Our Team', 'Manage Our Team','administrator', 'manage-fe-team', 'fe_manage_fe_team');
    add_submenu_page( 'fourth-element-scripts/fe-market.php', 'Manage Team/Ambassadors', 'Manage Team/Ambassadors','administrator', 'manage-team-ambassadors', 'fe_manage_team_ambassadors');
    add_submenu_page( 'fourth-element-scripts/fe-market.php', 'Outlet <-> Sale', 'Set Outlet <-> Sale','administrator', 'outlet-to-sale', 'fe_outlet_to_sale');    
    add_submenu_page( 'fourth-element-scripts/fe-market.php', 'Apply Checkout Coupon', 'Apply Checkout Coupon','administrator', 'apply-checkout-coupon', 'fe_apply_checkout_coupon');
    add_submenu_page( 'fourth-element-scripts/fe-market.php', 'Amend Publication Date', 'Amend Publication Date','administrator', 'amend_pub_date', 'fe_amend_pub_date');
    add_submenu_page( 'fourth-element-scripts/fe-market.php', 'Set Checkout Message', 'Set Checkout Message','administrator', 'set-checkout-message', 'fe_set_checkout_message');  
    add_submenu_page( 'fourth-element-scripts/fe-market.php', 'Wishlist Comp Entries', 'Wishlist Comp Entries','administrator', 'view-wishlist-entries', 'fe_view_wishlist_entries');
    add_submenu_page( 'fourth-element-scripts/fe-market.php', 'Store Date Related Coupon', 'Store Date Related Coupon','administrator', 'load-coupon-frm', 'fe_load_coupon_frm'); 

    //Devs Menu
    add_submenu_page( 'fourth-element-scripts/fe.php', 'Assign/Remove Products to Categories', 'Assign/Remove Products to Categories','administrator', 'load-category-data', 'fe_load_category_data');
    add_submenu_page( 'fourth-element-scripts/fe.php', 'Returns', 'Returns','administrator', 'returns-admin', 'fe_returns_admin');    
    add_submenu_page( 'fourth-element-scripts/fe.php', 'Export Orders for Sage', 'Export Orders for Sage','administrator', 'sage-admin', 'fe_sage_admin');
    add_submenu_page( 'fourth-element-scripts/fe.php', 'Export CONNECT referrals', 'Export CONNECT referrals','administrator', 'connect-admin', 'fe_connect_admin');    
    add_submenu_page( 'fourth-element-scripts/fe.php', 'Export Refund Data', 'Export Refund Data','administrator', 'refund-exports', 'fe_refund_exports'); 
    add_submenu_page( 'fourth-element-scripts/fe.php', 'Set up Ad Hoc Sale Event', 'Set up Ad Hoc Sale Event','administrator', 'sale-admin', 'fe_sale_admin');
    add_submenu_page( 'fourth-element-scripts/fe.php', 'Set up Summer/Winter Sale Event', 'Set up Summer/Winter Sale Event','administrator', 'fe-key-sale-event', 'fe_key_sale_event');
    add_submenu_page( 'fourth-element-scripts/fe.php', 'Swap Homepage', 'Swap Homepage','administrator', 'swap-homepage', 'fe_swap_homepage_frm'); 
    add_submenu_page( 'fourth-element-scripts/fe.php', 'Switch Off/On Express Shipping', 'Switch Off/On Express Shipping','administrator', 'switch-express-off', 'fe_switch_express_off_frm');     
    add_submenu_page( 'fourth-element-scripts/fe.php', 'Update Dealer Map', 'Update Dealer Map','administrator', 'update-dealer-map', 'fe_manage_locations');
    add_submenu_page( 'fourth-element-scripts/fe.php', 'Update RRPs', 'Update RRPs','administrator', 'update-rrp', 'fe_update_rrp');
    add_submenu_page( 'fourth-element-scripts/fe.php', 'Bulk Override Stock', 'Bulk Override Stock','administrator', 'bulk-override-stock', 'fe_bulk_override_stock');
    add_submenu_page( 'fourth-element-scripts/fe.php', 'Clear Wishlists', 'Clear Wishlists','administrator', 'clear-wishlists', 'fe_clear_wishlists');
  
    //MOTHBALLED
    //add_submenu_page( 'fourth-element-scripts/fe.php', 'Add Atum Inv', 'Add Atum Inv','administrator', 'setup-atum-inventories', 'fe_setup_atum_inventories');        
    //add_submenu_page( 'fourth-element-scripts/fe.php', 'Add Product to Cart', 'Add Product to Cart','administrator', 'add-product-to-cart', 'fe_prod_to_cart');
    //add_submenu_page( 'fourth-element-scripts/fe.php', 'Load Product Metas', 'Load Product Metas','administrator', 'load-product-meta', 'fe_load_meta_data');  
    //add_submenu_page( 'fourth-element-scripts/fe.php', 'Load Product Tabs', 'Load Product Tabs','administrator', 'load-product-tabs', 'fe_load_tab_data');
    //add_submenu_page( 'fourth-element-scripts/fe.php', 'Upload UWBPOTY Photo', 'Upload UWBPOTY Photo','administrator', 'upload-uwbpoty', 'fe_upload_uwbpoty');  
    //add_submenu_page( 'fourth-element-scripts/fe.php', 'Export Survey Results', 'Export Survey Results','administrator', 'export-survey-results', 'fe_export_survey');
    //add_submenu_page( 'fourth-element-scripts/fe.php', 'View Wishlist Entries', 'View Wishlist Entries','administrator', 'view-wishlist-entries', 'fe_view_wishlist_entries');
    //add_submenu_page( 'fourth-element-scripts/fe.php', 'Render Orders Map', 'Render Orders Map','administrator', 'render-orders-map', 'fe_render_map');  

}
add_action('admin_menu', 'fe_add_menu');


function fe_prod_to_cart() {
// we need to check if there's a promo already running, if so we can show the option to remove it otherwise show the form.
$chkPromo = get_option('fe_cart_promo');
$plugindir = plugins_url('includes/fe-close-promo.php', __FILE__);
    
    echo "<h1>Add a Product to Cart Promo</h1><p>This will allow anyone to put a product into a qualifying cart for a promo, etc</p><p><strong>Instructions:</strong><br/><br/>1) Find and enter the product ID number into field one<br/><br/>2) Select the categories, which qualify for the promo. So if a product is selected from the category, this product is added to the cart with it.</p>";
    
    if ( $chkPromo == '0' ) {
        echo do_shortcode('[gravityform id="20" title="false" description="false" ajax="true"]');
    } else {
        echo '<p class="close-promo">There is currently a promotion running. Do you wish to <a id="close-cart-promo">close this one</a>?</p><p class="fe-message"></p>
        <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery("#close-cart-promo").on("click", function(e) {
                    e.preventDefault();
                    var dataString = "promo=end";
                    jQuery.ajax({
                        type: "GET",
                        url: "'.$plugindir.'",
                        data: dataString,
                        dataType: "html",
                        success: function(promo){
                            console.log("Promo Removed: "+promo);
                            jQuery(".close-promo").hide();
                            jQuery(".fe-message").show();
                            jQuery(".fe-message").html("<strong>Promo has been deleted. Refresh page to start another one.</strong>");
                        }
                    });
                });
            });
        </script>';
    }
}

// 1.0 override form multiselect
//=========================================
// Populate the multiselect form field with all the categories
//that can be selected as qualifying for the promo.

function fe_populate_woo_categories( $form ) {
    
 global $wpdb;
    //only populating drop down for form id 20
    if ( $form['id'] != 20 ) {
       return $form;
    }
 
    //Reading posts for "Business" category;
    $posts = get_posts( 'category=' . get_cat_ID( 'Business' ) );
 
    //Creating item array.
    $items = array();
 
     $args = array(
        'taxonomy'   => 'product_cat',
        'orderby'    => 'name',
        'order'      => 'ASC',
        'hide_empty' => true,
    );

    $product_categories = get_terms($args);
    asort($product_categories);

    //Adding post titles to the items array
    foreach ( $product_categories as $cat ) {
        
        $parent = $cat->parent;
        $result = $wpdb->get_col("SELECT name FROM wp_terms WHERE term_id = ".$parent."");
        if ($result[0] == "") {
            $parentName = "No Parent";
        } else {
            $parentName = $result[0];
        }
        $items[] = array( 'value' => $cat->term_id, 'text' => $parentName." -> ".$cat->name );
    }
 
    //Adding items to field id 8. Replace 8 with your actual field id. You can get the field id by looking at the input name in the markup.
    foreach ( $form['fields'] as &$field ) {
        if ( $field->id == 2 ) {
            $field->choices = $items;
        }
    }
 
    return $form;
}
add_filter( 'gform_pre_render', 'fe_populate_woo_categories' );

// 1.0.1 deal with form submission
//=========================================
// We'll set up three option entries
// 1) flag for the set promo
// 2) the product id
// 3) comma separated category ids

function fe_set_up_promo($entry, $form){
global $wpdb;
 
    $product = $entry['1']; 
    $cats = strtr($entry['2'], array('['=>'','"'=>'',']'=>''));
  
    if ( !$product ) {
        return;
    }
    
    update_option('fe_cart_promo',1,'yes');
    update_option('fe_cart_promo_product',$product,'yes');
    update_option('fe_cart_promo_cats',$cats,'yes');    
}
add_action("gform_after_submission_20", "fe_set_up_promo", 10, 2);


// 1.1 Manage FE Sale
//=========================================

function fe_sale_admin() {

    global $wpdb;
    echo do_shortcode('[gravityform id="21" title="false" description="false" ajax="true"]');

    //get the category ids
    $getTerms = $wpdb->get_results("SELECT t.term_id, t.name, t.slug FROM wp_term_taxonomy tt inner join wp_terms t on t.term_id = tt.term_id where (tt.parent = 407 OR tt.parent = 569 OR tt.parent = 568) ORDER BY t.name ASC");

    echo "<p>Term IDs for use in Columns C-G</p>";
    echo "KEY CATEGORIES<br/><br/>";
    echo "Cat ID: 407 / Name: Clearance</br>";
    echo "Cat ID: 568 / Name: Men's Clearance</br>";
    echo "Cat ID: 569 / Name: Women's Clearance</br><br/>";
    echo "CHILD CATEGORIES<br/><br/>";
    foreach ( $getTerms as $term) {
        echo "Cat ID: " . $term->term_id . " / Name: " . $term->name . " / Slug: " . $term->slug."</br>";
    }
}

// 1.1.1 Process Sale CSV file
//=========================================
function fe_manage_sale($entry, $form){
global $wpdb;
 
    $csvFile = $entry['2']; 
    $saleStatus = $entry['3'];
  
    if ( !$csvFile ) {
        return;
    }
    
    //process the CSV file, need to work with the saleStatus flag to ensure we're loading or unloading the sale data    
    $csvFile = str_replace(SITE_URL,SITE_WEB_ROOT_PATH,$csvFile);

    ini_set('auto_detect_line_endings',TRUE);
    if (($handle = fopen($csvFile, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",", ";" )) !== FALSE) {
            $num = count($data);
           
                if ( strpos($data[0], 'Product') === false ) { // Get the product id from the sku
                    $getProdId = $wpdb->get_row("SELECT post_id FROM wp_postmeta WHERE meta_key = '_sku' and meta_value = '".$data[0]."'");
                    $productId = $getProdId->post_id;  
                }
                
                if ( strpos($data[1], 'Price') === false ) {                    
                    $price = $data[1];                    
                    $getVariations = $wpdb->get_results("SELECT ID FROM wp_posts WHERE post_parent = ".$productId." AND post_type ='product_variation' ");
                    $totRec = $wpdb->num_rows;

                    if ( $totRec > 0 ) {
                            //get the product variations, we'll set the sale price to those as well as the parent product (incase there are no variations)
                            if ( $saleStatus == 'setsale') {
                                
                                update_post_meta($productId,'_sale_price',$price);
                                
                                if ( strpos($data[7], 'Currencies') === false && $data[7] <> '') {
                                       $currenc =strtr($data[7], array('|'=>'"','/'=>','));
                                }
                                                               
                               
                                foreach ( $getVariations as $vary ) {
                                    update_post_meta($vary->ID,'_sale_price',$price);
                                    update_post_meta($vary->ID,'variable_sale_currency_prices',$currenc);
                                }


                            } else if ( $saleStatus == 'unsetsale') {
                                
                                update_post_meta($productId,'_sale_price','');
                                
                                foreach ( $getVariations as $vary ) {
                                    update_post_meta($vary->ID,'_sale_price','');
                                    update_post_meta($vary->ID,'variable_sale_currency_prices','');
                                }
                                
                                $regPrice = get_post_meta($productId,'_regular_price',true);
                                update_post_meta($productId,'_price',$regPrice);
                                
                            }

                    } else { // probably a simple product, no variations
                    
                            if ( $saleStatus == 'setsale') {
                                 if ( strpos($data[7], 'Currencies') === false && $data[7] <> '') {
                                       $currenc =strtr($data[7], array('|'=>'"','/'=>','));
                                }
                                update_post_meta($productId,'_sale_price',$price);
                                update_post_meta($productId,'_sale_currency_prices',$currenc);
                            } else {
                                update_post_meta($productId,'_sale_price','');
                                update_post_meta($productId,'_sale_currency_prices','');
                            }

                    }

                        //set or unset the categories based on what's being run
                        if ( strpos($data[2], 'Category') === false && $data[2] <> '' ) {
                            $categorya = $data[2];
                            if ( $saleStatus == 'setsale') {
                                wp_set_object_terms($productId,(int)$categorya,'product_cat',true);
                            } else if ( $saleStatus == 'unsetsale') {
                                wp_remove_object_terms($productId,(int)$categorya,'product_cat');
                            }       
                        }

                        if ( strpos($data[3], 'Category') === false  && $data[3] <> '') {
                            $categoryb = $data[3];
                            if ( $saleStatus == 'setsale') {
                                wp_set_object_terms($productId,(int)$categoryb,'product_cat',true);
                            } else if ( $saleStatus == 'unsetsale') {
                                wp_remove_object_terms($productId,(int)$categoryb,'product_cat');
                            }                   
                        }

                        if ( strpos($data[4], 'Category') === false  && $data[4] <> '') {
                            $categoryc = $data[4];
                            if ( $saleStatus == 'setsale') {                        
                                wp_set_object_terms($productId,(int)$categoryc,'product_cat',true);
                            } else if ( $saleStatus == 'unsetsale') {
                                wp_remove_object_terms($productId,(int)$categoryc,'product_cat');
                            }                   
                        }

                        if ( strpos($data[5], 'Category') === false  && $data[5] <> '') {
                            $categoryd = $data[5];
                            if ( $saleStatus == 'setsale') {
                                wp_set_object_terms($productId,(int)$categoryd,'product_cat',true);
                            } else if ( $saleStatus == 'unsetsale') {
                                wp_remove_object_terms($productId,(int)$categoryd,'product_cat');
                            }
                        }

                        if ( strpos($data[6], 'Category') === false && $data[6] <> '') {
                            $categorye = $data[6];
                            if ( $saleStatus == 'setsale') {
                                wp_set_object_terms($productId,(int)$categorye,'product_cat',true);
                            } else if ( $saleStatus == 'unsetsale') {
                                wp_remove_object_terms($productId,(int)$categorye,'product_cat');
                            }                   
                        }

                }
                
        wc_delete_product_transients($productId);
        }
        fclose($handle);
    }
    ini_set('auto_detect_line_endings',FALSE);
  
}
add_action("gform_after_submission_21", "fe_manage_sale", 10, 2);


// 1.2 render orders map
//=========================================

function fe_render_map() {
?>

<h1>View Orders and Products by location</h1>
<p>This filter will allow you to see what's being ordered around the world in the form of a map.</p>
<p><strong>Please note, this script needs to look up the latitude and longitude of each address, so it's time consuming.</strong> To use it, select the to and from dates, the wider the range, the longer it will take to render the map, so keep searches down to a 2-3 days range if possible.</p>
<p>The default map shows the last two days of orders...</p>
<form method="get">
    <input type="hidden" name="page" value="render-orders-map" />
    From: <input name="from" value="<?php echo $_GET['from'];?>" placeholder="dd-mm-yyyy" /> To: <input name="to" value="<?php echo $_GET['to'];?>" placeholder="dd-mm-yyyy" /> <input type="submit" value="LOOKUP ORDERS" />
</form>
<?php
global $wpdb;
$showPins = '';

if ( $_GET['to'] == "" ) {
    $today = date("Y-m-d");
} else {
    $today = date("Y-m-d",strtotime(str_replace("/","-",$_GET['to'])));
}

if ( $_GET['from'] == "" ) {
    $mth = date("Y-m-d",strtotime("-2 days"));
} else {
  $mth = date("Y-m-d",strtotime(str_replace("/","-",$_GET['from'])));  
}

$sqls = $wpdb->get_results("SELECT * FROM {$wpdb->posts} WHERE post_type = 'shop_order' AND post_status = 'wc-completed' AND (post_date BETWEEN '".$mth."' AND '".$today."')");
$cnt = $wpdb->num_rows;
$i=0;
$ordArr = array();
foreach ($sqls as $result) {
    $ordArr[$i]['id'] = $result->ID;    
    $getCity = get_post_meta( $result->ID, '_billing_city', true );
    $ordArr[$i]['city'] = ucWords($getCity);
    $getCountry = get_post_meta( $result->ID, '_billing_country', true );
    $ordArr[$i]['country'] = $getCountry;
    $getZip = get_post_meta( $result->ID, '_billing_postcode', true );
    if ($getZip == '') {
        $getZip = "n/a";
    }
    $ordArr[$i]['zip'] = $getZip;
    $dt = date("d-m-Y",strtotime($result->post_date));
    

    $latln = fe_get_lat_lon($getCity ." ". $getZip);

    $ordArr[$i]['latitude'] = $latln['lat'];
    $ordArr[$i]['longitude'] = $latln['lng'];

    $getOrderItems = $wpdb->get_results("SELECT * FROM wp_woocommerce_order_items WHERE order_item_type = 'line_item' AND order_id = ".$result->ID."");
        
    $items = "";

    foreach ( $getOrderItems as $orditem ) { 
        $items .= $orditem->order_item_name.",<br/>";
    }

    $ordArr[$i]['items'] = $items;

    $i++;
}

foreach ($ordArr as $ord) {


    $showPins .= "{'date':'".$dt."','city':'".str_replace("'","",$ord['city'])."','latitude':'".$ord['latitude']."','longitude':'".$ord['longitude']."','items':'".rtrim(str_replace("'","",$ord['items']), ",<br/>")."'},";
//$showPins = "";
}

if (!empty($ordArr)) {
?>
 <div id="order-map" class="mt30" style="margin-top:30px;height:700px; width:95%;"></div>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBvWU6CKGEvfR4xHV_6wL39csSZLohYmuY&callback=initialize"></script>
    <script type="text/javascript" src="https://googlemaps.github.io/js-marker-clusterer/src/markerclusterer.js"></script>
    <script>
    function initialize() {
        var center = new google.maps.LatLng(50.100027, -5.2808722);
 
        var map = new google.maps.Map(document.getElementById('order-map'), {
          zoom: 4,
          center: center,
          mapTypeId: google.maps.MapTypeId.ROADMAP
        });
        <?php $showPins = rtrim($showPins, ","); //remove the trailing , to stop JS error ?>
        var data = { "count": 10785236, "orders": [<?php echo $showPins;?>]}
        //var data = { "count": 10785236, "orders": [{'city':'Memphis','postcode':'38104','latitude':'35.1323787','longitude':'-90.004663'},{'city':'Gouda','postcode':'2806CL','latitude':'52.0132665','longitude':'4.7215756'}]
 
        var markers = [];
        for (var i = 0; i < <?php echo $cnt;?>; i++) {
            var a = 360.0 / markers.length;
            var dataPhoto = data.orders[i];
            var latLng = new google.maps.LatLng(dataPhoto.latitude,dataPhoto.longitude);
            var items = dataPhoto.items;
            var lat  = dataPhoto.latitude + -.00100 * Math.cos((+a*i) / 180 * Math.PI);
            var long = dataPhoto.longitude + -.00100 * Math.cos((+a*i) / 180 * Math.PI);
            var dte = dataPhoto.date;
            var town = dataPhoto.city;
            var marker = new google.maps.Marker({
                icon: 'https://www.fourthelement.com/wp-content/uploads/2017/11/dealer-20px.png',
                map: map, title: name , animation: google.maps.Animation.DROP,
                position: latLng
            });
            markers.push(marker);
 
            var content = '<strong>Date: '+dte+'<br/>' + items + '<br/>' + town + '</strong>'
 
            var infowindow = new google.maps.InfoWindow()
 
            google.maps.event.addListener(marker,'click', (function(marker,content,infowindow){
                return function() {
                    infowindow.setContent(content);
                    infowindow.open(map,marker);
                };
                })(marker,content,infowindow));
            }
            //var markerCluster = new MarkerClusterer(map, markers, {imagePath: 'https://www.fourthelement.com/wp-content/themes/fourth-element-child/images/m', maxZoom: 14});
    }
        google.maps.event.addDomListener(window, 'load', initialize);
    </script>
      
    <?php } 
}

function fe_get_lat_lon($address){

    $lat = $lon = '';

    $address = str_replace(" ", "", $address);
    $address = urlencode($address);

    //$url = "https://maps.google.com/maps/api/geocode/json?address=$address&sensor=false&key=AIzaSyCBKABwzhIktxOg2ymMpvUmdBWaYygloTI";
    //$url = "https://maps.google.com/maps/api/geocode/json?address=$address&sensor=false&key=AIzaSyBvWU6CKGEvfR4xHV_6wL39csSZLohYmuY";
    $url = "https://api.opencagedata.com/geocode/v1/json?q=".$address."&key=".OCMAPKEY;

    if( in_array  ('curl', get_loaded_extensions() ) ){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);
        $response_a = json_decode($response);


      if (!empty($response_a) && !empty($response_a->results)) {
        //$lat = $response_a->results[0]->geometry->location->lat; // google
        //$lon = $response_a->results[0]->geometry->location->lng; // google
        $lat = $response_a->results[0]->geometry->lat;
        $lon = $response_a->results[0]->geometry->lng;

          }
         }

   return array(
      'lat' => $lat,
      'lng' => $lon,
   );
}

// 1.3 Set up Menu item for Wishlist
// =============================================================================
function register_wishlist_item() {

    add_menu_page ( 
        __('Custom Post Types', 'textdomain'),
        'My Wishlists',
        'manage_options',
        '/wp-admin/edit.php?post_type=my-wishlists',
        '',
        '',
        2

    );
}
add_action('admin_menu', 'register_wishlist_item');

// 1.3.1 Create the Custom Post Type for Wishlists
// =============================================================================
function post_wishlists() {
    $labels = array(
        'name'                  => _x('My Wishlists', 'post type general name'),
        'singular_name'         => _x('My Wishlist', 'post type singular name'),
        'add_new'               => _x('Add New','my wishlist'),
        'add_new_item'          => __('Add My Wishlist'),
        'edit_item'             => __('Edit My Wishlist'),
        'new_item'              => __('New My Wishlist'),
        'all_items'             => __('My Wishlists'),
        'view_item'             => __('View My Wishlists'),
        'search_items'          => __('Search My Wishlists'),
        'not_found'             => __('No Wishlists found'),      
        'not_found_in_trash'    => __('No Wishlists found in the Trash'),
        'parent_item_colon'     => '',
        'menu_name'             => 'My Wishlists'
    );

    $args = array(
        'labels'                => $labels,
        'description'           => 'Holds all Wishlists for the Wishlist option on the website',
        'public'                => true,
        'publicly_queryable'    => true,
        'menu_position'         => 9,
        'show_in_menu'          => '/wp-admin/edit.php?post_type=my-wishlists',
        'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'comments'),
        'has_archive'           => true,
        'taxonomies'            => array('post_tag','category'),        
    );
    
    register_post_type( 'my-wishlist', $args );

}
add_action('init','post_wishlists');

// 1.4 Export orders to Sage
// =============================================================================
function fe_sage_admin() {
    
    echo '<h1 class="h2">Export Orders to SAGE</h1><p>Use the form below to set your then and now dates. This will provide a downloadable CSV file based on midnight timings (at present) between the two dates.</p>';

    echo '<form id="sage-export">
     <label for "fromdate" style="width:100px;display:inline-block;">From:</label> <input type="text" name="fromdt" placeholder="DD-MM-YYYY" /><br/>
    <label for "todate" style="width:100px;display:inline-block;">To:</label> <input type="text" name="todt" placeholder="DD-MM-YYYY" /><br/>
        <input type="submit" value="EXPORT ORDERS" style="margin-top:20px;" />
    </form>';
    echo '<div style="clear:both;margin-top:20px;display:none;" class="download"></div>';
    
        echo '
        <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery(\'form#sage-export\').submit(function(e){ 
                    e.preventDefault();
                    var fromdt = jQuery(\'input[name=fromdt]\').val();
                    var todt = jQuery(\'input[name=todt]\').val();
                    var dataString = \'thendt=\'+fromdt+\'&nowdt=\'+todt;
                    

                    jQuery.ajax({
                        type: "GET",
                        url: window.location.protocol + "//" + window.location.hostname + "/wp-content/themes/fourth-element-child/sage.php",
                        data: dataString,
                        dataType: "html",
                        beforeSend: function(){
                            jQuery(".download").hide();
                        },
                        success: function(result){                             
                            jQuery(".download").show();
                            jQuery(".download").html(result);
                        }
                    });
                });
            });
        </script>';
    
}

// 1.5 clear Wishlists
//=========================================

function fe_clear_wishlists() {
    $dt = strtotime("now");
?>

<h1>Remove old wishlists</h1>
<p>This form will allow you to remove wishlists that are older than 30 days.</p>
<form method="get" id="clearwishlists">
    <input type="hidden" name="currdt" value="<?php echo $dt;?>" />
    <input type="submit" value="CLEAR WISHLISTS" />
</form>
    
    <div style="clear:both;margin-top:20px;display:none;" class="listresult"></div>

        <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery('form#clearwishlists').submit(function(e){ 
                    e.preventDefault();
                    var currdt = jQuery('input[name=currdt]').val();
                    var dataString = 'currdt='+currdt;
                    

                    jQuery.ajax({
                        type: "GET",
                        url: window.location.protocol + "//" + window.location.hostname + "/wp-content/plugins/fourth-element-scripts/includes/clear-wishlists.php",
                        data: dataString,
                        dataType: "html",
                        beforeSend: function(){
                            jQuery(".listresult").hide();
                        },
                        success: function(result){                             
                            jQuery(".listresult").show();
                            jQuery(".listresult").html(result+' Wishlists Have Been Deleted');
                        }
                    });
                });
            });
        </script>
<?php
}

// 1.6 View Wishlist Competition Entries
//=========================================

function fe_view_wishlist_entries() {
global $wpdb;
$frmId = 25;
$metaKey = 1;
$wish = array();

    $getEntries = $wpdb->get_results("SELECT distinct gfm.meta_value FROM wp_gf_entry gfe JOIN wp_gf_entry_meta gfm ON gfe.id = gfm.entry_id WHERE gfe.form_id = 8 and gfm.meta_key = 1 ORDER BY gfm.meta_value ASC");
    echo "<h1>Wish List Entries</h1>";

    foreach ( $getEntries as $entry ) {
        $wish[] = $entry->meta_value;
    }


    foreach ( $wish as $akey=>$avalue) {
       
    
        $getAuthors = $wpdb->get_row("SELECT post_author FROM wp_posts WHERE ID = ".$avalue."");
        $user_info = get_userdata($getAuthors->post_author);
        $email = $user_info->user_email;

       
        if ( get_user_meta($getAuthors->post_author, 'first_name',true) ) {
            echo "Page ID: <a href=".get_the_permalink($avalue)." target='_blank'>".$avalue."</a> / Name: " . get_user_meta($getAuthors->post_author, 'first_name',true) . " " .get_user_meta($getAuthors->post_author, 'last_name',true)." / Email: ".$email."<br/>";
        }
    }
    
    
    echo "<h1>Wish Lists created but not entered</h1>";
    
    $getWishLists = $wpdb->get_results("SELECT user_id,meta_value FROM wp_usermeta WHERE meta_key = 'wishlist_post'");
    
    foreach ($getWishLists as $wl) {
        $user_info = get_userdata($wl->user_id);
        $email = $user_info->user_email;

       
        if ( !in_array($wl->meta_value,$wish) ) {
            echo "Page ID: <a href=".get_the_permalink($wl->meta_value)." target='_blank'>".$wl->meta_value."</a> / Name: " . get_user_meta($wl->user_id, 'first_name',true) . " " .get_user_meta($wl->user_id, 'last_name',true)." / Email: ".$email."<br/>";
        }
    }
    
    
}

// 1.7 Set Checkout Message
//=========================================

function fe_set_checkout_message() {
global $wpdb;
?>

<h1>Set Checkout Message</h1>
<p>This form will allow you to add a message to appear above the checkout form during a particular period</p>
<form method="get" id="setchkmess">
    Enter Message:  <input type="text" name="chkmessage" style="width: 80%" /><br/><br/>
    Enter Start Date: <input type="text" name="chkdts" placeholder="dd/mm/yyyy" /><br/><br/>
    Enter End Date: <input type="text" name="chkdte" placeholder="dd/mm/yyyy" /><br/><br/>
    <input type="submit" value="SET MESSAGE" />
</form>
   
    <div style="clear:both;margin-top:20px;display:none;" class="listresult"></div>

        <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery('form#setchkmess').submit(function(e){ 
                    e.preventDefault();
                    var mess = jQuery('input[name=chkmessage]').val();
                    var dts = jQuery('input[name=chkdts]').val();
                    var dte = jQuery('input[name=chkdte]').val();
                    var dataString = 'mess='+mess+'&dts='+dts+'&dte='+dte;
                    alert(dataString);
                    

                    jQuery.ajax({
                        type: "GET",
                        url: window.location.protocol + "//" + window.location.hostname + "/wp-content/plugins/fourth-element-scripts/includes/set-checkout-message.php",
                        data: dataString,
                        dataType: "html",
                        beforeSend: function(){
                            jQuery(".listresult").hide();
                        },
                        success: function(result){                             
                            jQuery(".listresult").show();
                            jQuery(".listresult").html('Message has been set');
                        }
                    });
                });
            });
        </script>
        
    <br/><br/>
    <p>Current message stored:  <?php echo get_option('fechkmessage');?></p>
    <p>Started on  <?php echo date("d/m/y",get_option('fechkdts'));?> and ends/ended on  <?php echo date("d/m/y",get_option('fechkdte'));?></p>
    
<?php

}


// 1.8 Export Survey Results
//=========================================

function fe_export_survey() {
global $wpdb;

//get all survey page one entries
$entArr = array();
$survey = '';
$dt = date('dmY');

//CSV Header
$survey .= "Email,First Name,Last Name,Gender,Age,Location,Type of Diving,Diving (Other),FE Products Owned,FE Products Want,What Holds them Back,What Holds them Back (Other),Where Normally Purchase,Where Normally Purchase (Other),Environmental Credentials,Other Brands Used,Other Brands Used (Other),Anything Else\n";

@chmod(SITE_WEB_ROOT_PATH.'wp-content/uploads/survey/', 0777);
$csv_file = SITE_WEB_ROOT_PATH.'/wp-content/uploads/survey/fe-survey-responses-'.$dt.'.csv';
$csv_handler = fopen($csv_file,'w') or die("Unable to open file!");  

$getPgTwo = $wpdb->get_results("SELECT id FROM `wp_gf_entry` WHERE form_id = 25 ORDER BY date_created ASC");

    foreach ($getPgTwo as $entry) {
        
        $entry->entry_id = $entry->id;
        
        $frmOne = gform_get_meta( $entry->id, '27' );
        $Email = gform_get_meta( $frmOne, '1' );
        
        
            $fName =  rawurldecode(gform_get_meta( $entry->id, '1.3' ));
            $lName =  rawurldecode(gform_get_meta( $entry->id, '1.6' ));
            
            $gender =  gform_get_meta( $entry->id, '2' );
            $age = gform_get_meta( $entry->id, '3' );
            $location = gform_get_meta( $entry->id, '4' );
            
            $type = gform_get_meta( $entry->id, '22' );

            if ( preg_match('/\","\b/', $type) ) {
                $type = strtr(gform_get_meta( $entry->id, '22' ), array('["'=>'"','"]'=>'"','","'=>','));
            } else {
                $type = strtr(gform_get_meta( $entry->id, '22' ), array('["'=>'','"]'=>''));
            }
            
            $typeOther = '';
            if (preg_match('/\Other\b/', $type) ) {            
                $typeOther = '"'.gform_get_meta( $entry->id, '6' ).'"';
            }
            
            $own = gform_get_meta( $entry->id, '7' );
            if ( preg_match('/\","\b/', $own )) {
                $own = strtr(gform_get_meta( $entry->id, '7' ), array('["'=>'"','"]'=>'"','","'=>','));
            } else {
                $own = strtr(gform_get_meta( $entry->id, '7' ), array('["'=>'','"]'=>''));
            }
            
            $want = gform_get_meta( $entry->id, '8' );            
            if ( preg_match('/\","\b/', $want )) {
                $want = strtr(gform_get_meta( $entry->id, '8' ), array('["'=>'"','"]'=>'"','","'=>','));
            } else {
                $want = strtr(gform_get_meta( $entry->id, '8' ), array('["'=>'','"]'=>''));
            }
            
            $holds = gform_get_meta( $entry->id, '23' );
            if (preg_match('/\","\b/', $holds) ) {
                $holds = strtr(gform_get_meta( $entry->id, '23' ), array('["'=>'"','"]'=>'"','","'=>','));
            } else {
                $holds = strtr(gform_get_meta( $entry->id, '23' ), array('["'=>'','"]'=>''));
            }
            
            $holdsOther = '';
            if (preg_match('/\Other\b/', $holds) ) {
                $holdsOther = gform_get_meta( $entry->id, '11' );
            }
            
            $where = gform_get_meta( $entry->id, '24' );
            if (preg_match('/\","\b/', $where)) {
                $where = strtr(gform_get_meta( $entry->id, '24' ), array('["'=>'"','"]'=>'"','","'=>','));
            } else {
                $where = strtr(gform_get_meta( $entry->id, '24' ), array('["'=>'','"]'=>''));
            }
            
            $whereOther = '';
            if ( preg_match('/\Other\b/', $where) ) { 
                $whereOther = '"'.strtr(gform_get_meta( $entry->id, '13' ), array(', '=>',')).'"';
            }
            
            $environ = gform_get_meta( $entry->id, '14' );
            
            $brands = gform_get_meta( $entry->id, '15' );
            if (preg_match('/\","\b/', $brands) ) {
                $brands = strtr(gform_get_meta( $entry->id, '15' ), array('["'=>'"','"]'=>'"','","'=>','));
            } else {
                $brands = strtr(gform_get_meta( $entry->id, '15' ), array('["'=>'','"]'=>''));
            }
            
            $brandsOther = '';
            if ( preg_match('/\Other\b/', $brands) ) {            
                $brandsOther = '"'.strtr(gform_get_meta( $entry->id, '25' ), array(', '=>',')).'"';
            }
            
            $else = gform_get_meta( $entry->id, '16' );
            
            if ( strpos($else, ',') !== false ) {            
                $else = '"'.str_replace("’","'",gform_get_meta( $entry->id, '16' )).'"';
            }
            
            
            if ( $Email <> '' ) {
                $survey .= $Email.','.$fName.','.$lName.','.$gender.','.$age.','.$location.','.$type.','.$typeOther.','.$own.','.$want.','.$holds.','.$holdsOther.','.$where.','.$whereOther.','.$environ.','.$brands.','.$brandsOther.','.$else."\n";
            }
            
            
    }

    if (is_resource($csv_handler)) {
		fwrite ($csv_handler,$survey);
    	fclose ($csv_handler);
    	@chmod(SITE_WEB_ROOT_PATH.'wp-content/uploads/survey/', 0755);
	}    
?>

<h1>Export Survey Results</h1>
<p>Click <a href="/wp-content/uploads/survey/fe-survey-responses-<?php echo $dt;?>.csv">here</a> to download all the survey results to date.</p>
<?php
}

// 1.9 Load Product Tab Data from CSV
//=========================================

function fe_load_tab_data() {
global $wpdb;
    echo do_shortcode('[gravityform id="26" title="false" description="false" ajax="true"]');
}

// 1.9.1 Process Process Tab Data
//=========================================
//MOTHBALLED: Due to different process for populating product accordions
/*function fe_process_tab_data($entry, $form) {
global $wpdb;    
    $csvFile = $entry['1']; 
  
    if ( !$csvFile ) {
        return;
    }
    
    error_log($csvFile);
    $csvFile = str_replace(SITE_URL,SITE_WEB_ROOT_PATH,$csvFile);

    ini_set('auto_detect_line_endings',TRUE);
    if (($handle = fopen($csvFile, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, "|")) !== FALSE) {
            
            $features = $fabric = $fit = $size = $chart = $about = ''; 
            $num = count($data);
            
            $tabArr = array(); // create an empty array to manage building the tab array

            $tabArr['core_tab_description'] = array('position'=>0,'type'=>'core','id'=>'description','title'=>'Description','heading'=>'Product Description');

                if ( strpos($data[0], 'HSKU') === false ) { // Get the product id from the sku
                    $getProdId = $wpdb->get_row("SELECT post_id FROM wp_postmeta WHERE meta_key = '_sku' and meta_value = '".$data[0]."'");
                    $productId = $getProdId->post_id;  
                }
                
                if ( strpos($data[1], 'HFEATURES') === false ) { // Get the features tab data
                    $features = $data[1];
                    if ( $features <> '' ) {
                        $featuresTab = array('post_title' => 'Features', 'post_content' => $features, 'post_status' => 'publish', 'post_type' => 'wc_product_tab', 'post_author' => 149);
                        $post_id = wp_insert_post( $featuresTab, $wp_error );
                        $tabArr['product_tab_'.$post_id] = array('position'=>1,'type'=>'product','id'=>$post_id,'name'=>'features');
                    }

                }
                
                if ( strpos($data[2], 'HFABRIC') === false ) { // Get the fabric tab data
                    $fabric = $data[2];
                    if ( $fabric <> '' ) {
                        $fabricTab = array('post_title' => 'Fabric & Care', 'post_content' => $fabric, 'post_status' => 'publish', 'post_type' => 'wc_product_tab', 'post_author' => 149);
                        $post_id = wp_insert_post( $fabricTab, $wp_error );
                        $tabArr['product_tab_'.$post_id] = array('position'=>2,'type'=>'product','id'=>$post_id,'name'=>'fabric-and-care');
                    }
                }
                
                if ( strpos($data[3], 'HFIT') === false ) { // Get the fit tab data
                    $fit = $data[3];
                    if ( $fit <> '' ) {
                        $fitTab = array('post_title' => 'Fit', 'post_content' => $fit, 'post_status' => 'publish', 'post_type' => 'wc_product_tab', 'post_author' => 149);
                        $post_id = wp_insert_post( $fitTab, $wp_error );
                        $tabArr['product_tab_'.$post_id] = array('position'=>3,'type'=>'product','id'=>$post_id,'name'=>'fit');
                    }
                }
                
                if ( strpos($data[4], 'HSIZE') === false ) { // Get the product size tab data
                    $size = $data[4];
                    if ( $size <> '' ){
                        $sizeTab = array('post_title' => 'Size', 'post_content' => $size, 'post_status' => 'publish', 'post_type' => 'wc_product_tab', 'post_author' => 149);
                        $post_id = wp_insert_post( $sizeTab, $wp_error );
                        $tabArr['product_tab_'.$post_id] = array('position'=>4,'type'=>'product','id'=>$post_id,'name'=>'size');
                    }
                }
                
                if ( strpos($data[5], 'HCHART') === false ) { // Get the clothing chart tab data
                    $chart = $data[5];
                    if ( $chart <> '' ) {
                        switch (strtoupper($chart)) {
                            case 'MENS':
                                $chart = 162;
                                $title = 'Mens Size Chart';
                                $name = 'mens-size-chart';
                                break;
                            case 'WOMENS':
                                $chart = 360;
                                $title = 'Womens Size Chart';
                                $name = 'womens-size-chart';
                                break;
                            case 'KIDS':
                                $chart = 17428;
                                $title = 'Kids Size Chart';
                                $name = 'kids-size-chart';
                                break;    
                            case 'HATS':
                                $chart = 361;
                                $title = 'Hat Size Chart';
                                $name = 'hat-size-chart';
                                break;
                            case 'FOOTWEAR':
                                $chart = '';
                                break;
                        }

                    $tabArr['global_tab_'.$chart] = array('position'=>5,'type'=>'global','id'=>$chart,'name'=>$name);
                    }
                }                
            
                if ( strpos($data[6], 'HABOUT') === false ) { // Get the about the collection tab data
                    $about = $data[6];
                    if ( $about <> '' ){
                        $aboutTab = array('post_title' => 'About the collection', 'post_content' => $about, 'post_status' => 'publish', 'post_type' => 'wc_product_tab', 'post_author' => 149);
                        $post_id = wp_insert_post( $aboutTab, $wp_error );
                        $tabArr['product_tab_'.$post_id] = array('position'=>1,'type'=>'product','id'=>$post_id,'name'=>'about');
                    }
                }    

                if ( strpos($data[7], 'HORIGIN') === false ) { // Get the origin tab data
                    $origin = $data[7];
                    if ( $origin <> '' ){
                        $originTab = array('post_title' => 'Origin', 'post_content' => $origin, 'post_status' => 'publish', 'post_type' => 'wc_product_tab', 'post_author' => 149);
                        $post_id = wp_insert_post( $originTab, $wp_error );
                        $tabArr['product_tab_'.$post_id] = array('position'=>1,'type'=>'product','id'=>$post_id,'name'=>'origin');
                    }
                } 

            update_post_meta($productId,'_product_tabs',$tabArr);
            update_post_meta($productId,'_override_tab_layout','yes');
            
        wc_delete_product_transients($productId);
        }
        fclose($handle);
    }
    ini_set('auto_detect_line_endings',FALSE);
}
add_action("gform_after_submission_26", "fe_process_tab_data", 10, 2);
*/

//2.0 Export new dealers to .asia
//=========================================
function fe_manage_locations() {
global $wpdb,$wpdb2;
//1. get latest id
echo '<h1>Update Dealer Maps</h1><p>As this page loads, locations are being updated!</p>';
$currLocId = $wpdb->get_row("SELECT location_id FROM wp_map_locations ORDER BY location_id DESC LIMIT 1");
echo '<p>Latest Location ID: '.$currLocId->location_id.'</p>';
//2. check last added id
$currLocStatus = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM wp_options WHERE option_name = %s LIMIT 1", 'fe_current_location' ) );
echo '<p>Last Location Exported: '.$currLocStatus->option_value.'</p>';

    //3. pull out locations
    if ( (int)$currLocId->location_id > (int)$currLocStatus->option_value ) { // if the location id is higher than the last one imported from wp_options
    $getNewLocations = $wpdb->get_results($wpdb->prepare("SELECT * FROM wp_map_locations WHERE location_id > %d",$currLocStatus->option_value));

    $locations = array(); //put everything into an array to avoid lots of db connections

        foreach ($getNewLocations as $loc) {
            $locations[$loc->location_id]['location_title'] = $loc->location_title;
            $locations[$loc->location_id]['location_address'] = $loc->location_address;
            $locations[$loc->location_id]['location_animation'] = 'Bounce';
            $locations[$loc->location_id]['location_latitude'] = $loc->location_latitude;
            $locations[$loc->location_id]['location_longitude'] = $loc->location_longitude;
            $locations[$loc->location_id]['location_city'] = $loc->location_city;
            $locations[$loc->location_id]['location_state'] = $loc->location_state;            
            $locations[$loc->location_id]['location_country'] = $loc->location_country;            
            $locations[$loc->location_id]['location_postal_code'] = $loc->location_postal_code;
            $locations[$loc->location_id]['location_settings'] = $loc->location_settings;
            $locations[$loc->location_id]['location_group_map'] = $loc->location_group_map;
            $locations[$loc->location_id]['location_extrafields'] = $loc->location_extrafields;        
        }
    
    } else {
    echo "--> No change needed!";
    }

//5. update map with new locations

    $wpdb2 = new WPDB('fourtcom_devdb', 'Ku+b=2BDlDod', 'fourtcom_asia', 'localhost'); // connect to the .asia site
    
    foreach($locations as $key => $value) {
        
        $mapQry = $wpdb2->get_row($wpdb2->prepare("SELECT map_locations FROM wp_create_map WHERE map_id = %d",2)); //pull out locations from dealer map
        $maplocations = unserialize($mapQry->map_locations);
        $maplocations[] = $key;
        $maplocationupdate = serialize($maplocations);
        $updateMap = $wpdb2->query($wpdb2->prepare("UPDATE wp_create_map SET map_locations = '".$maplocationupdate."' WHERE map_id = %d",2));
        
        foreach ($value as $j => $k) {
            $$j = str_replace("'","\'",$k);
        }
        $qry = $wpdb2->query("INSERT INTO wp_map_locations (location_title,location_address,location_animation,location_latitude,location_longitude,location_city,location_country,location_state,location_postal_code,location_settings,location_group_map,location_extrafields) VALUES ('".$location_title."','".$location_address."','".$location_animation."','".$location_latitude."','".$location_longitude."','".$location_city."','".$location_country."','".$location_state."','".$location_post_code."','".$location_settings."','".$location_group_map."','".$location_extrafields."')");
    }
    
    $updatecurr = $wpdb->query($wpdb->prepare("UPDATE wp_options SET option_value = %d WHERE option_name = %s",$currLocId->location_id,'fe_current_location'));
    echo "--> Locations Updated";
}

// 2.1 Load Category Loading Data Form
//=========================================

function fe_load_category_data() {
global $wpdb;
    echo do_shortcode('[gravityform id="27" title="false" description="false" ajax="true"]');
}

// 2.1.1 Process Category Loading Data
//=========================================
function fe_process_load_category_data($entry, $form) {
    global $wpdb,$woocommerce;    
        //load up the form entry data into variables
        $categories = $entry['4'];
        $skus  = $entry['2'];
        $process = $entry['3'];
       
        $catArr = explode(",",$categories);
        $skuArr = explode(",",$skus);
        
        if ( $process == 'add' ) {
            foreach ( $catArr as $key => $value ) {
                foreach ($skuArr as $skey => $svalue ) {
                    wp_set_object_terms(wc_get_product_id_by_sku(trim($svalue)),(int)$value,'product_cat',true);
                }
            }   
        } else if ( $process == 'remove' ) {
            foreach ( $catArr as $key => $value ) {
                foreach ($skuArr as $skey => $svalue ) {
                    wp_remove_object_terms(wc_get_product_id_by_sku(trim($svalue)),(int)$value,'product_cat');
                }
            }
        }
       
    }
    add_action("gform_after_submission_27", "fe_process_load_category_data", 10, 2);

//POPULATE DROP DOWN FOR CATEGORIES

add_filter('gform_pre_render_27', 'awp_populate_choices');
add_filter('gform_pre_validation_27', 'awp_populate_choices');
add_filter('gform_pre_submission_filter_27', 'awp_populate_choices');
add_filter('gform_admin_pre_render_1', 'awp_populate_choices');
function awp_populate_choices($form) {
global $wpdb;

    $query = "SELECT t.term_id AS ID, t.name AS title  
        FROM wp_terms AS t
            LEFT JOIN wp_term_taxonomy AS ta
                ON ta.term_id = t.term_id            
            WHERE ta.taxonomy='product_cat'
                AND ta.parent=0
            ORDER BY t.name ASC";

    $cats = $wpdb->get_results($query);

    foreach ($form['fields'] as &$field) {
        
        if ($field->inputName == 'fecats') {
 
            // Generate your data here. Below is just an example
            $pages = get_posts('numberposts=-1&post_status=publish&post_type=page');
 
            // Generate a nice array that Gravity Forms can understand
            $choices = [];
            $choices[] = ['text' => 'Select Category', 'value' => ''];
            
            foreach ($cats as $key => $cat) {
                $choices[] = ['text' => $cat->title, 'value' => $cat->ID];

                $query = "SELECT t.term_id AS ID, t.name AS title  
                    FROM wp_terms AS t
                    LEFT JOIN wp_term_taxonomy AS ta
                    ON ta.term_id = t.term_id            
                    WHERE ta.taxonomy='product_cat'
                    AND ta.parent=$cat->ID
                    ORDER BY t.name ASC";

                    $subcats = $wpdb->get_results($query);
                foreach ($subcats as $subkey => $subcat) {
                    $choices[] = ['text' => '->'.$subcat->title, 'value' => $subcat->ID];
                }
            }
 
            // Set choices to field
            $field->choices = $choices;
        }
    }
    return $form;
}    

// 2.2 Load Apply Checkout Coupon Form
//=========================================

function fe_apply_checkout_coupon() {
global $wpdb;
    echo do_shortcode('[gravityform id="28" title="false" description="false" ajax="true"]');
}

// 2.2.1 Process Checkout Coupon
//=========================================
function fe_process_checkout_coupon_data($entry, $form) {
global $wpdb,$woocommerce;    

    $dta  = $entry['3'];
    $dtb  = $entry['9'];
    $dtc  = $entry['8'];
    $dtd  = $entry['7'];
    $dte  = $entry['6'];             
    $dtf  = $entry['5'];
    $dtg  = $entry['4'];
    $code = $entry['10'];    

    $dtArr = array();

    if ( $dta <> "" ) { $dtArr[] = $dta; }
    if ( $dtb <> "" ) { $dtArr[] = $dtb; }
    if ( $dtc <> "" ) { $dtArr[] = $dtc; }
    if ( $dtd <> "" ) { $dtArr[] = $dtd; }
    if ( $dte <> "" ) { $dtArr[] = $dte; }
    if ( $dtf <> "" ) { $dtArr[] = $dtf; }                    
    if ( $dtg <> "" ) { $dtArr[] = $dtg; }

    
   $optCoupon = 2251791;
   $optCouponDts = 2251792;
    
   $query = $wpdb->query("UPDATE wp_options SET option_value = '".$code."' WHERE option_id = ".$optCoupon."");
   $query = $wpdb->query("UPDATE wp_options SET option_value = '".serialize($dtArr)."' WHERE option_id = ".$optCouponDts."");   

}
add_action("gform_after_submission_28", "fe_process_checkout_coupon_data", 10, 2);


// 2.3 Load Apply RRPs Form
//=========================================

function fe_update_rrp() {
global $wpdb;
    echo do_shortcode('[gravityform id="29" title="false" description="false" ajax="true"]');
}



// 2.3.1 Process RRP Updates
//=========================================
function fe_process_update_rrp($entry, $form) {
global $wpdb, $woocommerce, $product;  
    $csvFile = $entry['1']; 
  
    if ( !$csvFile ) {
        return;
    }
    
    $csvFile = str_replace(SITE_URL,SITE_WEB_ROOT_PATH,$csvFile);

    ini_set('auto_detect_line_endings',TRUE);
    if (($handle = fopen($csvFile, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $sku = $prodId = '';
            
            if ( strpos($data[0], 'SKU') === false ) { // Get the product id from the sku
				$sku = $data[0];
			}
			
			if (wc_get_product_id_by_sku($sku) <> 0) {
			    $getProds = $wpdb->get_results("SELECT * FROM wp_posts WHERE post_type='product_variation' AND post_parent = ".wc_get_product_id_by_sku($sku)." ORDER by ID ASC");
			    $totRecs = $wpdb->num_rows;
			

                if ($totRecs > 0 ) { // variable product

				foreach ($getProds as $prod) {
				    
				    $prodId = $prod->ID;
				    
				    error_log('SKU: '. $sku . ' / PROD ID ' . $prodId . ' / GBP' . $data[1] . ' / EUR' . $data[2] . ' / USD' . $data[2]);
				    
				    if ( strpos($data[1], 'GBPRRP') === false && $data[1] <> '' ) {
						$normalrrp = $data[1];
				        $urecord = $wpdb->query("UPDATE wp_postmeta SET meta_value = '".$normalrrp."' WHERE meta_key = '_price' and post_id = ".$prodId."");
						$urecord = $wpdb->query("UPDATE wp_postmeta SET meta_value = '".$normalrrp."' WHERE meta_key = '_regular_price' and post_id = ".$prodId."");
				    }
				
				    if ( strpos($data[2], 'EURRRP') === false && $data[2] <> '' ) {
						$eurorrp = $data[2];
				    } else {
				        $eurorrp = '';
				    }

				    if ( strpos($data[3], 'USDRRP') === false && $data[3] <> '' ) {
						$usdrrp = $data[3];
				    } else {
				        $usdrrp = '';
				    }  
				    
				    $skele = '{"EUR":"'.$eurorrp.'","USD":"'.$usdrrp.'"}';
				 
				    $urecord = $wpdb->query("UPDATE wp_postmeta SET meta_value = '".$skele."' WHERE meta_key = '_regular_currency_prices' and post_id = ".$prodId."");
					$urecord = $wpdb->query("UPDATE wp_postmeta SET meta_value = '".$skele."' WHERE meta_key = 'variable_regular_currency_prices' and post_id = ".$prodId."");   
				}
				
                } else { // simple product
                
                    error_log('SIMPLE SKU: '. $sku . ' / PROD ID ' . $prodId . ' / GBP' . $data[1] . ' / EUR' . $data[2] . ' / USD' . $data[2]);
                    if ( strpos($data[1], 'GBPRRP') === false && $data[1] <> '' ) {
						$normalrrp = $data[1];
				        $urecord = $wpdb->query("UPDATE wp_postmeta SET meta_value = '".$normalrrp."' WHERE meta_key = '_price' and post_id = ".wc_get_product_id_by_sku($sku)."");
				        error_log("GBP: UPDATE wp_postmeta SET meta_value = '".$normalrrp."' WHERE meta_key = '_price' and post_id = ".wc_get_product_id_by_sku($sku));
						$urecord = $wpdb->query("UPDATE wp_postmeta SET meta_value = '".$normalrrp."' WHERE meta_key = '_regular_price' and post_id = ".wc_get_product_id_by_sku($sku)."");
				    }
				
				    
				    if ( strpos($data[2], 'EURRRP') === false && $data[2] <> '' ) {
				        $eurorrp = $data[2];
				    } else {
				        $eurorrp = '';
				    }

				    if ( strpos($data[3], 'USDRRP') === false && $data[3] <> '' ) {
						$usdrrp = $data[3];
				    } else {
				        $usdrrp = '';
				    }  
				    
				    $skele = '{"EUR":"'.$eurorrp.'","USD":"'.$usdrrp.'"}';
				 
				    $urecord = $wpdb->query("UPDATE wp_postmeta SET meta_value = '".$skele."' WHERE meta_key = '_regular_currency_prices' and post_id = ".wc_get_product_id_by_sku($sku)."");
				    error_log("EUR/USD UPDATE wp_postmeta SET meta_value = '".$skele."' WHERE meta_key = '_regular_currency_prices' and post_id = ".wc_get_product_id_by_sku($sku));
					$urecord = $wpdb->query("UPDATE wp_postmeta SET meta_value = '".$skele."' WHERE meta_key = 'variable_regular_currency_prices' and post_id = ".wc_get_product_id_by_sku($sku).""); 
                
                
                }
			
			    //$wpdb->close();
			}

        wc_delete_product_transients($prodId);
        }
        fclose($handle);
    }
    ini_set('auto_detect_line_endings',FALSE);
  
}
add_action("gform_after_submission_29", "fe_process_update_rrp", 10, 2);         


// 2.4 Load Store Date Related Coupon Form
//=========================================

function fe_load_coupon_frm() {
    echo do_shortcode('[gravityform id="30" title="false" description="false" ajax="true"]');
}


// 2.4.1 Process Store Date Related Coupon Addition
//=========================================
function fe_process_coupon_frm($entry, $form) {
global $wpdb;
    $coupArr = array();
    $skuid = '';
    $coupArr['startdt'] = strtotime($entry['1']);
    $coupArr['starttime'] = $entry['2'];
    $coupArr['enddt'] = strtotime($entry['3']);
    $coupArr['endtime'] = $entry['4'];
    $coupArr['couponName'] = $entry['6'];
    $coupArr['couponDesc'] = $entry['7'];
    $coupArr['couponType'] = $entry['8'];
    $coupArr['couponAmount'] = $entry['9'];
    $coupArr['couponUsage'] = $entry['12'];
    
    $skus = explode(',',$entry['10']);
    foreach ( $skus as $sku ) {
        $skuid .= wc_get_product_id_by_sku($sku).',';
    }
    
    $coupArr['skus'] = rtrim($skuid,",");
    
    
    $query = $wpdb->query("UPDATE wp_options SET option_value = '".serialize($coupArr)."' WHERE option_name = 'fe_life_dt_coupon'");
    
}
add_action("gform_after_submission_30", "fe_process_coupon_frm", 10, 2);        


// 2.5 Load Swap Homepage Form
//=========================================

function fe_swap_homepage_frm() {
   echo do_shortcode('[gravityform id="31" title="false" description="false" ajax="true"]');
}


// 2.5.1 Process Swap Homepage Form
//=========================================
function fe_process_swap_homepage($entry, $form) {
global $wpdb;
    $coupArr = array();
    $coupArr['startdt'] = strtotime($entry['1']);
    $coupArr['starttime'] = $entry['2'];
    $coupArr['enddt'] = strtotime($entry['3']);
    $coupArr['endtime'] = $entry['4'];

    $query = $wpdb->query("UPDATE wp_options SET option_value = '".serialize($coupArr)."' WHERE option_name = 'fe_swap_homepage'");
    
}
add_action("gform_after_submission_31", "fe_process_swap_homepage", 10, 2);      


// 2.6 Load Switch Off Express Delivery Form
//=========================================

function fe_switch_express_off_frm() {
    echo do_shortcode('[gravityform id="32" title="false" description="false" ajax="true"]');
}


// 2.6.1 Process Switch Off Express Delivery Form
//=========================================
function fe_process_switch_express_off($entry, $form) {
global $wpdb;
    
    $dtArr['startdt'] = strtotime($entry['1']);
    $dtArr['enddt'] = strtotime($entry['2']);

    $query = $wpdb->query("UPDATE wp_options SET option_value = '".serialize($dtArr)."' WHERE option_name = 'fe_switch_off_express'");
    
}
add_action("gform_after_submission_32", "fe_process_switch_express_off", 10, 2);   


// 2.7 Manage Major FE Sale (Summer/Winter)
//=========================================

function fe_key_sale_event() {

    global $wpdb;
    echo do_shortcode('[gravityform id="33" title="false" description="false" ajax="true"]');

    //get the category ids
    $getTerms = $wpdb->get_results("SELECT t.term_id, t.name, t.slug FROM wp_term_taxonomy tt inner join wp_terms t on t.term_id = tt.term_id where (tt.parent = 698 OR tt.parent = 912 OR tt.parent = 918) ORDER BY t.term_id ASC");
    echo "<p>Term IDs for use in Columns C-G</p>";
    echo "KEY CATEGORIES<br/><br/>";
    echo "Cat ID: 698 / Name: Outlet</br>";
    echo "Cat ID: 912 / Name: Men's Outlet<br/>";
    echo "Cat ID: 918 / Name: Women's Outlet</br><br/>";
    echo "CHILD CATEGORIES<br/><br/>";
    foreach ( $getTerms as $term) {
        echo "Cat ID: " . $term->term_id . " / Name: " . $term->name . " / Slug: " . $term->slug."<br/>";
    }
    
}

// 2.7.1 Process Major FE Sale
//=========================================
function fe_process_key_sale($entry, $form) {
global $wpdb;
    $saleArr = array();

    $saleArr['period'] = $entry['4'];
    $preview = $entry['5.1'];
    
    if ( $preview == 'yes') { 
        $saleArr['prevDt'] = strtotime($entry['6']);
        $saleArr['prevTm'] = $entry['7'];
    }    
    
    $saleArr['saleDt'] = strtotime($entry['8']);
    $saleArr['saleTm'] = strtotime($entry['9']);
    
    $saleArr['endDt'] = strtotime($entry['10']);
    $saleArr['endTm'] = strtotime($entry['11']);
    
    $saleArr['spreadsht'] = $entry['2'];
    $saleArr['techslider'] = $entry['12'];
    $saleArr['shopslider'] = $entry['13'];    
    
    $query = $wpdb->query("UPDATE wp_options SET option_value = '".serialize($saleArr)."' WHERE option_name = 'fe_key_sale_data'");
    
}
add_action("gform_after_submission_33", "fe_process_key_sale", 10, 2);      

// 2.8 Load Bulk Override Stock Form
//=========================================

function fe_bulk_override_stock() {
    echo do_shortcode('[gravityform id="34" title="false" description="false" ajax="true"]');
}

// 2.8.1 Process Bulk Override Stock
//=========================================
function fe_process_bulk_stock_override($entry, $form) {
global $wpdb, $woocommerce, $product;  
 $csvFile = $entry['1']; 
  
    if ( !$csvFile ) {
        return;
    }
    
    $csvFile = str_replace(SITE_URL,SITE_WEB_ROOT_PATH,$csvFile);

    ini_set('auto_detect_line_endings',TRUE);
    if (($handle = fopen($csvFile, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            
            if ( strpos($data[0], 'SKU') === false ) { // Get the product id from the sku
				$sku = $data[0];
			}
			
            if ( strpos($data[1], 'STOCK') === false ) { // Get the product id from the sku
				$stock = $data[1];
			}
			
			//get the product ID
			$prodId = $wpdb->get_row("SELECT post_id FROM wp_postmeta WHERE meta_key = '_sku' AND meta_value='".strtoupper($sku)."' ORDER BY post_id DESC");
			if ($wpdb->num_rows > 0 ) {
			    //get current stock
			    $cStock = $wpdb->get_row("SELECT meta_value FROM wp_postmeta WHERE meta_key = '_stock' AND post_id ='".$prodId->post_id."'");
			    //log the comparison
			    //error_log ('sku: '.strtoupper($sku).' / current stock: '.$cStock->meta_value.' / sagestock: '. $stock);
			    //update stock level
			    $wpdb->query("UPDATE wp_postmeta SET meta_value = '".$stock."' WHERE meta_key = '_stock' AND post_id ='".$prodId->post_id."'");
			    
			    if ( $stock == 0 ) { // change the stock status too to reflect stock number
                    $wpdb->query("UPDATE wp_postmeta SET meta_value = 'outofstock' WHERE meta_key = '_stock_status' AND post_id = ".$prodId->post_id."");
                } else {
                    $wpdb->query("UPDATE wp_postmeta SET meta_value = 'instock' WHERE meta_key = '_stock_status' AND post_id = ".$prodId->post_id."");
                }
			}
			
        wc_delete_product_transients($prodId->post_id);
            
        }
        fclose($handle);
    }
    ini_set('auto_detect_line_endings',FALSE);
  
}
add_action("gform_after_submission_34", "fe_process_bulk_stock_override", 10, 2); 


// 2.9 Load UBWPOTY Form
//=========================================

function fe_upload_uwbpoty() {
    echo do_shortcode('[gravityform id="35" title="false" description="false" ajax="true"]');
}


// 2.9.1 Process UBWPOTY Image
//=========================================
function fe_process_uwbpoty($entry, $form) {
global $wpdb;
    $iUsername = $entry['1']; 
    $iUrl = 'https://'.$entry['6'];
    $iImage = $entry['3'];
    $query = $wpdb->query("INSERT INTO wp_ubpoty (username,imgurl,imglink) VALUES ('".$iUsername."','".$iImage."','".$iUrl."') ");
}
add_action("gform_after_submission_35", "fe_process_uwbpoty", 10, 2); 

// 2.10 Manage Team Divers/Ambassadors
//=========================================

function fe_manage_team_ambassadors() {
global $wpdb;
$getNames = $wpdb->get_results("SELECT * FROM wp_fe_divers ORDER BY firstname ASC");
$frm = '<h1>Manage Team Divers/Ambassadors</h1><hr/><h3>A) (EDIT) Select the Diver from the list below to edit</h3><form id="feteam"><select id="femember" name="femember"><option value="">Select Diver</option>';
    foreach ( $getNames as $name ) {
        $frm .= '<option value="'.$name->id.'">'.$name->firstname.' '.$name->lastname.'</option>';
    }
$frm .= '</select></form><div class="divermsgs"></div><div id="diverfrm" class="gform_wrapper"></div>';



$frm .= '<br/><hr/><h3>B) (ADD) Add NEW Team Diver/Ambassador</h3>';
$frm .= '<div style="width:60%;" id="addnewdiver">'.do_shortcode('[gravityform id="36" title="false" description="false" ajax="true"]').'</div>';
$frm .= '<script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery("select#fediver").on(\'change\',function (e) {
                e.preventDefault();
                var fediver = jQuery("select#fediver").val();
                var dataString = \'diver=\'+fediver;
                jQuery.ajax({
                        type: "GET",
                        url: window.location.protocol + "//" + window.location.hostname + "/wp-content/plugins/fourth-element-scripts/includes/load-diver.php",
                        data: dataString,
                        dataType: "html",
                        beforeSend: function(){
                        },
                        success: function(sendResult){
                            var diverbox = sendResult.split("|"), a = diverbox[0], b = diverbox[1], c = diverbox[2], d = diverbox[3], e = diverbox[4], f = diverbox[5], g = diverbox[6], h = diverbox[7], i = diverbox[8], j = diverbox[9], k = diverbox[10];

                            arr = k.split(\'!\');
                            if (jQuery.inArray(\'Arctic\',arr) != -1){ var opta = \'<option value="Arctic" selected>Arctic</option>\'; } else { var opta = \'<option value="Arctic">Arctic</option>\'; }
                            if (jQuery.inArray(\'ArcticExpedition\',arr) != -1){ var optb = \'<option value="ArcticExpedition" selected>Arctic Expedition</option>\'; } else { var optb = \'<option value="ArcticExpedition">Arctic Expedition</option>\'; }
                            if (jQuery.inArray(\'Argonaut\',arr) != -1){ var optc = \'<option value="Argonaut" selected>Argonaut</option>\'; } else { var optc = \'<option value="Argonaut">Argonaut</option>\'; }
                            if (jQuery.inArray(\'Halo\',arr) != -1){ var optd = \'<option value="Halo" selected>Halo 3D</option>\'; } else { var optd = \'<option value="Halo">Halo 3D</option>\'; }
                            if (jQuery.inArray(\'Hydra\',arr) != -1){ var opte = \'<option value="Hydra" selected>Hydra</option>\'; } else { var opte = \'<option value="Hydra">Hydra</option>\'; }
                            if (jQuery.inArray(\'Rashguards\',arr) != -1){ var optf = \'<option value="Rashguards" selected>Rashguards</option>\'; } else { var optf = \'<option value="Rashguards">Rashguards</option>\'; }
                            if (jQuery.inArray(\'J2\',arr) != -1){ var optg = \'<option value="J2" selected>J2 Baselayer</option>\'; } else { var optg = \'<option value="J2">J2 Baselayer</option>\'; }
                            if (jQuery.inArray(\'Life\',arr) != -1 ){ var opth = \'<option value="Life" selected>Life</option>\'; } else { var opth = \'<option value="Life">Life</option>\'; }
                            if (jQuery.inArray(\'Proteus\',arr) != -1){ var opti = \'<option value="Proteus" selected>Proteus II</option>\'; } else { var opti = \'<option value="Proteus">Proteus II</option>\'; }
                            if (jQuery.inArray(\'RF1\',arr) != -1){ var optj = \'<option value="RF1" selected>RF1</option>\'; } else { var optj = \'<option value="RF1">RF1</option>\'; }
                            if (jQuery.inArray(\'Surface\',arr) != -1){ var optk = \'<option value="Surface" selected>Surface</option>\'; } else { var optk = \'<option value="Surface">Surface</option>\'; }
                            if (jQuery.inArray(\'TechShorts\',arr) != -1){ var optl = \'<option value="TechShorts" selected>Technical Shorts</option>\'; } else { var optl = \'<option value="TechShorts">Technical Shorts</option>\'; }
                            if (jQuery.inArray(\'Thermocline\',arr) != -1){ var optm = \'<option value="Thermocline" selected>Thermocline</option>\'; } else { var optm = \'<option value="Thermocline">Thermocline</option>\'; }
                            if (jQuery.inArray(\'X-Core\',arr) != -1){ var optn = \'<option value="X-Core" selected>X-Core</option>\'; } else { var optn = \'<option value="X-Core">X-Core</option>\'; }
                            if (jQuery.inArray(\'Xenos\',arr) != -1){ var opto = \'<option value="Xenos" selected>Xenos</option>\'; } else { var opto = \'<option value="Xenos">Xenos</option>\'; }
                            if (jQuery.inArray(\'Xerotherm\',arr) != -1){ var optp = \'<option value="Xerotherm" selected>Xerotherm</option>\'; } else { var optp = \'<option value="Xerotherm">Xerotherm</option>\'; }
                            
                            jQuery("#diverfrm").html(\'<p style="color:#ff0000;" class="remlnk">Click <a href="?diver=\'+fediver+\'" class="removediver" style="color:#ff0000;">here to delete</a> this diver from the page</p><form id="updatediver" style="margin-top:30px;"><input type="hidden" name="edit_id" value=""/><ul class="gform_fields top_label form_sublabel_below description_below"><li id="edit_1" class="gfield field_sublabel_below field_description_below gfield_visibility_visible"><label class="gfield_label gfield_label_before_complex">Name</label><div class="ginput_complex ginput_container no_prefix has_first_name no_middle_name has_last_name no_suffix gf_name_has_2 ginput_container_name" id="edit_1_1"><span id="edit_1_1_3_container" class="name_first"><input type="text" name="edit_1_1" id="edit_1_1_1" value="" aria-label="First name" aria-invalid="false" ><label for="edit_1_1_1">First</label></span><span id="edit_1_1_6_container" class="name_last"><input type="text" name="edit_1_2" id="edit_1_2" value="" aria-label="Last name" aria-invalid="false"><label for="edit_1_1_2">Last</label></span></div></li><li id="edit_1_2" class="gfield gfield_contains_required field_sublabel_below field_description_below gfield_visibility_visible"><label class="gfield_label" for="input_19_2">Team Diver or Ambassador?<span class="gfield_required">*</span></label><div class="ginput_container ginput_container_select"><select name="edit_1_3" id="edit_1_3" class="medium gfield_select" aria-required="true" aria-invalid="false"><option value="">Select</option><option value="Team Diver">Team Diver</option><option value="Ambassador">Ambassador</option></select></div></li><li id="edit_1_3" class="gfield field_sublabel_below field_description_below gfield_visibility_visible"><label class="gfield_label" for="input_19_3">Email</label><div class="ginput_container ginput_container_email"><input name="edit_1_99" id="edit_1_99" type="text" value="" class="medium" aria-invalid="false"></div></li><li id="edit_1_4" class="gfield field_sublabel_below field_description_below gfield_visibility_visible"><label class="gfield_label" for="input_19_4">Bio</label><div class="ginput_container ginput_container_textarea"><textarea name="edit_1_4" id="edit_1_4" class="textarea medium" aria-invalid="false" rows="10" cols="50"></textarea></div></li><li id="edit_1_5" class="gfield field_sublabel_below field_description_below gfield_visibility_visible"><label class="gfield_label" for="input_19_5">Website</label><div class="gfield_description" id="gfield_description_19_10">THIS CAN INCLUDE HTTPS:// OR HTTP://</div><div class="ginput_container ginput_container_website"><input name="edit_1_5" id="edit_1_5" type="text" value="" class="medium" placeholder="" aria-invalid="false"></div></li><li id="edit_1_6" class="gfield field_sublabel_below field_description_above gfield_visibility_visible"><label class="gfield_label" for="input_19_6">Facebook</label><div class="gfield_description" id="gfield_description_19_6">DO NOT INCLUDE THE FULL FACEBOOK LINK, ONLY THE USERNAME AT THE END OF THE URL</div><div class="ginput_container ginput_container_text"><input name="edit_1_6" id="edit_1_6" type="text" value="" class="medium" aria-describedby="gfield_description_19_6" aria-invalid="false"></div></li><li id="edit_1_7" class="gfield field_sublabel_below field_description_above gfield_visibility_visible"><label class="gfield_label" for="input_19_7">Instagram</label><div class="gfield_description" id="gfield_description_19_7">DO NOT INCLUDE THE FULL INSTAGRAM LINK, ONLY THE USERNAME AT THE END OF THE URL</div><div class="ginput_container ginput_container_text"><input name="edit_1_7" id="edit_1_7" type="text" value="" class="medium" aria-describedby="gfield_description_19_7" aria-invalid="false"></div></li><li id="edit_1_8" class="gfield field_sublabel_below field_description_above gfield_visibility_visible"><label class="gfield_label" for="input_19_8">Twitter</label><div class="gfield_description" id="gfield_description_19_8">DO NOT INCLUDE THE FULL TWITTER URL, ONLY THE USERNAME AT THE END OF THE URL</div><div class="ginput_container ginput_container_text"><input name="edit_1_8" id="edit_1_8" type="text" value="" class="medium" aria-describedby="gfield_description_19_8" aria-invalid="false"></div></li><li id="edit_1_9" class="gfield gfield--width-full field_sublabel_below field_description_above gfield_visibility_visible"><label class="gfield_label" for="edit_1_9">What gear do they use/wear?</label><div class="gfield_description" id="gfield_description_1_9_11">The choices made here means this person may appear on the product/category pages.</div><div class="ginput_container ginput_container_multiselect"><select multiple="multiple" size="7" name="input_1_9[]" id="edit_1_9" class="large gfield_select" aria-invalid="false" aria-describedby="gfield_description_1_9_11">\'+opta+optb+optc+optd+opte+optf+optg+opth+opti+optj+optk+optl+optm+optm+optn+\'</select></div></li></ul><br/><p>If you have a new image, please send to Web Developer separately.</p><br/><input type="submit" value="UPDATE DIVER"/></form>\');
                            jQuery(\'select[name=edit_1_3]\').val(a);
                            jQuery(\'input[name=edit_1_1]\').val(b);
                            jQuery(\'input[name=edit_1_2]\').val(c);
                            jQuery(\'textarea[name=edit_1_4]\').val(d);
                            jQuery(\'input[name=edit_1_5]\').val(e);
                            jQuery(\'input[name=edit_1_6]\').val(f);
                            jQuery(\'input[name=edit_1_7]\').val(g);
                            jQuery(\'input[name=edit_1_8]\').val(h);         
                            jQuery(\'input[name=edit_1_99]\').val(i);                        
                            jQuery(\'input[name=edit_id]\').val(j);                             
                        }
                });
            });
             
            jQuery(document).on("click", ".removediver", function(e) {
                e.preventDefault();
                var str = this.search.slice(7);
                jQuery.ajax({
                    type: "GET",
                    url: window.location.protocol + \'//\' + window.location.hostname + \'/wp-content/plugins/fourth-element-scripts/includes/delete-diver.php\',
                    data: {getSalt: str },
                    dataType: "html",
                    success: function(result){
                        jQuery(\'#updatediver\').hide();
                        jQuery(\'.remlnk\').hide();
                        jQuery(\'.divermsgs\').html(\'<p style="color:#22a603;">Diver has been removed!</p>\');
                    }
                });
            });
            
            jQuery("#diverfrm").submit("#updatediver",function(e){   
                e.preventDefault();
                var id = jQuery(\'input[name=edit_id]\').val();                
                var firstname = jQuery(\'input[name=edit_1_1]\').val();
                var lastname = jQuery(\'input[name=edit_1_2]\').val();   
                var divertype = jQuery(\'select[name=edit_1_3]\').val();
                var email = jQuery(\'input[name=edit_1_99]\').val();
                var bio = jQuery(\'textarea[name=edit_1_4]\').val();
                var website = jQuery(\'input[name=edit_1_5]\').val();
                var facebook = jQuery(\'input[name=edit_1_6]\').val();   
                var instagram = jQuery(\'input[name=edit_1_7]\').val();
                var twitter = jQuery(\'input[name=edit_1_8]\').val();
                var products = jQuery(\'select[name="input_1_9[]"]\').val();
                var dataString = \'id=\'+id+\'&firstname=\'+firstname+\'&lastname=\'+lastname+\'&divertype=\'+divertype+\'&email=\'+email+\'&bio=\'+bio+\'&website=\'+website+\'&facebook=\'+facebook+\'&instagram=\'+instagram+\'&twitter=\'+twitter+\'&products=\'+products;
                //alert(dataString);
                jQuery.ajax({
                        type: "POST",
                        url: window.location.protocol + "//" + window.location.hostname + "/wp-content/plugins/fourth-element-scripts/includes/update-diver.php",
                        data: dataString,
                        dataType: "html",
                        beforeSend: function(){
                        },
                        success: function(sendResult){
                            jQuery(\'#updatediver\').hide();
                            jQuery(\'.remlnk\').hide();
                            jQuery(\'.divermsgs\').html(\'<p style="color:#22a603;">Diver has been updated!</p>\');
                        }
                });
            });
        });
        </script>';
echo $frm;
}

// 2.10.1 Process Add New Team Diver/Ambassador
//===========================================
function fe_add_team_ambassadors($entry, $form) {
global $wpdb, $woocommerce, $product; 
    
    $divertype = $entry['2'];
    $firstname = $entry['1.3'];
    $lastname = $entry['1.6'];    
    $email = $entry['3'];
    $bio = $entry['4'];    
    $website = $entry['10'];
    $facebook = $entry['6'];
    $instagram = $entry['7'];
    $twitter = $entry['8'];
    $image = $entry['9'];    
    
    $lastname = str_replace("'","''",$lastname);
    
    $message = print_r($entry, true);
    $message = wordwrap($message, 70);
    wp_mail('darren.shilson@fourthelement.com', 'Getting the Gravity Form Field IDs - Team Diver', $message);
    
    $query = $wpdb->query("INSERT INTO wp_fe_divers (divertype,email,firstname,lastname,bio,website,facebook,instagram,twitter,imageurl) VALUES ('".$divertype."','".$email."','".$firstname."','".$lastname."','".$bio."','".$website."','".$facebook."','".$instagram."','".$twitter."','".$image."')");
}
add_action("gform_after_submission_36", "fe_add_team_ambassadors", 10, 2);  


// 2.11 Export CONNECT referral data
// =============================================================================
function fe_connect_admin() {
    
    echo '<h1>Export CONNECT data</h1><p>Use the form below to set your then and now dates. This will provide a downloadable CSV file based on midnight timings (at present) between the two dates.</p>';

    echo '<form id="sage-export">
     <label for "fromdate" style="width:100px;display:inline-block;">From:</label> <input type="text" name="fromdt" placeholder="DD-MM-YYYY" /><br/>
    <label for "todate" style="width:100px;display:inline-block;">To:</label> <input type="text" name="todt" placeholder="DD-MM-YYYY" /><br/>
        <input type="submit" value="EXPORT REFERRALS" style="margin-top:20px;" />
    </form>';
    echo '<div style="clear:both;margin-top:20px;display:none;" class="download"></div>';
    
        echo '
        <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery(\'form#sage-export\').submit(function(e){ 
                    e.preventDefault();
                    var fromdt = jQuery(\'input[name=fromdt]\').val();
                    var todt = jQuery(\'input[name=todt]\').val();
                    var dataString = \'thendt=\'+fromdt+\'&nowdt=\'+todt;
                    

                    jQuery.ajax({
                        type: "GET",
                        url: window.location.protocol + "//" + window.location.hostname + "/wp-content/themes/fourth-element-child/connect.php",
                        data: dataString,
                        dataType: "html",
                        beforeSend: function(){
                            jQuery(".download").hide();
                        },
                        success: function(result){                             
                            jQuery(".download").show();
                            jQuery(".download").html(result);
                        }
                    });
                });
            });
        </script>';
    
}


// 2.12 Load Product Meta Data from CSV
//=========================================

function fe_load_meta_data() {
global $wpdb;
    echo do_shortcode('[gravityform id="38" title="false" description="false" ajax="true"]');
}

// 2.12.1 Process Process Meta Data
//=========================================
function fe_process_meta_data($entry, $form) {
global $wpdb;    
    $csvFile = $entry['1']; 
  
    if ( !$csvFile ) {
        return;
    }
    
    error_log($csvFile);
    $csvFile = str_replace(SITE_URL,SITE_WEB_ROOT_PATH,$csvFile);

    ini_set('auto_detect_line_endings',TRUE);
    if (($handle = fopen($csvFile, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, "|")) !== FALSE) {
            
            $features = $fabric = $fit = ''; 

            if ( strpos($data[0], 'HSKU') === false ) { // Get the product id from the sku
                $getProdId = $wpdb->get_row("SELECT post_id FROM wp_postmeta WHERE meta_key = '_sku' and meta_value = '".$data[0]."'");
                $productId = $getProdId->post_id;  
            }
                
            if ( strpos($data[1], 'HFEATURES') === false ) { // Get the features tab data
                $features = $data[1];
                update_post_meta($productId,'woo_features',$features);
            }
                
            if ( strpos($data[2], 'HFABRIC') === false ) { // Get the fabric tab data
                $fabric = $data[2];
                update_post_meta($productId,'woo_fabric_care',$fabric);                    
            }
                
            if ( strpos($data[3], 'HFIT') === false ) { // Get the fit tab data
                $fit = $data[3];
                update_post_meta($productId,'woo_fit',$fit);                    
            }
                

        wc_delete_product_transients($productId);
        }
        fclose($handle);
    }
    ini_set('auto_detect_line_endings',FALSE);
}
add_action("gform_after_submission_38", "fe_process_meta_data", 10, 2);


// 2.13 Load Amend Publication Date Form
//=========================================

function fe_amend_pub_date() {
global $wpdb;
    echo '<h1>Change Product Publication Dates</h1><p>Use the form below to set new dates.</p>';
    echo do_shortcode('[gravityform id="39" title="false" description="false" ajax="true"]');
}
			
			
// 2.13.1 Process Amend Publication Date Form
//=========================================
function fe_amendpub_data($entry, $form) {
global $wpdb,$woocommerce;   
    
    $date = $entry['1'];
    $skus  = $entry['2'];
   
    $date = $date.' 00:00:00';
    $skuArr = explode(",",$skus);
    
    foreach ($skuArr as $skey => $svalue ) {
        $pId = wc_get_product_id_by_sku(trim($svalue));
        $wpdb->query("UPDATE wp_posts SET post_date = '".$date."' WHERE ID = '".$pId."'");
    }
    
}
add_action("gform_after_submission_39", "fe_amendpub_data", 10, 2);


// 2.14 Load Atum Inventory Form
//=========================================

function fe_setup_atum_inventories() {
global $wpdb;
    echo do_shortcode('[gravityform id="47" title="false" description="false" ajax="true"]');
}
// 2.14.1 Process Atum Inventory Form
//=========================================
function fe_process_atum_inventories($entry, $form) {
global $wpdb,$woocommerce; 

	$skus = str_replace(" ","",$entry['1']);
	$skuArray = explode(",",$skus);
    
$eucountries = $uscountries = '';
$eucountries = 'a:27:{i:0;s:2:"BE";i:1;s:2:"BG";i:2;s:2:"CZ";i:3;s:2:"DK";i:4;s:2:"DE";i:5;s:2:"EE";i:6;s:2:"IE";i:7;s:2:"EL";i:8;s:2:"ES";i:9;s:2:"FR";i:10;s:2:"HR";i:11;s:2:"IT";i:12;s:2:"CY";i:13;s:2:"LV";i:14;s:2:"LT";i:15;s:2:"LU";i:16;s:2:"HU";i:17;s:2:"MT";i:18;s:2:"NL";i:19;s:2:"AT";i:20;s:2:"PL";i:21;s:2:"PT";i:22;s:2:"RO";i:23;s:2:"SI";i:24;s:2:"SK";i:25;s:2:"FI";i:26;s:2:"SE";}';
$uscountries = 'a:1:{i:0;s:2:"US";}';

foreach ($skuArray as $sku) {

    $euId = $usId = '';

    //get stock status
    $getStock = get_post_meta(wc_get_product_id_by_sku($sku),'_stock_status',true);
    
    //generate the inventory meta record for the new region
    //note on backorders - set 0 for no, 1 for allow and 2 for allow and notify
    $getBo = get_post_meta(wc_get_product_id_by_sku($sku),'_backorders',true);
    $storeBo = strtr($getBo, array("no"=>0,"yes"=>1,"notify"=>2));
        
    
    $getProds = $wpdb->get_results("SELECT * FROM wp_posts WHERE post_type='product_variation' AND post_parent = ".wc_get_product_id_by_sku($sku)." ORDER by ID ASC");
    $totRecs = $wpdb->num_rows;
    
    if ( $totRecs == 0 ) { // simple product
    
 	    //************************************//	            
	    //**GENERATE EUROPE INVENTORY RECORD**//
	    //************************************//   
        $aquery = $wpdb->query("INSERT INTO wp_atum_inventories (product_id,name,priority,region,inventory_date,is_main,write_off,inbound_stock,stock_on_hold,sold_today,sales_last_days,reserved_stock,customer_returns,warehouse_damage,lost_in_post,other_logs,out_stock_days,lost_sales,update_date) values ('".wc_get_product_id_by_sku(strtoupper($sku))."','Euro EU Warehouse',2,'".$eucountries."',CURRENT_TIMESTAMP,0,0,0,0,0,0,0,0,0,0,0,0,0,CURRENT_TIMESTAMP) ");                    
        $euId = $wpdb->insert_id;
        $bquery = $wpdb->query("INSERT INTO wp_atum_inventory_meta (inventory_id,sku,manage_stock,stock_quantity,backorders,stock_status,sold_individually) VALUES (".$euId.",'-E-".$sku."',1,5,".$storeBo.",'".$getStock."',0)");
            
 	    //************************************//	            
	    //**GENERATE USA INVENTORY RECORD   **//
	    //************************************//   
        $cquery = $wpdb->query("INSERT INTO wp_atum_inventories (product_id,name,priority,region,inventory_date,is_main,write_off,inbound_stock,stock_on_hold,sold_today,sales_last_days,reserved_stock,customer_returns,warehouse_damage,lost_in_post,other_logs,out_stock_days,lost_sales,update_date) values ('".wc_get_product_id_by_sku(strtoupper($sku))."','USA Warehouse',2,'".$uscountries."',CURRENT_TIMESTAMP,0,0,0,0,0,0,0,0,0,0,0,0,0,CURRENT_TIMESTAMP) ");                    
        $usId = $wpdb->insert_id;
        $dquery = $wpdb->query("INSERT INTO wp_atum_inventory_meta (inventory_id,sku,manage_stock,stock_quantity,backorders,stock_status,sold_individually) VALUES (".$usId.",'".$sku."',1,5,".$storeBo.",'".$getStock."',0)");
                
    
        //generate the product data record FOR NEW PRODUCTS
        $equery = $wpdb->query("INSERT INTO wp_atum_product_data (product_id,atum_controlled,inheritable) VALUES (".wc_get_product_id_by_sku(strtoupper($sku)).",1,0)");

        //generate the meta data for the product
        update_post_meta(wc_get_product_id_by_sku(strtoupper($sku)),'_atum_manage_stock','yes');
        update_post_meta(wc_get_product_id_by_sku(strtoupper($sku)),'_multi_inventory','yes');
        update_post_meta(wc_get_product_id_by_sku(strtoupper($sku)),'_inventory_sorting_mode','global');            
        update_post_meta(wc_get_product_id_by_sku(strtoupper($sku)),'_inventory_iteration','global');            
        update_post_meta(wc_get_product_id_by_sku(strtoupper($sku)),'_expirable_inventories','global');            
        update_post_meta(wc_get_product_id_by_sku(strtoupper($sku)),'_price_per_inventory','global');     
    
    } else {
        
        foreach ($getProds as $prod) {
            
            $getStock = $getBo = $storeBo = $euId = $usId = '' ;
            
            //get stock status
            $getStock = get_post_meta($prod->ID,'_stock_status',true);
            
            //generate the inventory meta record for the region
            //note on backorders - set 0 for no, 1 for allow and 2 for allow and notify
            $getBo = get_post_meta($prod->ID,'_backorders',true);
            $storeBo = strtr($getBo, array("no"=>0,"yes"=>1,"notify"=>2));
            
            //get variable sku
            $sku = get_post_meta($prod->ID,'_sku',true);               
            
 	        //************************************//	            
	        //**GENERATE EUROPE INVENTORY RECORD**//
	        //************************************//
                    
            $aquery = $wpdb->query("INSERT INTO wp_atum_inventories (product_id,name,priority,region,inventory_date,is_main,write_off,inbound_stock,stock_on_hold,sold_today,sales_last_days,reserved_stock,customer_returns,warehouse_damage,lost_in_post,other_logs,out_stock_days,lost_sales,update_date) values ('".$prod->ID."','Euro EU Warehouse',2,'".$eucountries."',CURRENT_TIMESTAMP,0,0,0,0,0,0,0,0,0,0,0,0,0,CURRENT_TIMESTAMP) ");
            $euId = $wpdb->insert_id;
            $bquery = $wpdb->query("INSERT INTO wp_atum_inventory_meta (inventory_id,sku,manage_stock,stock_quantity,backorders,stock_status,sold_individually) VALUES (".$euId.",'-E-".$sku."',1,5,".$storeBo.",'".$getStock."',0)");
            
            
 	        //************************************//	            
	        //**GENERATE USA INVENTORY RECORD   **//
	        //************************************//
            
            if ( strpos($sku,'BTA') !== false || strpos($sku,'BTP') !== false || strpos($sku,'BTR') !== false ) { //US Boots One Size Up
                $sku = strtr($sku,array('04'=>'05','05'=>'06','06'=>'07','07'=>'08','08'=>'09','09'=>'10','10'=>'11','11'=>'12','12'=>'13','13'=>'14'));
            }
                
            $cquery = $wpdb->query("INSERT INTO wp_atum_inventories (product_id,name,priority,region,inventory_date,is_main,write_off,inbound_stock,stock_on_hold,sold_today,sales_last_days,reserved_stock,customer_returns,warehouse_damage,lost_in_post,other_logs,out_stock_days,lost_sales,update_date) values ('".$prod->ID."','USA Warehouse',2,'".$uscountries."',CURRENT_TIMESTAMP,0,0,0,0,0,0,0,0,0,0,0,0,0,CURRENT_TIMESTAMP) ");
            $usId = $wpdb->insert_id;
            $dquery = $wpdb->query("INSERT INTO wp_atum_inventory_meta (inventory_id,sku,manage_stock,stock_quantity,backorders,stock_status,sold_individually) VALUES (".$usId.",'".$sku."',1,5,".$storeBo.",'".$getStock."',0)");
            
            //generate the product data record FOR NEW PRODUCTS
            $equery = $wpdb->query("INSERT INTO wp_atum_product_data (product_id,atum_controlled,inheritable) VALUES (".$prod->ID.",1,0)");
            

            //generate the meta data for the product
            update_post_meta($prod->ID,'_atum_manage_stock','yes');
            update_post_meta($prod->ID,'_multi_inventory','yes');
            update_post_meta($prod->ID,'_inventory_sorting_mode','global');            
            update_post_meta($prod->ID,'_inventory_iteration','global');          
            update_post_meta($prod->ID,'_expirable_inventories','global');
            update_post_meta($prod->ID,'_price_per_inventory','global');                          
        }
    }
}
}
add_action("gform_after_submission_47", "fe_process_atum_inventories", 10, 2);


// 2.15 RETUENS Admin section
//=========================================
function fe_returns_admin() {
global $wpdb;

$statuses = array(1 => "Archived",2 => "Exchange sent",3 => "Failed exchange",4 => "Fault refund processed",5 => "Fault exchange processed",6 => "Footplate returned",7 => "Refund for failed exchange",8 => "Refund processed",9 => "Request received",10 => "Request received",11 => "Request received – Hydra",12 => "Request received – BIOMAP",13 => "Return received at warehouse");
$eulist = array("BE","BG","CZ","DK","DE","EE","IE","EL","ES","FR","HR","IT","CY","LV","LT","LU","HU","MT","NL","AT","PL","PT","RO","SI","SK","FI","SE");



if ($_GET['orderby'] == '' ) {

    $ordering = 'id ASC';
    $orderbya = 'returnsiddesc';
    $orderbyb = 'orderidasc';
    $orderbyc = 'datestartedasc';    

} else if ($_GET['orderby'] == 'returnsidasc' ) {

    $ordering = 'returnsid ASC';
    $orderbya = 'returnsiddesc';
    $orderbyb = 'orderidasc';
    $orderbyc = 'datestartedasc';

} else if ($_GET['orderby'] == 'returnsiddesc' ) {

    $ordering = 'returnsid DESC';
    $orderbya = 'returnsidasc';
    $orderbyb = 'orderidasc';
    $orderbyc = 'datestartedasc';    

} else if ($_GET['orderby'] == 'orderidasc' ) {

    $ordering = 'orderid ASC';
    $orderbya = 'returnsidasc';
    $orderbyb = 'orderiddesc';
    $orderbyc = 'datestartedasc';    

} else if ($_GET['orderby'] == 'orderiddesc' ) {

    $ordering = 'orderid DESC';
    $orderbya = 'returnsidasc';
    $orderbyb = 'orderidasc';
    $orderbyc = 'datestartedasc';

} else if ($_GET['orderby'] == 'datestartedasc' ) {

    $ordering = 'datestarted ASC';
    $orderbya = 'returnsidasc';
    $orderbyb = 'orderidasc';
    $orderbyc = 'datestarteddesc';

} else if ($_GET['orderby'] == 'datestarteddesc' ) {

    $ordering = 'datestarted DESC';
    $orderbya = 'returnsidasc';
    $orderbyb = 'orderidasc';
    $orderbyc = 'datestartedasc';

}

$reasonCodes = array("1" => "Product looks different to appearance online","2" => "Incorrect item sent","3" => "Arrived too late","4" => "Item is Faulty","5" => "I ordered more than one size","6" => "It doesn’t fit me properly","7" => "It doesn’t suit me","8" => "I am returning a BIOMAP Footplate");

$getReturns = $wpdb->get_results("SELECT * FROM wp_fe_returns WHERE status <> 1 ORDER BY " . $ordering);

echo '<style>.close-view:hover { cursor:pointer;}</style><h1 style="margin:30px 0;">Fourth Element Returns Admin</h1>';

echo '<table style="margin-bottom:30px;" cellpadding="5"><tr><td>REGION: </td><td bgcolor="#f7a99f" width="5%"></td><td> UK Warehouse</td><td bgcolor="#9fa3f7" width="5%"></td><td> Euro Warehouse</td><td bgcolor="#f7f49f" width="5%"></td><td> USA Warehouse</td></tr></table>';

echo '<table border="1" cellpadding="5" cellspacing="0" style="width:95%;">
        <th width="12%">Submission ID</th>
        <th width="5%">Region</th>
        <th width="15%">Returns ID <a style="width: 15px;height: auto;float: right;padding-top: 2px;" id="returnsid-order" data-attribute="asc" href="/wp-admin/admin.php?page=returns-admin&orderby='.$orderbya.'"><img src="/wp-content/plugins/fourth-element-scripts/images/fe-arrows.png" height="15" width="15" /></a></th>
        <th width="10%">Order ID <a style="width: 15px;height: auto;float: right;padding-top: 2px;" id="orderid-order" data-attribute="asc" href="/wp-admin/admin.php?page=returns-admin&orderby='.$orderbyb.'"><img src="/wp-content/plugins/fourth-element-scripts/images/fe-arrows.png" height="15" width="15" /></a></th>
        <th width="20%">Date Return Submitted <a style="width: 15px;height: auto;float: right;padding-top: 2px;" id="dateopened-order" data-attribute="asc" href="/wp-admin/admin.php?page=returns-admin&orderby='.$orderbyc.'"><img src="/wp-content/plugins/fourth-element-scripts/images/fe-arrows.png" height="15" width="15" /></a></th>
        <th width="20%">Status</th><th width="20%" bgcolor="#59524c"></th></tr>';

foreach ( $getReturns as $returns ) {
    $atwarehouse = $billingcountry = '';
    $billingcountry = get_post_meta($returns->orderid,'_billing_country',true);

    $bgcolor='';
    if(strtotime($returns->datestarted) < strtotime('-28 days')) {
        $bgcolor = '#ff6666';
    } else if(strtotime($returns->datestarted) < strtotime('-20 days')) {
        $bgcolor = 'orange';
    }
    
    echo '<tr bgcolor='.$bgcolor.'><td align="center"><strong>'.$returns->id.'</strong> </td>';
    if ( in_array($billingcountry,$eulist) ) {
        $countrybg = '#9fa3f7';
    } else if ( $billingcountry == 'GB' ) {
        $countrybg = '#f7a99f';
    } else {
        $countrybg = '#f7f49f';
    }
    
    echo '<td align="center" bgcolor="'.$countrybg.'">'.$billingcountry.'</td>';
    echo '<td align="center">';
    
    
            
    if ($returns->returnsid == '' || $returns->returnsid == 0 ) {
        echo '<strong>Awaiting Returns ID</strong>';
    } else {
        echo '<strong>'.$returns->returnsid.'</strong>'; 
    }
    
    
    echo '</td><td align="center">'.$returns->orderid.'</td>
            <td align="center">'.date("d-m-Y", strtotime($returns->datestarted)).'</td>
            <td align="center">'.$statuses[$returns->status].'</td>';
                
                
    echo '</select></td><td align="center"><a name="vieworderdetails" id="vieworderdetails" href="?detail='.$returns->id.'">View Return Details</a></td></tr>';
    
    
    echo '<tr id="detail-'.$returns->id.'" style="display:none;"><td colspan="7">
    <form class="manage-record"><input type="hidden" name="returns-id" class="returns-id" value="'.$returns->id.'" />
    <input class="returns-status" type="hidden" name="returns-status" value="'.$returns->status.'" />
    <table width="100%" cellpadding="10"><tr valign="top"><td width="42%">
    <h3>Order ID: #'.$returns->orderid.'</h3>';
    if ($returns->returnsid == '' || $returns->returnsid == 0 ) {
        echo '<p>Enter Returns ID from Blade: (Please click SAVE RECORD to save ID)</p><input type="text" class="supply-returns-id" name="supply-returns-id" />';
    } else {
        echo '<p style="margin-top:30px;">Returns ID: <strong>'.$returns->returnsid.'</strong></p><input class="supply-returns-id" type="hidden" name="supply-returns-id" value="noreturnemail" />';
    }
    echo '<p>Order Date: '.date("d-m-Y", strtotime(get_post_meta($returns->orderid,'_paid_date',true))).'</p>
    <p>Customer Name: '.get_post_meta($returns->orderid,'_billing_first_name',true).' '.get_post_meta($returns->orderid,'_billing_last_name',true).'</p>
    <p>Correspondence Email: '.$returns->corresemail.'</p>
    <p>Item(s) being returned:</p>
        <ul>
            ';

        $skuList = unserialize($returns->skus);

        $itemtype = array();
        foreach ( $skuList as $skukey => $skuvalue) {
            
            $refundCheck = '';
            
            
            if ($skuvalue['refundneeded'] == "Yes") {
                $refundCheck = 'Yes';
            } else {
                $refundCheck = 'No';
            }
            echo '
            <li style="list-style:circle; margin-left:30px;">ITEM STATUS: <strong>'.$skuvalue['itemstatus'].'</strong></li>
            <li style="list-style:circle; margin-left:30px;">SKU: <strong>'.strtoupper($skukey).'</strong></li>
            <li style="list-style:circle; margin-left:30px;">Reason code: <strong>'.$skuvalue['reasoncode'].' '.$reasonCodes[$skuvalue['reasoncode']].'</strong></li>
            <li style="list-style:circle; margin-left:30px;">Refund requested: <strong>'.$refundCheck.'</strong></li>';

            if ($skuvalue['exchangesize'] && $skuvalue['exchangesize'] <> 'Select Size' ) {
                $itemtype[] = 'Exchange';
                echo '<li style="list-style:circle; margin-left:30px;">Size required for exchange: <strong>'.ucwords(strtolower($skuvalue['exchangesize'])).'</strong></li>';
            }
            
            if ( $skuvalue['reasonfault'] ) {
                echo '<li style="list-style:circle; margin-left:30px;">Fault Detail: <strong>'.str_replace("|","'",$skuvalue['reasonfault']).'</strong></li>';
            }
            
            if ( $skuvalue['refundneeded'] == 'Yes' && $skuvalue['emailsent'] <> 'completed' ) {
                $itemtype[] = 'Refund';
                echo '<p style="margin-top:30px;line-height: 1.8em;">Item Status Options:<br/><a class="subprocess" href="?statid='.$returns->id.'|'.$returns->returnsid.'|4|'.strtoupper($skukey).'">Fault Refund Processed</a><br/><a class="subprocess" href="?statid='.$returns->id.'|'.$returns->returnsid.'|8|'.strtoupper($skukey).'">Refund Processed</a></p>';
            }
            
            if ( $skuvalue['exchangesize'] && $skuvalue['exchangesize'] <> 'Select Size' && $skuvalue['emailsent'] <> 'completed' ) {
                echo '<p style="margin-top:30px;line-height: 1.8em;">Item Status Options:<br/><a class="subprocess" href="?statid='.$returns->id.'|'.$returns->returnsid.'|3|'.strtoupper($skukey).'">Failed Exchange</a><br/><a class="subprocess" href="?statid='.$returns->id.'|'.$returns->returnsid.'|5|'.strtoupper($skukey).'">Fault Exchange Processed</a><br/><a class="subprocess" href="?statid='.$returns->id.'|'.$returns->returnsid.'|7|'.strtoupper($skukey).'">Refund For Failed Exchange</a><br/><a class="subprocess" href="?statid='.$returns->id.'|'.$returns->returnsid.'|2|'.strtoupper($skukey).'">Exchange Sent</a></p>';
            }
        
            echo '<hr style="margin:30px 0;" />';
        }
        
    echo '</ul>';
    
    
    /*echo '<p>Return Status:</p>
    <select name="returns-status" id="returns-status" class="returns-status">
            ';    
                foreach ($statuses as $statusid => $status) {
                    $sel = '';
                    if ( $returns->status == $statusid ) {
                        $sel = 'selected';
                    }           
                    
                    if ( $statusid <> 9 ) {
                        echo '<option value="'.$statusid.'" '.$sel.'>'.$status.'</option>';
                    }
                }
                
    echo '</select></p>*/
    
    echo '</td><td>';
    
    if ( $returns->faultdetails ) {
        echo '<p style="margin-top:30px;">Fault Details:</p><p><em>'.$returns->faultdetails.'</em></p>';
    }
    
    echo '<p style="margin-top:30px;">Fourth Element Notes:</p>';
    
    if ($returns->notes) {
        echo '<textarea cols="100" rows="10" name="return-notes" class="return-notes">'.$returns->notes.'</textarea></td></tr>';
    } else {
        echo '<textarea cols="100" rows="10" name="return-notes" class="return-notes">There are currently no notes at present</textarea></td></tr>';
    }


    
    if ( !in_array('Exchange',$itemtype)) {
        echo '<tr><td colspan="2"><p style="margin:0 0 30px 0;">If you can refund the entire order instead of individual items, use one of these buttons:</p><p style="margin:30px 0;"> <a class="subprocess" style="text-decoration:none;background:#000;color:#fff;font-weight:bold;padding:10px 25px;" href="?statid='.$returns->id.'|'.$returns->returnsid.'|4">FAULT REFUND PROCESSED</a> <p style="margin:30px 0;"> <a class="subprocess" style="text-decoration:none;background:#000;color:#fff;font-weight:bold;padding:10px 25px;" href="?statid='.$returns->id.'|'.$returns->returnsid.'|8">REFUND PROCESSED</a></td></tr>';
        
    }
   
    $requestreceived = array(6,10,11,12);

    if ( in_array( (int)$returns->status, $requestreceived) ) { 
        $atwarehouse = '<p style="margin:0 0 30px 0;"> <a class="subprocess" style="text-decoration:none;background:#000;color:#fff;font-weight:bold;padding:10px 25px;" href="?statid='.$returns->id.'|'.$returns->returnsid.'|13">RETURN RECEIVED AT WAREHOUSE</a>';
    }
    
    echo '<tr><td colspan="2"><p style="margin:0 0 30px 0;">Main Record Options:</p> '.$atwarehouse.' <input type="submit" class="close-view" style="text-decoration:none;background:#59524c;color:#fff;font-weight:bold;padding:10px 25px;border:none;font-size:13px;" value="SAVE RECORD" /> <a class="subprocess" style="text-decoration:none;background:#000;color:#fff;font-weight:bold;padding:10px 25px;" href="?statid='.$returns->id.'|'.$returns->returnsid.'|1">ARCHIVE RETURN</a>  <a target="_blank" style="text-decoration:none;background:#000;color:#fff;font-weight:bold;padding:10px 25px;" href="/wp-admin/post.php?post='.$returns->orderid.'&action=edit">GO TO WOOCOMMERCE ORDER</a></p></td></tr></table>
    
    </form>
    </td></tr>';
    
    
}

echo    '</table>';
echo    '<script type="text/javascript">
        jQuery(document).ready(function() {
            
            var str = \'\';
            
            jQuery(document).on("click", "#vieworderdetails", function(e) {
                e.preventDefault();
                str = this.search.slice(8);
                jQuery(\'#detail-\'+str).toggle();
            });
            
            
            jQuery(document).on("click", ".subprocess", function(e) {
                e.preventDefault();
                str = this.search.slice(8).split(\'|\');
                var dataString = \'id=\'+str[0]+\'&returnsid=\'+str[1]+\'&status=\'+str[2]+\'&sku=\'+str[3];
                //alert(dataString);
                jQuery.ajax({
                    type: "GET",
                    url: window.location.protocol + "//" + window.location.hostname + "/wp-content/plugins/fourth-element-scripts/includes/update-return-record.php",
                    data: dataString,
                    dataType: "html",
                    beforeSend: function(){
                    },
                    success: function(sendResult){
                        jQuery(\'#detail-\'+str[0]).hide();
                    }
                });
                
            });            
            
            jQuery(\'.manage-record\').on("submit", function(e) {
                e.preventDefault();
                var form = jQuery(this);
                var id = form.find(\'.returns-id\').val();
                var notes = form.find(\'.return-notes\').val();
                var returnsid = form.find(\'.supply-returns-id\').val();
                var status = form.find(\'.returns-status\').val();
                
                var dataString = \'id=\'+id+\'&notes=\'+notes+\'&status=\'+status+\'&returnsid=\'+returnsid;
                //alert(dataString);
                jQuery.ajax({
                    type: "GET",
                    url: window.location.protocol + "//" + window.location.hostname + "/wp-content/plugins/fourth-element-scripts/includes/update-return-record.php",
                    data: dataString,
                    dataType: "html",
                    beforeSend: function(){
                    },
                    success: function(sendResult){
                        jQuery(\'#detail-\'+str).hide();
                    }
                });
            });
            
        });
        </script>';

    
}



// 2.15.1 Process Returns Entry
//=========================================================

function process_fe_return($entry, $form){
global $wpdb;
$skuarr = array();

$formid = $entry['id'];
$orderid = trim($entry['1'],'#');
$chkhydra = $entry['12'];
$corresemail = $entry['10'];

$eulist = array("BE","BG","CZ","DK","DE","EE","IE","EL","ES","FR","HR","IT","CY","LV","LT","LU","HU","MT","NL","AT","PL","PT","RO","SI","SK","FI","SE");
$shipCountry = get_post_meta($orderid,'_billing_country',true);

if ( in_array($shipCountry,$eulist) ) {
    $returnsNumber = 0;
} else {
    //generate unique reference number based on date and time
    $returnsNumber = strtotime(date('d-m-Y h:m:s'));
}
    
//get total number of items
$getItems = $wpdb->get_row("SELECT count(id) as reccount FROM wp_gf_entry_meta WHERE entry_id = $formid AND meta_key = 1001");
$totItems = $getItems->reccount - 1;

$skuarr = $reasoncode = array();
$w2route = array("2","4");
$w3route = array("1","3","5","6","7");
$w5route = array("8");



for ( $i=0; $i <= $totItems; $i++ ) {
    $status = $request = '';
    $getSku = $wpdb->get_row("SELECT meta_value FROM wp_gf_entry_meta WHERE entry_id = $formid AND meta_key = 1001 AND item_index = '_".$i."'");
    $getName = $wpdb->get_row("SELECT meta_value FROM wp_gf_entry_meta WHERE entry_id = $formid AND meta_key = 1002 AND item_index = '_".$i."'");    
    $getCode = $wpdb->get_row("SELECT meta_value FROM wp_gf_entry_meta WHERE entry_id = $formid AND meta_key = 1003 AND item_index = '_".$i."'");
    $reasoncode[] = $getCode->meta_value;
    $getRefund = $wpdb->get_row("SELECT meta_value FROM wp_gf_entry_meta WHERE entry_id = $formid AND meta_key = 1004 AND item_index = '_".$i."'");
    $getSize = $wpdb->get_row("SELECT meta_value FROM wp_gf_entry_meta WHERE entry_id = $formid AND meta_key = 1005 AND item_index = '_".$i."'");
    $getFault = $wpdb->get_row("SELECT meta_value FROM wp_gf_entry_meta WHERE entry_id = $formid AND meta_key = 1006 AND item_index = '_".$i."'");
    $getFault = str_replace("'","''",$getFault->meta_value);

    if ( $chkhydra == 'Yes' ) { 
        $status = 11;
        $request = 'Request Received - Hydra';
    } else if ( $chkhydra == 'No' ) {
        if ( in_array($reasoncode[0],$w2route) ) {
            $status = 9;
        } else if  ( in_array($reasoncode[0],$w3route) ) {
            $status = 10;
        } else if  ( in_array($reasoncode[0],$w5route) ) {
            $status = 12;
        }
        $request = 'Request Received';
    }
    
    $skuarr[strtoupper($getSku->meta_value)] = array('skuname'=>$getName->meta_value,'reasoncode'=>$getCode->meta_value,'refundneeded'=>$getRefund->meta_value,'exchangesize'=>$getSize->meta_value,'reasonfault'=>$getFault->meta_value,'itemstatus'=>$request,'emailsent'=>'notcompleted');
    
 
}



//insert returns record
$wpdb->query("INSERT INTO wp_fe_returns (returnsid,orderid,formentryid,datestarted,corresemail,status,skus) VALUES ($returnsNumber,$orderid,$formid,'".date('Y-m-d')."','".$corresemail."','".$status."','".serialize($skuarr)."')");

$message = print_r($entry, true);
$message = wordwrap($message, 70);
wp_mail('darren.shilson@fourthelement.com', 'Getting the Gravity Form Field IDs - Returns Submission', $message);

    
}
add_action("gform_after_submission_57", "process_fe_return", 10, 2);




// 2.16 Load classes to populate products from Stockfeed DB
//=========================================================

function chk_product_feed($sku = '', $filter = '') {
global $wpdb2;
    $wpdb2 = new WPDB(FEEDUSER, FEEDPASS, FEEDDB, FEEDHOST);
    $features = $fabric = '';

    if ( $filter == '') {
        $getProductDetails = $wpdb2->get_row("SELECT * FROM fesf_product_brochureware WHERE sku = '".$sku."'");
    } else if ($filter == 'cross') {
        $getProductDetails = $wpdb2->get_row("SELECT cross_sell_skus FROM fesf_product_brochureware WHERE sku = '".$sku."'");
    } else if ($filter == 'complete') {
        $getProductDetails = $wpdb2->get_row("SELECT complete_set_skus FROM fesf_product_brochureware WHERE sku = '".$sku."'");
    } else if ($filter == 'colour') {
        $getProductDetails = $wpdb2->get_row("SELECT colour FROM fesf_product_brochureware WHERE sku = '".$sku."'");
    }
    
    $prodArr = array();
    
    if ( $wpdb2->num_rows > 0 ) {
        
        if ( $filter == '') {
            $prodArr['name'] = $getProductDetails->name;
            $prodArr['colour'] = $getProductDetails->colour;
            $prodArr['description'] = $getProductDetails->description;
            $prodArr['topimg'] = $getProductDetails->topimg;
            $prodArr['topimgw'] = $getProductDetails->topimgw;        
            $prodArr['topimgh'] = $getProductDetails->topimgh;
            $prodArr['topimgalt'] = $getProductDetails->topimgalt;
        
            if ( $getProductDetails->key_features_a ) { $features .= $getProductDetails->key_features_a.'<br/>'; }
            if ( $getProductDetails->key_features_b ) { $features .= $getProductDetails->key_features_b.'<br/>'; }
            if ( $getProductDetails->key_features_c ) { $features .= $getProductDetails->key_features_c.'<br/>'; }
            if ( $getProductDetails->key_features_d ) { $features .= $getProductDetails->key_features_d.'<br/>'; }
            if ( $getProductDetails->key_features_e ) { $features .= $getProductDetails->key_features_e.'<br/>'; }
            if ( $getProductDetails->key_features_f ) { $features .= $getProductDetails->key_features_f.'<br/>'; }
            if ( $getProductDetails->key_features_g ) { $features .= $getProductDetails->key_features_g.'<br/>'; }
            if ( $getProductDetails->key_features_h ) { $features .= $getProductDetails->key_features_h.'<br/>'; }
            if ( $getProductDetails->key_features_i ) { $features .= $getProductDetails->key_features_i.'<br/>'; }
            if ( $getProductDetails->key_features_j ) { $features .= $getProductDetails->key_features_j; }
            $prodArr['features'] = $features;
        
            if ( $getProductDetails->fabric_care_a ) { $fabric .= $getProductDetails->fabric_care_a.'<br/>'; }
            if ( $getProductDetails->fabric_care_b ) { $fabric .= $getProductDetails->fabric_care_b.'<br/>'; }
            if ( $getProductDetails->fabric_care_c ) { $fabric .= $getProductDetails->fabric_care_c.'<br/>'; }
            $prodArr['fabric'] = $fabric;
        
            $prodArr['fit'] = $getProductDetails->fit;
            $prodArr['size'] = $getProductDetails->size;
            $prodArr['origin'] = $getProductDetails->origin;
            $prodArr['ingredients'] = $getProductDetails->ingredients;
        
            $prodArr['crosssell'] = $getProductDetails->cross_sell_skus;
            $prodArr['completeset'] = $getProductDetails->complete_set_skus;
        
        } else if ($filter == 'cross') {
            $prodArr['crosssell'] = $getProductDetails->cross_sell_skus;
        } else if ($filter == 'complete') {
            $prodArr['completeset'] = $getProductDetails->complete_set_skus; 
        } else if ($filter == 'colour') {
            $prodArr['colour'] = $getProductDetails->colour; 
        }
        
        return $prodArr;
        
    } else {
        return 0;
    }
}



class ProductFeed {

private $wpdb2;
private $sku;
private $filter;
private $prodArr;
private $name;
private $colour;
private $fit;
private $description;
private $topimg;
private $topimgw;
private $topimgh;
private $topimgalt;
private $features;
private $fabric;
private $size;
private $origin;
private $ingredients;
private $crosssell;
private $completeset;

public function __construct()
{
    global $wpdb2;
    $wpdb2 = new WPDB(FEEDUSER, FEEDPASS, FEEDDB, FEEDHOST);
    $this->wpdb = $wpdb2;
}

public function product_results($sku,$filter = ''){
    $product_table = "fesf_product_brochureware";
    $this->sku = trim($sku);
    $this->filter = trim($filter);
    $features = $fabric = '';

    if ( $this->filter == '') {
        $product = $this->wpdb->get_row("SELECT * FROM $product_table WHERE sku = '".$this->sku."'");
    } else if ($this->filter == 'cross') {
        $product = $this->wpdb->get_row("SELECT cross_sell_skus FROM $product_table WHERE sku = '".$this->sku."'");
    } else if ($this->filter == 'complete') {
        $product = $this->wpdb->get_row("SELECT complete_set_skus FROM $product_table WHERE sku = '".$this->sku."'");
    } else if ($this->filter == 'colour') {
        $product = $this->wpdb->get_row("SELECT colour FROM $product_table WHERE sku = '".$this->sku."'");
    }

    $prodArr = array();
    
        if ( $filter == '') {
        
            $this->name = $product->name;
            $this->colour = $product->colour;
            
            $this->description = $product->description;
            $this->topimg = $product->topimg;
            $this->topimgw = $product->topimgw;        
            $this->topimgh = $product->topimgh;
            $this->topimgalt = $product->topimgalt;
        
            if ( $product->key_features_a ) { $features .= trim($product->key_features_a).'<br/>'; }
            if ( $product->key_features_b ) { $features .= trim($product->key_features_b).'<br/>'; }
            if ( $product->key_features_c ) { $features .= trim($product->key_features_c).'<br/>'; }
            if ( $product->key_features_d ) { $features .= trim($product->key_features_d).'<br/>'; }
            if ( $product->key_features_e ) { $features .= trim($product->key_features_e).'<br/>'; }
            if ( $product->key_features_f ) { $features .= trim($product->key_features_f).'<br/>'; }
            if ( $product->key_features_g ) { $features .= trim($product->key_features_g).'<br/>'; }
            if ( $product->key_features_h ) { $features .= trim($product->key_features_h).'<br/>'; }
            if ( $product->key_features_i ) { $features .= trim($product->key_features_i).'<br/>'; }
            if ( $product->key_features_j ) { $features .= trim($product->key_features_j); }
            $this->features = $features;
            //error_log($this->features);
        
            if ( $product->fabric_care_a ) { $fabric .= trim($product->fabric_care_a).'<br/>'; }
            if ( $product->fabric_care_b ) { $fabric .= trim($product->fabric_care_b).'<br/>'; }
            if ( $product->fabric_care_c ) { $fabric .= trim($product->fabric_care_c).'<br/>'; }
            $this->fabric = $fabric;
        
            $this->fit = $product->fit;
            $this->size = $product->size;
            $this->origin = $product->origin;
            $this->ingredients = $product->ingredients;
        
            $this->crosssell = $product->cross_sell_skus;
            $this->completeset = $product->complete_set_skus;
        
        } else if ($filter == 'cross') {
            $this->crosssell = $product->cross_sell_skus;
        } else if ($filter == 'complete') {
            $this->completeset = $product->complete_set_skus; 
        } else if ($filter == 'colour') {
            $this->colour = $product->colour; 
        }
    
}

public function fe_get_product() {
    //return $prodArr;
    if ( $this->filter == '') {
        return array('name'=>$this->name,'colour'=>$this->colour,'description'=>$this->description,'topimg'=>$this->topimg, 'topimgw'=>$this->topimgw, 'topimgh'=>$this->topimgh, 'topimgalt'=>$this->topimgalt, 'features'=>$this->features,'fabric'=>$this->fabric, 'fit'=>$this->fit, 'size'=>$this->size, 'origin'=>$this->origin, 'ingredients'=>$this->ingredients, 'crosssell'=>$this->crosssell, 'completeset'=>$this->completeset);
    } else if ($this->filter == 'cross') {
        return array('crosssell'=>$this->crosssell);
    } else if ($this->filter == 'complete') { 
        return array('completeset'=>$this->completeset);
    } else if ($this->filter == 'colour') {
        return array('colour'=>$this->colour);
    }
}
}

// 2.17 Manage FE Team
//=========================================

function fe_manage_fe_team() {
global $wpdb;
$getNames = $wpdb->get_results("SELECT * FROM wp_fe_team ORDER BY firstname ASC");
$frm = '<h1>Manage Fourth Element Team</h1><hr/><h3>A) (EDIT) Select the employee from the list below to edit</h3>
<form id="feteam"><select id="feemployee" name="feemployee"><option value="">Select Employee</option>';
    foreach ( $getNames as $name ) {
        $frm .= '<option value="'.$name->id.'">'.$name->firstname.' '.$name->lastname.'</option>';
    }
$frm .= '</select></form><div class="memberfrmmgs"></div><div id="memberfrm" class="gform_wrapper"></div>';

$frm .= '<script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery("select#feemployee").on(\'change\',function (e) {
                e.preventDefault();
                var femember = jQuery("select#feemployee").val();
                var dataString = \'femember=\'+femember;
                jQuery.ajax({
                        type: "GET",
                        url: window.location.protocol + "//" + window.location.hostname + "/wp-content/plugins/fourth-element-scripts/includes/load-fe-team-member.php",
                        data: dataString,
                        dataType: "html",
                        beforeSend: function(){
                        },
                        success: function(sendResult){
                            var memberbox = sendResult.split("|"), a = memberbox[0], b = memberbox[1], c = memberbox[2], d = memberbox[3], e = memberbox[4], f = memberbox[5], g = memberbox[6], h = memberbox[7], i = memberbox[8];
                        
                            jQuery("#memberfrm").html(\'<p style="color:#ff0000;" class="remlnk">Click <a href="?femember=\'+femember+\'" class="removemember" style="color:#ff0000;">here to delete</a> this team member from the page</p><form id="updatemember" style="margin-top:30px;"><input type="hidden" name="edit_id" value=""/><ul class="gform_fields top_label form_sublabel_below description_below"><li id="edit_1" class="gfield field_sublabel_below field_description_below gfield_visibility_visible"><label class="gfield_label gfield_label_before_complex"><strong>Name</strong></label><div class="ginput_complex ginput_container no_prefix has_first_name no_middle_name has_last_name no_suffix gf_name_has_2 ginput_container_name" id="edit_1_1"><span id="edit_1_1_1_container" class="name_first"><input style="margin-top: 10px;" type="text" name="edit_1_1" id="edit_1_1_1" value="" aria-label="First name" aria-invalid="false" ><label style="margin:0 10px 0 5px" for="edit_1_1_1">First</label></span><span id="edit_1_1_1_container" class="name_last"><input style="margin-top: 10px;" type="text" name="edit_1_2" id="edit_1_2" value="" aria-label="Last name" aria-invalid="false"><label style="margin:0 10px 0 5px" for="edit_1_1_2">Last</label></span></div></li><li id="edit_1_3" class="gfield field_sublabel_below field_description_below gfield_visibility_visible"><label class="gfield_label" for="input_19_3"><strong>Email</strong></label><div class="ginput_container ginput_container_email"><input style="margin-top: 10px;" size="50" name="edit_1_3" id="edit_1_3" type="text" value="" class="medium" aria-invalid="false"></div></li><li id="edit_1_4" class="gfield gfield_contains_required field_sublabel_below field_description_below gfield_visibility_visible"><label class="gfield_label" for="input_19_2"><strong>Show Email as MAILTO: link</strong><span class="gfield_required">*</span></label><div class="ginput_container ginput_container_select"><select  style="margin-top: 10px;" name="edit_1_4" id="edit_1_4" class="medium gfield_select" aria-required="true" aria-invalid="false"><option value="">Select</option><option value="on">Yes</option><option value="off">No</option></select></div></li><li id="edit_1_5" class="gfield field_sublabel_below field_description_below gfield_visibility_visible"><label class="gfield_label" for="input_19_5"><strong>Office</strong></label><br><p><sub>Select from:<br/>uk (United Kingdom)<br/>usa (North America)<br/>asia (Asia)<br/>aus (Australia)<br/>row (Rest of World)<br/>Separate multiple offices with comma, no spaces e.g. uk,usa</sub></p><div class="ginput_container ginput_container_email"><input name="edit_1_5" id="edit_1_5" type="text" value="" class="medium" aria-invalid="false"></div></li><li id="edit_1_6" class="gfield field_sublabel_below field_description_below gfield_visibility_visible"><label class="gfield_label" for="input_19_6"><strong>Job Title</strong></label><div class="ginput_container ginput_container_website"><input style="margin-top: 10px;" name="edit_1_6" id="edit_1_6" type="text" value="" class="medium" placeholder="" aria-invalid="false"></div></li><li id="edit_1_7" class="gfield field_sublabel_below field_description_below gfield_visibility_visible"><label class="gfield_label" for="input_19_7"><strong>Image URL</strong></label><div class="ginput_container ginput_container_image"><input name="edit_1_7" id="edit_1_7" type="text" size="100" value="" class="medium" placeholder="" aria-invalid="false"></div></li><li id="edit_1_8" class="gfield field_sublabel_below field_description_below gfield_visibility_visible"><label class="gfield_label" for="input_19_8"><strong>Bio</strong></label><div class="ginput_container ginput_container_textarea"><textarea style="margin-top:10px" name="edit_1_8" id="edit_1_8" class="textarea medium" aria-invalid="false" rows="10" cols="50"></textarea></div></li></ul><br/><input type="submit" value="UPDATE MEMBER"/></form>\');
                            
                            jQuery(\'input[name=edit_1_1]\').val(a);
                            jQuery(\'input[name=edit_1_2]\').val(b);
                            jQuery(\'input[name=edit_1_3]\').val(c);
                            jQuery(\'select[name=edit_1_4]\').val(d);
                            jQuery(\'input[name=edit_1_5]\').val(e);
                            jQuery(\'input[name=edit_1_6]\').val(f);
                            jQuery(\'input[name=edit_1_7]\').val(g);
                            jQuery(\'textarea[name=edit_1_8]\').val(h);
                            jQuery(\'input[name=edit_id]\').val(i);
                        }
                });
            });
            
            jQuery(document).on("click", ".removemember", function(e) {
                e.preventDefault();
                var str = this.search.slice(10);
                jQuery.ajax({
                    type: "GET",
                    url: window.location.protocol + \'//\' + window.location.hostname + \'/wp-content/plugins/fourth-element-scripts/includes/delete-member.php\',
                    data: {getSalt: str },
                    dataType: "html",
                    success: function(result){
                        jQuery(\'#updatemember\').hide();
                        jQuery(\'.remlnk\').hide();
                        jQuery(\'.memberfrmmgs\').html(\'<p style="color:#22a603;">Team member has been removed!</p>\');
                    }
                });
            });
            
            jQuery("#memberfrm").submit("#updatemember",function(e){   
                e.preventDefault();
                var id = jQuery(\'input[name=edit_id]\').val();                
                var firstname = jQuery(\'input[name=edit_1_1]\').val();
                var lastname = jQuery(\'input[name=edit_1_2]\').val();   
                var email = jQuery(\'input[name=edit_1_3]\').val();
                var showemail = jQuery(\'select[name=edit_1_4]\').val();
                var office = jQuery(\'input[name=edit_1_5]\').val();
                var jobtitle = jQuery(\'input[name=edit_1_6]\').val();
                var imgurl = jQuery(\'input[name=edit_1_7]\').val();   
                var bio = jQuery(\'textarea[name=edit_1_8]\').val();

                var dataString = \'id=\'+id+\'&firstname=\'+firstname+\'&lastname=\'+lastname+\'&email=\'+email+\'&showemail=\'+showemail+\'&office=\'+office+\'&jobtitle=\'+jobtitle+\'&imgurl=\'+imgurl+\'&bio=\'+bio;
                //alert(dataString);
                jQuery.ajax({
                        type: "POST",
                        url: window.location.protocol + "//" + window.location.hostname + "/wp-content/plugins/fourth-element-scripts/includes/update-member.php",
                        data: dataString,
                        dataType: "html",
                        beforeSend: function(){
                        },
                        success: function(sendResult){
                            jQuery(\'#updatemember\').hide();
                            jQuery(\'.remlnk\').hide();
                            jQuery(\'.memberfrmmgs\').html(\'<p style="color:#22a603;">Member has been updated!</p>\');
                        }
                });
            });
            
        });
        </script>';            

$frm .= '<br/><hr/><h3>B) (ADD) Add NEW Team Member</h3>';
$frm .= '<div style="width:60%;" id="addnewdiver">'.do_shortcode('[gravityform id="48" title="false" description="false" ajax="true"]').'</div>';

echo $frm;    
    
}

// 2.17.1 Process Add New Team Member
//===========================================
function fe_add_fe_team_member($entry, $form) {
global $wpdb, $woocommerce, $product; 
    
    $firstname = $entry['1.3'];
    $lastname = $entry['1.6'];    
    $email = $entry['2'];
    $showemail = strtr($entry['3'],array('["'=>'','"]'=>''));
    $office = strtr($entry['4'],array('["'=>'','"]'=>'','"'=>''));
    $jobtitle = $entry['6'];
    $bio = $entry['7'];    
    $image = $entry['9'];    
    
    $lastname = str_replace("'","''",$lastname);
    
    $message = print_r($entry, true);
    $message = wordwrap($message, 70);
    wp_mail('darren.shilson@fourthelement.com', 'Getting the Gravity Form Field IDs - Team Diver', $message);
    
    $query = $wpdb->query("INSERT INTO wp_fe_team (firstname,lastname,email,emailonoff,office,jobtitle,imgurl,bio) VALUES ('".$firstname."','".$lastname."','".$email."','".$showemail."','".$office."','".$jobtitle."','".$bio."','".$image."')");
}
add_action("gform_after_submission_48", "fe_add_fe_team_member", 10, 2);  


// 2.18 Set Outlet <-> Sale
//===========================================
function fe_outlet_to_sale() {
global $wpdb;
//sale set option
$saleSet = (int)get_option('fe_outlet_to_sale');

$outSale = '<h1>Set Outlet <-> Sale</h1>';

if ( $saleSet == 1 ) {
    $outSale .= '<p>The Outlet categories are currently set to Sale, do you wish to cancel this?</p><p><a style="background:#000;padding:5px 7px; color:#fff;cursor:pointer;" id="fecanceloutletsale">CANCEL SALE</a></p><p class="sale-result"></p>';
    $outSale .= '<script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery(document).on("click", "#fecanceloutletsale", function(e) {
                e.preventDefault();
                var str = \'exitsale\';
                jQuery.ajax({
                    type: "GET",
                    url: window.location.protocol + \'//\' + window.location.hostname + \'/wp-content/plugins/fourth-element-scripts/includes/cancel-outlet-sale.php\',
                    data: {getSalt: str },
                    dataType: "html",
                    success: function(result){

                        jQuery(\'#fecanceloutletsale\').hide();
                        jQuery(\'.sale-result\').html(\'<p style="color:#22a603;">Sale has ended!</p>\');
                    }
                });
            });
        });
        </script>'; 
} else {
    $outSale .= '<p>Complete this simple form to swap the Outlet categories to Sale categories</p>
        <div id="setoutletsale"><form id="maketheoutletsaleswap">
        <ul style="list-style:none;">
        <li><p>See this screenshot for location of ID for field below<br/><img style="width:400px;height:auto;" src="/wp-content/plugins/fourth-element-scripts/images/attachmentid.gif"/><br/><label for="cateidbanner">Enter the ID number of the Category Banner you wish to use:</label><br/><input style="margin:10px 0;width:500px;" type="text" name="cateidbanner" /></li>
        <li><label for="catestrapline">Enter a strapline you wish to use: (optional)(no ampersands!)</label><br/><input style="margin:10px 0;width:500px;" type="text" name="catestrapline" /></li>
        <li><label for="catedesc">Enter the description you wish to use: (no html)(no ampersands!)</label><br/><textarea name="catedesc" rows="5" cols="73"></textarea></li>
        </ul>
        <p><strong>When you submit the form, the categories are changed with immediate effect and the text supplied is applied to all of the categories. Please check the text for typos, etc.</strong></p>
        <p><strong>When you are ready, click this button.</strong></p>
        <input style="border:#000;background:#000;padding:5px 7px; color:#fff;cursor:pointer;" type="submit" name="submitsaleawap" value="SWAP CATEGORIES" />
        </form></div>';

    $outSale .= '<script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery("#setoutletsale").submit("#maketheoutletsaleswap",function(e){   
                e.preventDefault();
                var imgid = jQuery(\'input[name=cateidbanner]\').val();                
                var strapline = jQuery(\'input[name=catestrapline]\').val();
                var description = jQuery(\'textarea[name=catedesc]\').val();
                var dataString = \'imgid=\'+imgid+\'&strapline=\'+strapline+\'&description=\'+description;
                jQuery.ajax({
                        type: "POST",
                        url: window.location.protocol + "//" + window.location.hostname + "/wp-content/plugins/fourth-element-scripts/includes/setup-outlet-sale.php",
                        data: dataString,
                        dataType: "html",
                        beforeSend: function(){
                        },
                        success: function(sendResult){
                            jQuery(\'#maketheoutletsaleswap\').hide();
                            jQuery(\'#setoutletsale\').html(\'<p style="color:#22a603;">Sale has started!</p>\');
                        }
                });
            });
        });
        </script>'; 
}

echo $outSale;
}

// 2.19 Export refunds and reasons
// =============================================================================

function fe_refund_exports() {
    
    echo '<h1>Export REFUND data</h1><p>Use the form below to set your then and now dates. This will provide a downloadable CSV file based on midnight timings (at present) between the two dates.</p>';

    echo '<form id="refund-export">
     <label for "fromdate" style="width:100px;display:inline-block;">From:</label> <input type="text" name="fromdt" placeholder="DD-MM-YYYY" /><br/>
    <label for "todate" style="width:100px;display:inline-block;">To:</label> <input type="text" name="todt" placeholder="DD-MM-YYYY" /><br/>
        <input type="submit" value="EXPORT REFUNDS" style="margin-top:20px;" />
    </form>';
    echo '<div style="clear:both;margin-top:20px;display:none;" class="download"></div>';
    
        echo '
        <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery(\'form#refund-export\').submit(function(e){ 
                    e.preventDefault();
                    var fromdt = jQuery(\'input[name=fromdt]\').val();
                    var todt = jQuery(\'input[name=todt]\').val();
                    var dataString = \'thendt=\'+fromdt+\'&nowdt=\'+todt;
                    

                    jQuery.ajax({
                        type: "GET",
                        url: window.location.protocol + "//" + window.location.hostname + "/wp-content/themes/fourth-element-child/refunds.php",
                        data: dataString,
                        dataType: "html",
                        beforeSend: function(){
                            jQuery(".download").hide();
                        },
                        success: function(result){                             
                            jQuery(".download").show();
                            jQuery(".download").html(result);
                        }
                    });
                });
            });
        </script>';
    
}