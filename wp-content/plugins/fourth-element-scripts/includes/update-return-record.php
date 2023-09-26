<?php 
include(__DIR__ . '../../../../../wp-config.php');
global $wpdb;
ob_start();
require_once( "../../../themes/fourth-element-child/library/fpdf/fpdf.php" );
class PDF extends FPDF
{
protected $col = 0; // Current column
protected $y0;      // Ordinate of column start
protected $B = 0;
protected $I = 0;
protected $U = 0;
protected $HREF = '';

function SetStyle($tag, $enable)
    {
        // Modify style and select corresponding font
        $this->$tag += ($enable ? 1 : -1);
        $style = '';
        foreach(array('B', 'I', 'U') as $s)
        {
            if($this->$s>0)
                $style .= $s;
        }
        $this->SetFont('',$style);
    }

function ResourceBody($file)
    {
        // Read text file
        $txt = file_get_contents($file);
        // Font
        $this->SetFont('Arial','',10);
        // Output text in a 6 cm width column
        $this->MultiCell(160,4,$txt);
        $this->Ln();

    }

    function PrintResource($num, $title, $file)
    {
        // Add chapter
        $this->AddPage();
        $this->ResourceBody($file);
    }

}

$id = $_GET['id'];
$notes = $_GET['notes'];
$status = $_GET['status'];
$returnsid = $_GET['returnsid'];
$sku = $_GET['sku'];



if ( $sku <> 'undefined' ) {
    $prodName = $wpdb->get_row("SELECT post_title FROM wp_posts WHERE id = ".wc_get_product_id_by_sku($sku)."");
    $prodLine = 'Product: '. $sku .'/'.$prodName->post_title;
}

$statuses = array(1 => "Archived",2 => "Exchange sent",3 => "Failed exchange",4 => "Fault refund processed",5 => "Fault exchange processed",6 => "Footplate returned",7 => "Refund for failed exchange",8 => "Refund processed",9 => "Request received",10 => "Request received",11 => "Request received – Hydra",12 => "Request received – BIOMAP",13 => "Return received at warehouse");

//get personal bits
$eulist = array("BE","BG","CZ","DK","DE","EE","IE","EL","ES","FR","HR","IT","CY","LV","LT","LU","HU","MT","NL","AT","PL","PT","RO","SI","SK","FI","SE");

$getcurrStatus = $wpdb->get_row("SELECT orderid,corresemail,status from wp_fe_returns WHERE id = ".$id."");
$firstname = get_post_meta($getcurrStatus->orderid,'_billing_first_name',true);
//get country so we can show correct delivery label
$shipCountry = get_post_meta($getcurrStatus->orderid,'_billing_country',true);

$emailheader = '<table width="100%"><tr valign="top"><td align="center"><table width="660"><tr><td align="center" colspan="2"><img src="http://fourthelement.com/images/fe-logo.png" width="400" height="130" alt="Fourth Element Logo" /></td></tr><tr><td><p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">Dear '.$firstname.',</p>';
$emailfooter = '<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">Kind regards</p><p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">The Fourth Element Team</p></td></tr></table><br/><br/><hr/><br/><table width="660"><tr><td><p align="center" style="margin:20px 0 10px 0;"><a style="text-decoration:none;font-size:14px;color:#596670;" href="http://fourthelement.com" title="Fourth Element website">fourthelement.com</a></p></td></tr><tr><td align="center"><p align="center"><a href="https://www.facebook.com/fourthelementdive/" title="Fourth Element on Facebook"><img src="https://fourthelement.com/wp-content/themes/fourth-element-child/library/images/facebook-email-icon.png" alt="Facebook icon" width="40" height="40"></a> <a href="https://www.instagram.com/fourthelementdive" title="Fourth Element on Instagram"><img src="https://fourthelement.com/wp-content/themes/fourth-element-child/library/images/instagram-email-icon.png" alt="Facebook icon" width="40" height="40"></a> <a href="https://twitter.com/fourth_element" title"Fourth Element on Twitter"><img src="https://fourthelement.com/wp-content/themes/fourth-element-child/library/images/twitter-email-icon.png" alt="Facebook icon" width="40" height="40"></a></p></td></tr></table></td></tr></table>';


