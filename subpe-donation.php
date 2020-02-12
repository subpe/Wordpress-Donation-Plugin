<?php
/**
 * Plugin Name: subpe Payment Donation
 * Plugin URI: https://github.com/subpe/
 * Description: This plugin allow you to accept donation payments using subpe. This plugin will add a simple form that user will fill, when he clicks on submit he will redirected to subpe website to complete his transaction and on completion his payment, subpe will send that user back to your website along with transactions details. This plugin uses server-to-server verification to add additional security layer for validating transactions. Admin can also see all transaction details with payment status by going to "subpe Payment Details" from menu in admin.
 * Version: 1.0
 * Author: subpe
 * Author URI: http://subpe.in/
 * Text Domain: subpe Payments
 */

//ini_set('display_errors','On');
register_activation_hook(__FILE__, 'subpe_activation');
register_deactivation_hook(__FILE__, 'subpe_deactivation');

// do not conflict with WooCommerce subpe Plugin Callback
if(!isset($_GET["wc-api"])){
	add_action('init', 'subpe_donation_response');
}

add_shortcode( 'subpedonation', 'subpe_donation_handler' );




add_action( 'wp_footer', function() {
   if ( !empty($_POST['RESPONSE_CODE'] )) {
$current_url=current_location();
      // fire the custom action
//$url = get_home_url(); 
if ($_POST['STATUS']=="Captured") {
	echo "<script>swal({
     title: 'Wow!',
     text: 'We Have received your payment.',
     icon: 'success',
     type: 'success'
 }).then(function() {
     window.location = '$url';
 });</script>";
}
    else{
    	echo "<script>swal({
     title: 'Wow!',
     text: 'We have not received your payment.',
     icon: 'error',
     type: 'failure'
 }).then(function() {
     window.location = '$current_url';
 });</script>";
    }	
     
   }
} );


if(isset($_GET['donation_msg']) && $_GET['donation_msg'] != ""){
	add_action('the_content', 'subpeDonationShowMessage');
}

function subpeDonationShowMessage($content){
	return '<div class="box">'.htmlentities(urldecode($_GET['donation_msg'])).'</div>'.$content;
}
	
// css for admin
add_action('admin_head', 'my_custom_fonts');

function my_custom_fonts() {
  echo '<style>
   .toplevel_page_subpe_options_page img{
      width:19px;
    } 
  </style>';
}
function current_location()
{
    if (isset($_SERVER['HTTPS']) &&
        ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
        isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
        $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        $protocol = 'https://';
    } else {
        $protocol = 'http://';
    }
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}


function wpdocs_theme_name_scripts() {
    wp_enqueue_script( 'sweetalert', 'https://unpkg.com/sweetalert/dist/sweetalert.min.js', array(), '1.0.0', false );
}
add_action( 'wp_enqueue_scripts', 'wpdocs_theme_name_scripts' );
//admin css


