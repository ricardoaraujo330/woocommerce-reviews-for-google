<?php
/**
 * Plugin Name:  Woo Reviews for Google
 * Plugin URI:   https://github.com/ricardoaraujo330
 * Description:  A Plugin to activate Google Customer Reviews in your Woocommerce store.
 * Author:       ricardoaraujo330
 * Author URI:   https://github.com/ricardoaraujo330
 * Version:      1.0
 * License:      GPLv2 or later
 * Text Domain:  woo-rfg
 */
 
add_action('wp_footer','google_customer_review');
add_action('admin_menu','gcr_plugin_admin_add_page');

function google_customer_review() {
        $showBadgeOption = get_option( 'icr-showBadge' ) ? get_option( 'icr-showBadge' ) : '';
        $showBadge       = ! empty( $showBadgeOption );
        $merchantIdOption = get_option( 'icr-merchantId' ) ? get_option( 'icr-merchantId' ) : '';
        $merchantId      = $merchantIdOption ? absint( $merchantIdOption ) : 0;
        $opt_in_style    = get_option( 'icr-opt-in-style' ) ? get_option( 'icr-opt-in-style' ) : 'CENTER_DIALOG';
        $language_code   = get_option( 'icr-language-code' ) ? get_option( 'icr-language-code' ) : str_replace( '_', '-', get_locale() );

        if ( $opt_in_style ) {
                $opt_in_style = strtoupper( $opt_in_style );
        }

        if ( $language_code ) {
                $language_code = str_replace( '_', '-', $language_code );
                $language_parts = explode( '-', $language_code );
                if ( count( $language_parts ) >= 2 ) {
                        $language_parts[0] = strtolower( $language_parts[0] );
                        $language_parts[1] = strtoupper( $language_parts[1] );
                        $language_code     = implode( '-', $language_parts );
                }
        }
        $base_location   = wc_get_base_location();
        $merchantCountry = isset( $base_location['country'] ) ? $base_location['country'] : '';
        if ( $merchantCountry ) {
                $merchantCountry = strtoupper( $merchantCountry );
        }

        if ( is_order_received_page() ) {
                $order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';

                if ( ! empty( $order_key ) ) {
                        $order_id = wc_get_order_id_by_order_key( $order_key );

                        if ( $order_id ) {
                                $order = wc_get_order( $order_id );

                                if ( $order instanceof WC_Order && ! $order->has_status( 'failed' ) ) {
                                        $merchant_id_int      = absint( $merchantId );
                                        $order_id_for_render  = $order->get_id();
                                        $order_email          = sanitize_email( $order->get_billing_email() );
                                        $delivery_country     = $order->get_shipping_country();

                                        if ( empty( $delivery_country ) ) {
                                                $delivery_country = $order->get_billing_country();
                                        }

                                        if ( empty( $delivery_country ) && isset( $base_location['country'] ) ) {
                                                $delivery_country = $base_location['country'];
                                        }

                                        if ( $delivery_country ) {
                                                $delivery_country = strtoupper( $delivery_country );
                                        }

                                        $estimated_delivery_date = icr_calc_delivery();

                                        if ( ! empty( $estimated_delivery_date ) ) {
                                                $timestamp = strtotime( $estimated_delivery_date );
                                                if ( $timestamp ) {
                                                        $estimated_delivery_date = gmdate( 'Y-m-d', $timestamp );
                                                } else {
                                                        $estimated_delivery_date = '';
                                                }
                                        }

                                        $render_settings = array(
                                                'merchant_id'             => $merchant_id_int,
                                                'order_id'                => (string) $order_id_for_render,
                                                'email'                   => $order_email,
                                                'delivery_country'        => $delivery_country,
                                                'estimated_delivery_date' => $estimated_delivery_date,
                                        );

                                        if ( ! empty( $opt_in_style ) ) {
                                                $render_settings['opt_in_style'] = $opt_in_style;
                                        }

                                        if ( ! empty( $language_code ) ) {
                                                $render_settings['language_code'] = $language_code;
                                        }

                                        if ( ! empty( $merchantCountry ) ) {
                                                $render_settings['merchant_country'] = $merchantCountry;
                                        }

                                        $products = array();

                                        foreach ( $order->get_items( 'line_item' ) as $item ) {
                                                $product = $item->get_product();

                                                if ( ! $product ) {
                                                        continue;
                                                }

                                                $gtin = $product->get_meta( 'gtin', true );

                                                if ( empty( $gtin ) ) {
                                                        continue;
                                                }

                                                $gtin = trim( (string) $gtin );

                                                if ( '' === $gtin ) {
                                                        continue;
                                                }

                                                $products[] = array(
                                                        'gtin' => $gtin,
                                                );
                                        }

                                        if ( ! empty( $products ) ) {
                                                $render_settings['products'] = $products;
                                        }

                                        $should_render = $merchant_id_int && $order_id_for_render && ! empty( $order_email ) && ! empty( $delivery_country ) && ! empty( $estimated_delivery_date );

                                        if ( $should_render ) {
?>

                <script src="https://apis.google.com/js/platform.js?onload=renderOptIn" async defer></script>

                <script>
                  window.renderOptIn = function() {
                        window.gapi.load('surveyoptin', function() {
                          window.gapi.surveyoptin.render(
                                <?php echo wp_json_encode( $render_settings ); ?>
                                );
                        });
                  }
                </script>
<?php
                                        }
                                }
                        }
                }
        }
        if($showBadge){ ?>
                <script src="https://apis.google.com/js/platform.js?onload=renderBadge" async defer></script>
                <script>
                  window.renderBadge = function() {
                        var ratingBadgeContainer = document.createElement("div");
                        document.body.appendChild(ratingBadgeContainer);
                        window.gapi.load('ratingbadge', function() {
                          window.gapi.ratingbadge.render(ratingBadgeContainer, {"merchant_id": <?php echo (int) $merchantId; ?>});
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
                        $merchantId = absint( wp_unslash( $_POST['icr-merchantId'] ) );
                        update_option('icr-merchantId',$merchantId);
                }
                if(isset($_POST['icr-afterTimeDays']) && !empty($_POST['icr-afterTimeDays'])){
                        $afterTimeDays = absint( wp_unslash( $_POST['icr-afterTimeDays'] ) );
                        update_option('icr-afterTimeDays',$afterTimeDays);
                }
                if(isset($_POST['icr-beforeTime']) && !empty($_POST['icr-beforeTime'])){
                        $beforeTime = absint( wp_unslash( $_POST['icr-beforeTime'] ) );
                        update_option('icr-beforeTime',$beforeTime);
                }
                if(isset($_POST['icr-showBadge']) && !empty($_POST['icr-showBadge'])){
                        $showBadge = absint( wp_unslash( $_POST['icr-showBadge'] ) );
                        update_option('icr-showBadge',$showBadge);
                }else{
                        update_option('icr-showBadge','');
                }
                if(isset($_POST['icr-opt-in-style'])){
                        $allowed_styles = array( 'CENTER_DIALOG', 'BOTTOM_RIGHT_DIALOG', 'BOTTOM_LEFT_DIALOG' );
                        $submitted_style = strtoupper( sanitize_text_field( wp_unslash( $_POST['icr-opt-in-style'] ) ) );
                        if ( ! in_array( $submitted_style, $allowed_styles, true ) ) {
                                $submitted_style = 'CENTER_DIALOG';
                        }
                        update_option( 'icr-opt-in-style', $submitted_style );
                }
                if(isset($_POST['icr-language-code'])){
                        $language_code = sanitize_text_field( wp_unslash( $_POST['icr-language-code'] ) );
                        $language_code = str_replace( '_', '-', $language_code );
                        $language_parts = explode( '-', $language_code );
                        if ( count( $language_parts ) >= 2 ) {
                                $language_parts[0] = strtolower( $language_parts[0] );
                                $language_parts[1] = strtoupper( $language_parts[1] );
                                $language_code     = implode( '-', $language_parts );
                        }
                        update_option( 'icr-language-code', $language_code );
                }

                $merchantId = get_option( 'icr-merchantId' ) ? get_option( 'icr-merchantId' ) : '';
                $showBadge = get_option( 'icr-showBadge' ) ? get_option( 'icr-showBadge' ) : '';
                $beforeTime = get_option( 'icr-beforeTime' ) ? get_option( 'icr-beforeTime' ) : '12';
                $afterTimeDays = get_option( 'icr-afterTimeDays' ) ? get_option( 'icr-afterTimeDays' ) : '2';
                $optInStyle = get_option( 'icr-opt-in-style' ) ? get_option( 'icr-opt-in-style' ) : 'CENTER_DIALOG';
                $languageCode = get_option( 'icr-language-code' ) ? get_option( 'icr-language-code' ) : str_replace( '_', '-', get_locale() );
		
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
                                          <th>
                                                  <?php echo __( 'Opt-in style', 'woo-rfg' ); ?>:
                                          </th>
                                          <td>
                                                  <select name="icr-opt-in-style">
                                                          <?php
                                                          $styles = array(
                                                                  'CENTER_DIALOG'       => __( 'Center dialog', 'woo-rfg' ),
                                                                  'BOTTOM_RIGHT_DIALOG' => __( 'Bottom right dialog', 'woo-rfg' ),
                                                                  'BOTTOM_LEFT_DIALOG'  => __( 'Bottom left dialog', 'woo-rfg' ),
                                                          );
                                                          foreach ( $styles as $style_key => $style_label ) {
                                                                  printf(
                                                                          '<option value="%1$s" %3$s>%2$s</option>',
                                                                          esc_attr( $style_key ),
                                                                          esc_html( $style_label ),
                                                                          selected( $optInStyle, $style_key, false )
                                                                  );
                                                          }
                                                          ?>
                                                  </select>
                                                  <p class="description"><?php echo __( 'Choose how the opt-in module is displayed.', 'woo-rfg' ); ?></p>
                                          </td>
                                  </tr>
                                  <tr>
                                          <th>
                                                  <?php echo __( 'Language code', 'woo-rfg' ); ?>:
                                          </th>
                                          <td>
                                                  <input type="text" name="icr-language-code" value="<?php echo esc_attr( $languageCode ); ?>" class="regular-text" />
                                                  <p class="description"><?php echo __( 'Use a supported locale such as en-US or pt-BR.', 'woo-rfg' ); ?></p>
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