if ( $getcurrStatus->status == $status ) {
    error_log('status stayed the same');
} else {

    if ( $status == 6) { // Email 13
        
        $statusEmail = $emailheader.'<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">Thank you for returning your BIOMAP footplate to us.</p>
        <p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">If you have ordered an Argonaut drysuit from us, you will receive updates from our team as your suit is built.</p>
        <p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">Meanwhile, if you have any questions regarding the Argonaut, please email us at <a href="mailto:argonaut@fourthelement.com">argonaut@fourthelement.com</a></p>'.$emailfooter;
        
        $headers  = "Fourth Element <info@fourthelement.com>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html\r\n";
        wp_mail($getcurrStatus->corresemail, 'Returned Footplate', $statusEmail,'From: '.$headers.'');
    
    } else if ( $status == 13 ) { // Email 9
        
        $statusEmail = $emailheader.'<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">Thank you for returning your item(s) to us.</p>';
        
        if ( $sku ) {
            $statusEmail .= '<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">'.$prodLine.'</p>';
        }        
        
        $statusEmail .= '<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">We just wanted to let you know that your return has been received at fourth element and we will process it within 2 working days.</p>'.$emailfooter;
        
        $headers  = "Fourth Element <info@fourthelement.com>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html\r\n";
        wp_mail($getcurrStatus->corresemail, 'Your fourth element return request – Goods Received', $statusEmail,'From: '.$headers.'');
        
    } else if ( $status == 5 ) { // Email 8
        
        $statusEmail = $emailheader.'<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">Thank you for returning your item(s) to us for an exchange.  We are sorry that the product did not meet your expectations.</p>';
        
        if ( $sku ) {
            $statusEmail .= '<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">'.$prodLine.'</p>';
        }        
        
        $statusEmail .= '<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">This has now been processed and you should receive your replacement product shortly.</p>
        <p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">If you paid for the return shipping, this should have been refunded to you and you should see the money in your account within 3 working days.</p>
        <p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">If it has not been refunded to you please let us know by replying to this email and attaching a copy of your shipping invoice/receipt.</p>
        <p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">Thank you for choosing fourth element and we hope to see you again soon.</p>'.$emailfooter;
        
        $headers  = "Fourth Element <info@fourthelement.com>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html\r\n";
        wp_mail($getcurrStatus->corresemail, 'Your fourth element return request – Replacement Processed', $statusEmail,'From: '.$headers.'');
        
    } else if ( $status == 4 ) { // Email 11
        
        $statusEmail = $emailheader.'<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">Thank you for returning your item(s) to us for a refund.  We are sorry that the product did not meet your expectations.</p>';
        
        if ( $sku ) {
            $statusEmail .= '<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">'.$prodLine.'</p>';
        }        
        
        $statusEmail .= '<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">This has now been processed and you should see the money in your account within 3 working days.</p>
        <p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">If you paid for the return shipping, this should have been refunded to you also.  If it has not been refunded to you please let us know by replying to this email and attaching a copy of your shipping invoice/receipt.</p>
        <p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">Thank you for choosing fourth element and we hope to see you again soon.</p>'.$emailfooter;
        
        $headers  = "Fourth Element <info@fourthelement.com>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html\r\n";
        wp_mail($getcurrStatus->corresemail, 'Your fourth element return request – Refund Processed', $statusEmail,'From: '.$headers.'');
        
    } else if ( $status == 3 ) { // Email 5
        
        $statusEmail = $emailheader.'<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">Thank you for submitting an exchange request to us.</p>';
        
        if ( $sku ) {
            $statusEmail .= '<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">'.$prodLine.'</p>';
        }        
        
        $statusEmail .= '<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">Unfortunately, the item or size that you have requested in exchange is no longer in stock and as a result, we will not be able to process this exchange.  As a result, when we receive your return, we will issue a refund to you and you should see the money in your account within 3 working days of receiving your returned goods.</p>
        <p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">We are sorry for the inconvenience this may cause, and we thank you for choosing fourth element and we hope to see you again soon.</p>'.$emailfooter;
        
        $headers  = "Fourth Element <info@fourthelement.com>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html\r\n";
        wp_mail($getcurrStatus->corresemail, 'Your fourth element return request – Problem with your exchange', $statusEmail,'From: '.$headers.'');
        
    } else if ( $status == 7 ) { // Email 6
        
        $statusEmail = $emailheader.'<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">Thank you for returning your item(s) to us.</p>';
        
        if ( $sku ) {
            $statusEmail .= '<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">'.$prodLine.'</p>';
        }        
        
        $statusEmail .= '<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">As we were unable to exchange your order due to this item no longer being in stock, we have processed a refund and you should see the money in your account within 3 working days.</p>
        <p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">We are sorry for the inconvenience this may have caused, and we thank you for choosing fourth element and we hope to see you again soon.</p>'.$emailfooter;
        
        $headers  = "Fourth Element <info@fourthelement.com>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html\r\n";
        wp_mail($getcurrStatus->corresemail, 'Your fourth element return request – Refund Processed', $statusEmail,'From: '.$headers.'');
        
    } else if ( $status == 2 ) { // Email 7
        
        $statusEmail = $emailheader.'<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">Thank you for returning your item(s) to us for an exchange.</p>';
        
        if ( $sku ) {
            $statusEmail .= '<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">'.$prodLine.'</p>';
        }        
        
        $statusEmail .= '<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">This has now been processed and you should receive your replacement product shortly.</p>
        <p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">Thank you for choosing fourth element and we hope to see you again soon.</p>'.$emailfooter;
        
        $headers  = "Fourth Element <info@fourthelement.com>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html\r\n";
        wp_mail($getcurrStatus->corresemail, 'Your fourth element return request – Exchange Processed', $statusEmail,'From: '.$headers.'');
        
    } else if ( $status == 8 ) { // Email 4
        
        $statusEmail = $emailheader.'<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">Thank you for returning your item(s) to us for a refund.</p>';
        
        if ( $sku ) {
            $statusEmail .= '<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">'.$prodLine.'</p>';
        }
        
        $statusEmail .= '<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">This has now been processed and you should see the money in your account within 3 working days.</p>
        <p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">Thank you for choosing fourth element and we hope to see you again soon.</p>'.$emailfooter;
        
        $headers  = "Fourth Element <info@fourthelement.com>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html\r\n";
        wp_mail($getcurrStatus->corresemail, 'Your fourth element return request – Refund Processed', $statusEmail,'From: '.$headers.'');
        
    }

}

