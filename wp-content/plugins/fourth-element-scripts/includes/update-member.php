<?php 
include(__DIR__ . '../../../../../wp-config.php');
global $wpdb;
$form_fields = array('id','firstname','lastname','email','showemail','office','jobtitle','imgurl','bio');

// Entify each field value to go some way to prevent oddities and hacks
foreach ( $form_fields as $this_field_name ) {
    $this_field_value = trim($_POST[$this_field_name]);
        if ( $this_field_value ) {
            $GLOBALS[$this_field_name] = htmlentities(str_replace('\\','',$this_field_value),ENT_QUOTES,'UTF-8');
        } else {
            $GLOBALS[$this_field_name] = NULL;
        }
}

$lastname = str_replace("'","''",$lastname);
$bio = str_replace("'","''",$bio);

$result = $wpdb->query("UPDATE wp_fe_team SET firstname = '".$firstname."', lastname = '".$lastname."', email = '".$email."', emailonoff = '".$showemail."', office = '".$office."', jobtitle = '".$jobtitle."', imgurl = '".$imgurl."', bio = '".$bio."' WHERE id = ".$id."");
print_r($result);