function subpe_activation() {
	global $wpdb, $wp_rewrite;
	$settings = subpe_settings_list();
	foreach ($settings as $setting) {
		add_option($setting['name'], $setting['value']);
	}
	add_option( 'subpe_donation_details_url', '', '', 'yes' );
	$post_date = date( "Y-m-d H:i:s" );
	$post_date_gmt = gmdate( "Y-m-d H:i:s" );

	$ebs_pages = array(
		'subpe-page' => array(
			'name' => 'subpe Transaction Details page',
			'title' => 'subpe Transaction Details page',
			'tag' => '[subpe_donation_details]',
			'option' => 'subpe_donation_details_url'
		),
	);
	
	$newpages = false;
	
	$subpe_page_id = $wpdb->get_var("SELECT id FROM `" . $wpdb->posts . "` WHERE `post_content` LIKE '%" . $subpe_pages['subpe-page']['tag'] . "%'	AND `post_type` != 'revision'");
	if(empty($subpe_page_id)){
		$subpe_page_id = wp_insert_post( array(
			'post_title'	=>	$subpe_pages['subpe-page']['title'],
			'post_type'		=>	'page',
			'post_name'		=>	$subpe_pages['subpe-page']['name'],
			'comment_status'=> 'closed',
			'ping_status'	=>	'closed',
			'post_content' =>	$subpe_pages['subpe-page']['tag'],
			'post_status'	=>	'publish',
			'post_author'	=>	1,
			'menu_order'	=>	0
		));
		$newpages = true;
	}

	update_option( $subpe_pages['subpe-page']['option'], _get_page_link($subpe_page_id) );
	unset($subpe_pages['subpe-page']);
	
	$table_name = $wpdb->prefix . "subpe_donation";
	$sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
				`id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
				`name` varchar(255),
				`email` varchar(255),
				`phone` varchar(255),
				`address` varchar(255),
				`city` varchar(255),
				`country` varchar(255),
				`state` varchar(255),
				`zip` varchar(255),
				`amount` varchar(255),
				`payment_status` varchar(255),
				`date` datetime
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($sql);

	if($newpages){
		wp_cache_delete( 'all_page_ids', 'pages' );
		$wp_rewrite->flush_rules();
	}
}

function subpe_deactivation() {
	$settings = subpe_settings_list();
	foreach ($settings as $setting) {
		delete_option($setting['name']);
	}
}

function subpe_settings_list(){
	$settings = array(
		array(
			'display' => 'Merchant ID',
			'name'    => 'subpe_merchant_id',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Merchant Id Provided by subpe'
		),
		array(
			'display' => 'Merchant Key',
			'name'    => 'subpe_merchant_key',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Merchant Secret Key Provided by subpe'
		),
		array(
			'display' => 'Website',
			'name'    => 'subpe_website',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Website Name Provided by subpe'
		),
		array(
			'display' => 'Industry Type',
			'name'    => 'subpe_industry_type_id',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Industry Type Provided by subpe'
		),
		array(
			'display' => 'Channel ID',
			'name'    => 'subpe_channel_id',
			'value'   => 'WEB',
			'type'    => 'textbox',
			'hint'    => 'Channel ID Provided by subpe e.g. WEB/WAP'
		),
		array(
			'display' => 'Transaction URL',
			'name'    => 'transaction_url',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Transaction URL Provided by subpe'
		),
		array(
			'display' => 'Transaction Status URL',
			'name'    => 'transaction_status_url',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Transaction Status URL Provided by subpe'
		),
		array(
			'display' => 'Default Amount',
			'name'    => 'subpe_amount',
			'value'   => '100',
			'type'    => 'textbox',
			'hint'    => 'the default donation amount, WITHOUT currency signs -- ie. 100'
		),
		array(
			'display' => 'Default Button/Link Text',
			'name'    => 'subpe_content',
			'value'   => 'subpe',
			'type'    => 'textbox',
			'hint'    => 'the default text to be used for buttons or links if none is provided'
		)				
	);
	return $settings;
}


if (is_admin()) {
	add_action( 'admin_menu', 'subpe_admin_menu' );
	add_action( 'admin_init', 'subpe_register_settings' );
}


function subpe_admin_menu() {
	add_menu_page('subpe Donation', 'subpe Donation', 'manage_options', 'subpe_options_page', 'subpe_options_page', plugin_dir_url(__FILE__).'assets/logo.ico');
	//, plugin_dir_url(__FILE__).'assets/logo.ico'

	add_submenu_page('subpe_options_page', 'subpe Donation Settings', 'Settings', 'manage_options', 'subpe_options_page');

	add_submenu_page('subpe_options_page', 'subpe Donation Payment Details', 'Payment Details', 'manage_options', 'wp_subpe_donation', 'wp_subpe_donation_listings_page');
	
	require_once(dirname(__FILE__) . '/subpe-donation-listings.php');
}


function subpe_options_page() {
	echo	'<div class="wrap">
				<h1>subpe Configuarations</h1>
				<form method="post" action="options.php">';
					wp_nonce_field('update-options');
					echo '<table class="form-table">';
						$settings = subpe_settings_list();
						foreach($settings as $setting){
						echo '<tr valign="top"><th scope="row">'.$setting['display'].'</th><td>';

							if ($setting['type']=='radio') {
								echo $setting['yes'].' <input type="'.$setting['type'].'" name="'.$setting['name'].'" value="1" '.(get_option($setting['name']) == 1 ? 'checked="checked"' : "").' />';
								echo $setting['no'].' <input type="'.$setting['type'].'" name="'.$setting['name'].'" value="0" '.(get_option($setting['name']) == 0 ? 'checked="checked"' : "").' />';
		
							} elseif ($setting['type']=='select') {
								echo '<select name="'.$setting['name'].'">';
								foreach ($setting['values'] as $value=>$name) {
									echo '<option value="'.$value.'" ' .(get_option($setting['name'])==$value? '  selected="selected"' : ''). '>'.$name.'</option>';
								}
								echo '</select>';

							} else {
								echo '<input type="'.$setting['type'].'" name="'.$setting['name'].'" value="'.get_option($setting['name']).'" />';
							}

							echo '<p class="description" id="tagline-description">'.$setting['hint'].'</p>';
							echo '</td></tr>';
						}

						echo '<tr>
									<td colspan="2" align="center">
										<input type="submit" class="button-primary" value="Save Changes" />
										<input type="hidden" name="action" value="update" />';
										echo '<input type="hidden" name="page_options" value="';
										foreach ($settings as $setting) {
											echo $setting['name'].',';
										}
										echo '" />
									</td>
								</tr>

								<tr>
								</tr>
							</table>
						</form>';

			$last_updated = "";
			$path = plugin_dir_path( __FILE__ ) . "/subpe_version.txt";
			if(file_exists($path)){
				$handle = fopen($path, "r");
				if($handle !== false){
					$date = fread($handle, 10); // i.e. DD-MM-YYYY or 25-04-2018
					$last_updated = '<p>Last Updated: '. date("d F Y", strtotime($date)) .'</p>';
				}
			}

			include( ABSPATH . WPINC . '/version.php' );
			$footer_text = '<hr/><div class="text-center">'.$last_updated.'<p>Wordpress Version: '. $wp_version .'</p></div><hr/>';

			echo $footer_text.'</div>';
}


function subpe_register_settings() {
	$settings = subpe_settings_list();
	foreach ($settings as $setting) {
		register_setting($setting['name'], $setting['value']);
	}
}

function subpe_donation_handler(){

	if(isset($_REQUEST["action"]) && $_REQUEST["action"] == "subpe_donation_request"){
		return subpe_donation_submit();
	} else {
		return subpe_donation_form();
	}
}

function subpe_donation_form(){
	$current_url = "//".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$html = ""; 
	$html = '<form name="frmTransaction" method="post">
					<p>
						<label for="donor_name">Name:</label>
						<input type="text" name="donor_name" maxlength="255" value="" required/>
					</p>
					<p>
						<label for="donor_email">Email:</label>
						<input type="text" name="donor_email" maxlength="255" value="" required />
					</p>
					<p>
						<label for="donor_phone">Phone:</label>
						<input type="text" name="donor_phone" maxlength="15" value="" required />
					</p>
					<p>
						<label for="donor_amount">Amount:</label>
						<input type="text" name="donor_amount" maxlength="10" value="'.trim(get_option('subpe_amount')).'" required />
					</p>
					<p>
						<label for="donor_address">Address:</label>
						<input type="text" name="donor_address" maxlength="255" value="" required />
					</p>
					<p>
						<label for="donor_city">City:</label>
						<input type="text" name="donor_city" maxlength="255" value="" required />
					</p>
					<p>
						<label for="donor_state">State:</label>
						<input type="text" name="donor_state" maxlength="255" value="" required />
					</p>
					<p>
						<label for="donor_postal_code">Postal Code:</label>
						<input type="text" name="donor_postal_code" maxlength="10" value="" required />
					</p>
					<p>
						<label for="donor_country">Country:</label>
						<input type="text" name="donor_country" maxlength="255" value="" required />
					</p>
					<p>
						<input type="hidden" name="action" value="subpe_donation_request">
						<input type="submit" value="' . trim(get_option('subpe_content')) .'" required />
					</p>
				</form>';
	
	return $html;
}


function subpe_donation_submit(){

	$valid = true; // default input validation flag
	$html = '';
	$msg = '';
			
	if( trim($_POST['donor_name']) != ''){
		$donor_name = $_POST['donor_name'];
	} else {
		$valid = false;
		$msg.= 'Name is required </br>';
	}
			
	if( trim($_POST['donor_email']) != ''){
		$donor_email = $_POST['donor_email'];
		if( preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/" , $donor_email)){}
		else{
			$valid = false;
			$msg.= 'Invalid email format </br>';
		}
	} else {
		$valid = false;
		$msg.= 'E-mail is required </br>';
	}
				
	if( trim($_POST['donor_amount']) != ''){
		$donor_amount = $_POST['donor_amount'];
		if( (is_numeric($donor_amount)) && ( (strlen($donor_amount) > '1') || (strlen($donor_amount) == '1')) ){}
		else{
			$valid = false;
			$msg.= 'Amount cannot be less then $1</br>';
		}
	} else {
		$valid = false;
		$msg.= 'Amount is required </br>';
	}

	if($valid){
		

		require_once(dirname(__FILE__) . '/lib/bppg_helper.php');
		global $wpdb;

		$table_name = $wpdb->prefix . "subpe_donation";
		$data = array(
					'name' => sanitize_text_field($_POST['donor_name']),
					'email' => sanitize_email($_POST['donor_email']),
					'phone' => sanitize_text_field($_POST['donor_phone']),
					'address' => sanitize_text_field($_POST['donor_address']),
					'city' => sanitize_text_field($_POST['donor_city']),
					'country' => sanitize_text_field($_POST['donor_country']),
					'state' => sanitize_text_field($_POST['donor_state']),
					'zip' => sanitize_text_field($_POST['donor_postal_code']),
					'amount' => sanitize_text_field($_POST['donor_amount']),
					'payment_status' => 'Pending Payment',
					'date' => date('Y-m-d H:i:s'),
				);

		$result = $wpdb->insert($table_name, $data);

		if(!$result){
			throw new Exception($wpdb->last_error);
		}

		$order_id = $wpdb->insert_id;

		// $order_id = 'TEST_'.strtotime("now").'-'.$order_id; //just for testing

		//For Post Request

		//End Post Request
       //echo get_option('subpe_merchant_id');exit;
 		$post_params = array(
			'PAY_ID' => trim(get_option('subpe_merchant_id')),
				'ORDER_ID' =>$order_id,
				'RETURN_URL' =>get_permalink(),
				'CUST_EMAIL'=>sanitize_email($_POST['donor_email']),
				'CUST_NAME' =>sanitize_text_field($_POST['donor_name']),
				'CUST_STREET_ADDRESS1'=>sanitize_text_field($_POST['donor_address']),
				'CUST_CITY' =>sanitize_text_field($_POST['donor_city']),
				'CUST_STATE' => sanitize_text_field($_POST['donor_state']),
				'CUST_COUNTRY' =>sanitize_text_field($_POST['donor_country']),
				'CUST_ZIP' =>sanitize_text_field($_POST['donor_postal_code']),
				'CUST_PHONE'=>sanitize_text_field($_POST['donor_phone']),
				'CURRENCY_CODE' =>356,
				'AMOUNT'        =>sanitize_text_field($_POST['donor_amount']*100),
				'PRODUCT_DESC' =>'Donation Collection' ,
				'CUST_SHIP_STREET_ADDRESS1' =>'',
				'CUST_SHIP_CITY'  => '',
				'CUST_SHIP_STATE' =>'',
				'CUST_SHIP_COUNTRY'=>'',
				'CUST_SHIP_ZIP'  =>'',
				'CUST_SHIP_PHONE'=>'',
				'CUST_SHIP_NAME' =>'',
				'TXNTYPE'      =>'SALE',                            
		);		
	
		$post_params["HASH"] = PayDonation::getHashFromArray(	$post_params,
																				trim(get_option('subpe_merchant_key'))
																			);
      //print_r($post_params);exit;
		$form_action = trim(get_option('transaction_url'));

		$html = "<center><h1>Please do not refresh this page...</h1></center>";

		
		$html .= '<form method="post" action="'.$form_action.'" name="f1">';

		foreach($post_params as $k=>$v){
			$html .= '<input type="hidden" name="'.$k.'" value="'.$v.'">';
		}

		$html .= "</form>";
		$html .= '<script type="text/javascript">document.f1.submit();</script>';
		return $html;

	} else {
		return $msg;
	}
}

function subpe_donation_meta_box() {
	$screens = array( 'subpedonation' );
	
	foreach ( $screens as $screen ) {
		add_meta_box(  'myplugin_sectionid', __( 'subpe', 'myplugin_textdomain' ),'subpe_donation_meta_box_callback', $screen, 'normal','high' );
	}
}

add_action( 'add_meta_boxes', 'subpe_donation_meta_box' );

function subpe_donation_meta_box_callback($post) {
	echo "admin";
}

function subpe_donation_response(){
	global $wpdb;

	if($_POST['STATUS']=="Captured")
{
		require_once(dirname(__FILE__) . '/lib/bppg_helper.php');
		global $wpdb;

		$subpe_merchant_key = trim(get_option('subpe_merchant_key'));
		$subpe_merchant_id = trim(get_option('subpe_merchant_id'));
		$transaction_status_url = trim(get_option('transaction_status_url'));
		?>
		<script>swal("Thank you for your order. Your transaction has been successful.");</script>
		<?php

   $msg = "Thank you for your order. Your transaction has been successful.";
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "subpe_donation SET payment_status = 'Complete Payment' WHERE  id =%d", sanitize_text_field($_POST['ORDER_ID'])));
				
				}
// 				else{

// $msg = "It seems some issue in server to server communication. Kindly connect with administrator.";
// 					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "subpe_donation SET payment_status = 'Fraud Payment' WHERE id = %d", sanitize_text_field($_POST['ORDER_ID'])));
// 				}



				

}


		// if(subpeDonation::verifychecksum_e($_POST, $subpe_merchant_key, $_POST['CHECKSUMHASH']) === "TRUE") {
			
		// 	if($_POST['RESPCODE'] == "01"){

		// 		// Create an array having all required parameters for status query.
		// 		$requestParamList = array("MID" => $subpe_merchant_id, "ORDERID" => $_POST['ORDERID']);

		// 		// $_POST['ORDERID'] = substr($_POST['ORDERID'], strpos($_POST['ORDERID'], "-") + 1); // just for testing
				
		// 		$StatusCheckSum = subpeDonation::getChecksumFromArray($requestParamList, $subpe_merchant_key);

		// 		$requestParamList['CHECKSUMHASH'] = $StatusCheckSum;

		// 		$responseParamList = subpeDonation::callNewAPI($transaction_status_url, $requestParamList);

		// 		if($responseParamList['STATUS'] == 'TXN_SUCCESS' && $responseParamList['TXNAMOUNT'] == $_POST['TXNAMOUNT']) {
		// 			$msg = "Thank you for your order. Your transaction has been successful.";
		// 			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "subpe_donation SET payment_status = 'Complete Payment' WHERE  id = %d", sanitize_text_field($_POST['ORDERID'])));
				
		// 		} else  {
		// 			$msg = "It seems some issue in server to server communication. Kindly connect with administrator.";
		// 			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "subpe_donation SET payment_status = 'Fraud Payment' WHERE id = %d", sanitize_text_field($_POST['ORDERID'])));
		// 		}

		// 	} else {
		// 		$msg = "Thank You. However, the transaction has been Failed For Reason: " . sanitize_text_field($_POST['RESPMSG']);
		// 		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "subpe_donation SET payment_status = 'Cancelled Payment' WHERE id = %d", sanitize_text_field($_POST['ORDERID'])));
		// 	}
		// } else {
		// 	$msg = "Security error!";
		// 	$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "subpe_donation SET payment_status = 'Payment Error' WHERE  id = %d", sanitize_text_field($_POST['ORDERID'])));
		// }

		// $redirect_url = get_site_url() . '/' . get_permalink(get_the_ID());
		// //echo $redirect_url ."<br />";
		// $redirect_url = add_query_arg( array('donation_msg'=> urlencode($msg)));
		// wp_redirect( $redirect_url,301 );
		// exit;
	// }
// }


/*
* Code to test Curl
*/
if(isset($_GET['subpe_action']) && $_GET['subpe_action'] == "curltest"){
	add_action('the_content', 'curltest_donation');
}

function curltest_donation($content){

	// phpinfo();exit;
	$debug = array();

	if(!function_exists("curl_init")){
		$debug[0]["info"][] = "cURL extension is either not available or disabled. Check phpinfo for more info.";

	// if curl is enable then see if outgoing URLs are blocked or not
	} else {

		// if any specific URL passed to test for
		if(isset($_GET["url"]) && $_GET["url"] != ""){
			$testing_urls = array(esc_url_raw($_GET["url"]));
		
		} else {

			// this site homepage URL
			$server = get_site_url();

			$testing_urls = array(
											$server,
											"https://www.gstatic.com/generate_204",
											get_option('transaction_status_url')
										);
		}

		// loop over all URLs, maintain debug log for each response received
		foreach($testing_urls as $key=>$url){

			$debug[$key]["info"][] = "Connecting to <b>" . $url . "</b> using cURL";
			
			$response = wp_remote_get($url);

			if ( is_array( $response ) ) {

				$http_code = wp_remote_retrieve_response_code($response);
				$debug[$key]["info"][] = "cURL executed succcessfully.";
				$debug[$key]["info"][] = "HTTP Response Code: <b>". $http_code . "</b>";

				// $debug[$key]["content"] = $res;

			} else {
				$debug[$key]["info"][] = "Connection Failed !!";
				$debug[$key]["info"][] = "Error: <b>" . $response->get_error_message() . "</b>";
				break;
			}
		}
	}

	$content = "<center><h1>cURL Test for subpe Donation Plugin</h1></center><hr/>";
	foreach($debug as $k=>$v){
		$content .= "<ul>";
		foreach($v["info"] as $info){
			$content .= "<li>".$info."</li>";
		}
		$content .= "</ul>";

		// echo "<div style='display:none;'>" . $v["content"] . "</div>";
		$content .= "<hr/>";
	}

	return $content;
}
/*
* Code to test Curl
*/