if ( $returnsid <> 'noreturnemail') {
    $addReturnsID = ", returnsid = '".$returnsid."' "; //for update sql query
    //if there is now a returns id, let's notify the customer of it.
    
if ( $shipCountry == 'GB' ) {
    
    $pdf = new PDF('L','mm','A4');
    $title = '';
    
    $pdf->AddPage();
    $pdf->Image('https://fourthelement.com/images/snippet-background.png', 35, 10, 120, 90);
    $pdf->SetFont('Arial','',12);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,30);
    $pdf->Cell(150,0,'Returns Number: '.$returnsid);
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,40);
    $pdf->Cell(150,0,'Returns Department');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,45);
    $pdf->Cell(150,0,'Fourth Element');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,50);
    $pdf->Cell(150,0,'Water Ma Trout');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,55);
    $pdf->Cell(150,0,'Helston');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,60);
    $pdf->Cell(150,0,'Cornwall');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,65);
    $pdf->Cell(150,0,'TR13 0LW');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,70);
    $pdf->Cell(150,0,'United Kingdom');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,80);
    $pdf->Cell(150,0,'T: +44 1326 574745');
    
    //$pdf->Output($returnsid.'-returns-label.pdf', 'D');
    $pdf->Output('/home/fourtcom/public_html/wp-content/uploads/returns-labels/'.$returnsid.'-returns-label.pdf', 'F');
    ob_end_flush(); 
    $url = 'https://fourthelement.com/wp-content/uploads/returns-labels/'.$returnsid.'-returns-label.pdf';

} else if ( $shipCountry == 'US' ) {
    $pdf = new PDF('L','mm','A4');
    $title = '';
    
    $pdf->AddPage();
    $pdf->Image('https://fourthelement.com/images/snippet-background.png', 35, 10, 120, 90);
    $pdf->SetFont('Arial','',12);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,30);
    $pdf->Cell(150,0,'Returns Number: '.$returnsid);
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,40);
    $pdf->Cell(150,0,'Returns Department');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,45);
    $pdf->Cell(150,0,'Fourth Element USA');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,50);
    $pdf->Cell(150,0,'383 Portland Street');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,55);
    $pdf->Cell(150,0,'Fryeburg');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,60);
    $pdf->Cell(150,0,'Maine');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,65);
    $pdf->Cell(150,0,'04037');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,70);
    $pdf->Cell(150,0,'USA');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,80);
    $pdf->Cell(150,0,'T: +1 207 935 1670');    
    
    $pdf->Output('/home/fourtcom/public_html/wp-content/uploads/returns-labels/'.$returnsid.'-returns-label.pdf', 'F');
    ob_end_flush(); 
    $url = 'https://fourthelement.com/wp-content/uploads/returns-labels/'.$returnsid.'-returns-label.pdf';
    
} else if ( in_array($shipCountry,$eulist) ) {
    
    $pdf = new PDF('L','mm','A4');
    $title = '';
    
    $pdf->AddPage();
    $pdf->Image('https://fourthelement.com/images/snippet-background.png', 35, 10, 120, 90);
    $pdf->SetFont('Arial','',12);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,30);
    $pdf->Cell(150,0,'Returns Number: '.$returnsid);
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,40);
    $pdf->Cell(150,0,'Returns Department Fourth Element');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,45);
    $pdf->Cell(150,0,'I-Fulfilment');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,50);
    $pdf->Cell(150,0,'Harderhook 19');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,55);
    $pdf->Cell(150,0,'46395 Bocholt');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,60);
    $pdf->Cell(150,0,'Germany');
    
    $pdf->Output('/home/fourtcom/public_html/wp-content/uploads/returns-labels/'.$returnsid.'-returns-label.pdf', 'F');
    ob_end_flush(); 
    $url = 'https://fourthelement.com/wp-content/uploads/returns-labels/'.$returnsid.'-returns-label.pdf';

    
} else {
    
    $pdf = new PDF('L','mm','A4');
    $title = '';
    
    $pdf->AddPage();
    $pdf->Image('https://fourthelement.com/images/snippet-background.png', 35, 10, 120, 90);
    $pdf->SetFont('Arial','',12);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,30);
    $pdf->Cell(150,0,'A Returns Number: '.$returnsid);
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,40);
    $pdf->Cell(150,0,'Returns Department');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,45);
    $pdf->Cell(150,0,'Fourth Element');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,50);
    $pdf->Cell(150,0,'Water Ma Trout');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,55);
    $pdf->Cell(150,0,'Helston');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,60);
    $pdf->Cell(150,0,'Cornwall');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,65);
    $pdf->Cell(150,0,'TR13 0LW');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,70);
    $pdf->Cell(150,0,'United Kingdom');
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(60,80);
    $pdf->Cell(150,0,'T: +44 1326 574745');
    
    $pdf->Output('/home/fourtcom/public_html/wp-content/uploads/returns-labels/'.$returnsid.'-returns-label.pdf', 'F');

    ob_end_flush(); 
    $url = 'https://fourthelement.com/wp-content/uploads/returns-labels/'.$returnsid.'-returns-label.pdf';
    
}

    $returnsEmail = $emailheader;
    $returnsEmail .= '<p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">Thank you for submitting your returns request.  Your returns number is <strong>'.$returnsid.'</strong>.  Please include this in any correspondence with us.</p>
        <p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">We are very sorry that you had a problem with your order. One of our team will be in touch with you soon to request more information from you and to arrange the return of your items.</p>
        <p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">When preparing your items for return please take care when packing the goods.  Neoprene can be damaged if it is folded and crushed.  If possible, please re-use the packaging you received from us.</p>
        <p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">On your delivery note, there is a printed address label, please cut this out and attach this to the outside of your parcel and write your returns number in the box provided.</p>
        <p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">If you have lost your delivery note, please click here to print an address label.</p>
        <p style="font-size:14px;color:#596670;margin:30px 0 30px 0;"><a href="'.$url.'">Download Delivery Label</a></p>
        <p style="font-size:14px;color:#596670;margin:10px 0 10px 0;">Once you have packed up your return, you can simply send this parcel back to us (we recommend a tracked service for your own peace of mind) and we will refund the costs of this delivery to you, or you can request for us to arrange a collection.  We will let you know when your return has been received and processed.</p>';
    $returnsEmail .= $emailfooter;
    
    $headers  = "Fourth Element <info@fourthelement.com>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html\r\n";
    //wp_mail($getcurrStatus->corresemail, 'Your fourth element return request', $returnsEmail,'From: '.$headers.'');    
}

$notes = str_replace("\'","''",$notes);

if ( $sku && $sku <> 'undefined' ) {
    $getitems = $wpdb->get_row("SELECT skus FROM wp_fe_returns WHERE id = ".$id."");
    $skus = unserialize($getitems->skus);
    foreach($skus as $key => &$val){
        if ( strtoupper($key) == $sku ) {
            $val['itemstatus'] = $statuses[$status];
            $val['emailsent'] = 'completed';
        }
    }

    $updatedskus = serialize($skus);
    $result = $wpdb->query("UPDATE wp_fe_returns SET skus = '".$updatedskus."' WHERE id = ".$id."");
    
} else {
    $result = $wpdb->query("UPDATE wp_fe_returns SET notes = '".$notes."', status = '".$status."' ".$addReturnsID." WHERE id = ".$id."");
}


print_r($result);