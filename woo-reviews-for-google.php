<?php
/**
 * Plugin Name:  Woo Reviews for Google
 * Plugin URI:   http://icreator.nl
 * Description:  A Plugin to activate Google Customer Reviews in your Woocommerce store.
 * Author:       ICREATOR
 * Author URI:   http://icreator.nl
 * Version:      1.0
 * License:      GPLv2 or later
 * Text Domain:  woo-rfg
 */
 
add_action('wp_footer','google_customer_review');
add_action('admin_menu','gcr_plugin_admin_add_page');

function google_customer_review() {
	$showBadge = get_option( 'icr-showBadge' ) ? get_option( 'icr-showBadge' ) : '';
	$merchantId = get_option( 'icr-merchantId' ) ? get_option( 'icr-merchantId' ) : ''; 
	if ( is_order_received_page() ) {

		$order_key   = $_GET['key'];
		$order       = new WC_Order( wc_get_order_id_by_order_key( $order_key ) );
		$order_total = $order->get_total();
		
		if ( ! $order->has_status( 'failed' ) ) {
?>
		
		<script src="https://apis.google.com/js/platform.js?onload=renderOptIn" async defer></script>
		<?php $order_date = $order->order_date; ?>

		<script>
		  window.renderOptIn = function() {
			window.gapi.load('surveyoptin', function() {
			  window.gapi.surveyoptin.render(
				{
				  "merchant_id": <?php echo $merchantId; ?>,
				  "order_id": "<?php echo $order->id; ?>",
				  "email": "<?php echo esc_html( $order->billing_email ); ?>",
				  "delivery_country": "<?php echo $order->shipping_country?>",
				  "estimated_delivery_date": "<?php echo icr_calc_delivery() ?>"
				});
			});
		  }
		</script>
		<?php
		}
	}
	if($showBadge){ ?>
		<script src="https://apis.google.com/js/platform.js?onload=renderBadge" async defer></script>
		<script>
		  window.renderBadge = function() {
			var ratingBadgeContainer = document.createElement("div");
			document.body.appendChild(ratingBadgeContainer);
			window.gapi.load('ratingbadge', function() {
			  window.gapi.ratingbadge.render(ratingBadgeContainer, {"merchant_id": <?php echo $merchantId; ?>});
			});
		  }
		</script>
	<?php 
	}
}

function gcr_plugin_admin_add_page() {
	add_submenu_page(
		'woocommerce',                                // $page_title
		__( 'Woo Reviews for Google', 'woo-rfg' ),            // $menu_title
		__( 'Woo Reviews for Google', 'woo-rfg' ),            // $menu_title
		'manage_options',                             // $capability
		'icr-woo-rfg',                                    // $menu_slug
		'gcr_options_page'
		);
}

function icr_calc_delivery(){
	$currentday = date('D');
	$currenttime = date ('G');
	$beforeTime = get_option( 'icr-beforeTime' ) ? get_option( 'icr-beforeTime' ) : '12';	
	$afterTimeDays = get_option( 'icr-afterTimeDays' ) ? get_option( 'icr-afterTimeDays' ) : '2';	
	$delivery_date;

	/*
	Door de weeks Voor 12 uur: dag daarna
	door de weeks na 12 uur: 2 dagen daarna ( ma -> wo)
	
	Weekend : Dinsdag
	weekend + vrijdag na 12 uur: Dinsdag
	
	*/
	
	switch ($currentday){
		Case "Mon":
			if ($currenttime >= $beforeTime){
				$calc_date = $currenttime+strtotime("+{$afterTimeDays} day");
				return $delivery_date= date("Y-m-d",$calc_date);
			}
			else{
				$calc_date = $currenttime+strtotime('+1 day');
				return $delivery_date= date("Y-m-d",$calc_date);
			}
			break;
		
		Case  "Tue":
			if ($currenttime >= $beforeTime){
				$calc_date = $currenttime+strtotime("+{$afterTimeDays} day");
				return $delivery_date= date("Y-m-d",$calc_date);
			}
			else{
				$calc_date = $currenttime+strtotime('+1 day');
				return $delivery_date= date("Y-m-d",$calc_date);
			}
			break;
		
		Case  "Wed":
			if ($currenttime >= $beforeTime){
				$calc_date = $currenttime+strtotime("+{$afterTimeDays} day");
				return $delivery_date= date("Y-m-d",$calc_date);
			}
			else{
				$calc_date = $currenttime+strtotime('+1 day');
				return $delivery_date= date("Y-m-d",$calc_date);
			}
			break;
		
		Case  "Thu":
			if ($currenttime >= $beforeTime){
				$calc_date = $currenttime+strtotime("+{$afterTimeDays} day");
				return $delivery_date= date("Y-m-d",$calc_date);
			}
			else{
				$calc_date = $currenttime+strtotime('+1 day');
				return $delivery_date= date("Y-m-d",$calc_date);
			}
			break;
		
		Case "Fri":
			if ($currenttime >= $beforeTime){
				$weekendDays = $afterTimeDays + 2;
				$calc_date = $currenttime+strtotime("+{$afterTimeDays} day");
				return $delivery_date= date("Y-m-d",$calc_date);
			}
			else{
				$calc_date = $currenttime+strtotime('+1 day');
				return $delivery_date= date("Y-m-d",$calc_date);
			}
			break;
		
		Case  "Sat":
			$weekendDays = $afterTimeDays + 1;
			$calc_date = $currenttime+strtotime("+{$afterTimeDays} day");
			return $delivery_date= date("Y-m-d",$calc_date);
			break;
		
		
		Case  "Sun":
			$calc_date = $currenttime+strtotime("+{$afterTimeDays} day");
			return $delivery_date= date("Y-m-d",$calc_date);	
			break;
	}
}

// display the admin options page
function gcr_options_page() {

		if(isset($_POST['icr-merchantId']) && !empty($_POST['icr-merchantId'])){
			$merchantId = (int) $_POST['icr-merchantId'];
			update_option('icr-merchantId',$merchantId);
		}
		if(isset($_POST['icr-afterTimeDays']) && !empty($_POST['icr-afterTimeDays'])){
			$afterTimeDays = (int) $_POST['icr-afterTimeDays'];
			update_option('icr-afterTimeDays',$afterTimeDays);
		}
		if(isset($_POST['icr-beforeTime']) && !empty($_POST['icr-beforeTime'])){
			$beforeTime = (int) $_POST['icr-beforeTime'];
			update_option('icr-beforeTime',$beforeTime);
		}
		if(isset($_POST['icr-showBadge']) && !empty($_POST['icr-showBadge'])){
			$showBadge = (int) $_POST['icr-showBadge'];
			update_option('icr-showBadge',$showBadge);
		}else{
			update_option('icr-showBadge','');
		}

		$merchantId = get_option( 'icr-merchantId' ) ? get_option( 'icr-merchantId' ) : '';  
		$showBadge = get_option( 'icr-showBadge' ) ? get_option( 'icr-showBadge' ) : '';
		$beforeTime = get_option( 'icr-beforeTime' ) ? get_option( 'icr-beforeTime' ) : '12';	
		$afterTimeDays = get_option( 'icr-afterTimeDays' ) ? get_option( 'icr-afterTimeDays' ) : '2';	
		
		?>
	<div class="wrap">
		<h1><?php echo __( 'Woo Reviews for Google', 'woo-rfg' ) ?></h1>
		<div class="notice"><p><?php echo __( 'Please activate reviews in your merchant center first.', 'woo-rfg' ) ?></p></div>
		<form action="" method="post">
			<?php settings_fields( 'gcr_plugin_options_settings_fields' ); ?>
			<?php do_settings_sections( 'icr-woo-rfg' ); ?>
			<br>
			<table class="form-table">
				<tr>
					<th>
						<?php echo __( 'Merchant id', 'woo-rfg' ) ?>:
					</th>
					<td>
					 <input type="text" name="icr-merchantId" value="<?php echo esc_attr( $merchantId ); ?>" class="regular-text"/>
					 <p class="description"><?php echo __( 'You can find this in your merchant center.', 'woo-rfg' ) ?></p>
					 </td>
				</tr>
				<tr>
					<th>
						<?php echo __( 'Delivery', 'woo-rfg' ) ?>:
					</th>
					<td>
					 <?php echo __( 'If ordered before', 'woo-rfg' ) ?> <input type="number" name="icr-beforeTime" value="<?php echo esc_attr( $beforeTime ); ?>" class="tiny-text"/>:00
					 <?php echo __( 'delivery on next day.', 'woo-rfg' ) ?><br /><?php echo __( 'Else delivery in', 'woo-rfg' ) ?> <input type="number" name="icr-afterTimeDays" value="<?php echo esc_attr( $afterTimeDays ); ?>" class="tiny-text"/> <?php echo __( 'days.', 'woo-rfg' ) ?>
					 </td>
				</tr>
				<tr>
					<th>
						<?php echo __( 'Display Badge', 'woo-rfg' ) ?>:
					</th>
					<td class="forminp forminp-checkbox">
						<input type="checkbox" name="icr-showBadge" <?php echo ($showBadge) ? 'checked' : '';  ?>  value="1"/>
						<p class="description"><?php echo __( 'This is for showing your average review score', 'woo-rfg' ) ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row" style="white-space: nowrap">
						<?php submit_button(); ?>
					</th>
				</tr>
			</table>
		</form>		
	</div>			
<?php	
}