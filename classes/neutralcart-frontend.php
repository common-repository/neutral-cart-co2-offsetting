<?php
class Neutralcart_Frontend
{
	public static $neutralcart_tax_percentage;

	public function __construct() {
		add_action('init',array($this,'neutralcart_frontend_init'));

	}

	public static function neutralcart_frontend_init(){
		$neutralcart_status = sanitize_text_field(get_option('neutralcart_status', 'disable'));
		$user = wp_get_current_user();
		$allowed_roles = array('editor', 'administrator', 'author');
		
		if($neutralcart_status == 'enable' || ($neutralcart_status == 'test' && array_intersect($allowed_roles, $user->roles))){
			
			add_action( 'wp_head', array($this,'neutralcart_label_style'));
			add_action( 'wp_footer', array($this,'add_script_on_checkout' ));
			
			//get tax percentage
			add_action( 'woocommerce_before_calculate_totals', array($this, 'neutralcart_calculate_tax_percentage') );
			
			//calculate the carbon offsetting cost
			add_action( 'woocommerce_cart_calculate_fees', array($this,'calculate_cost' ), 10, 1);
			
			//getting the label placement and adding it to the correct position
			$neutralcart_label_placement = sanitize_text_field(get_option('neutralcart_label_placement', 'woocommerce_review_order_before_payment'));
			add_action( $neutralcart_label_placement, array($this,'add_option_to_checkout' ));

		}
	}



	public static function neutralcart_calculate_tax_percentage() {

		//include tax on the fee
		$tax_rates = \WC_Tax::get_base_tax_rates();
		$biggest_tax_rate = 0;
        if(!empty($tax_rates)) {
	        foreach ($tax_rates as $tax_rate)
	        {
	            if($biggest_tax_rate < $tax_rate['rate'] )
	                $biggest_tax_rate = $tax_rate['rate'];
	        }
	    }
        self::$neutralcart_tax_percentage = ((int)$biggest_tax_rate)/100;
	}
	

	public static function neutralcart_label_style() {
		$neutralcart_label_style 	= get_option('neutralcart_label_style', 1);
		$neutralcart_custom_css 	= get_option('neutralcart_label_css', '');

		switch($neutralcart_label_style) {
			case 1: //default (bright)

			?>
		    <style>
		    	#neutralcart_label {
		    		background-color: #fff;
		    		color: #000;
	    		    box-shadow: 0 0 15px 0 rgba(0,0,0,0.10);
    				border: 1px solid #ddd;
		    	}
			</style>
			<?php

			break;
			case 2: //dark
			?>
		    <style>
		    	#neutralcart_label {
		    		background-color:#353C38;
		    		color: #ffffff;
	    		    box-shadow: 0 0 15px 0 rgba(0,0,0,0.10);
		    	}
			</style>
			<?php

			break;
			
			default: //default (bright)
			?>
		    <style>
		    	#neutralcart_label {
		    		background-color: #fff;
		    		color: #000;
	    		    box-shadow: 0 0 15px 0 rgba(0,0,0,0.10);
    				border: 1px solid #ddd;
		    	}
			</style>
			<?php

			break;
		}


		?>


	    <style> 
		   <?php echo $neutralcart_custom_css; ?>
		</style>

		<?php

	}

	public static function add_script_on_checkout(){
		if (is_checkout()) { //is_checkout()
			$neutralcart_auto_checked = sanitize_text_field(get_option('neutralcart_auto_checked', 'disable'));
    ?>

		    <script type="text/javascript">
		    jQuery( document ).ready(function( $ ) {
		        $('#neutralcart_woocommerce').click(function(){
		            jQuery('body').trigger('update_checkout');
		        });
		    });
		    </script>

		    
	    <?php
	    }
	    if( $neutralcart_auto_checked == 'enable' ){
	    	?>
	    	<script type="text/javascript">jQuery('#neutralcart_woocommerce').trigger('click');</script>
	    	<?php
	    }
	}

    
	public static function calculate_cost( $cart ){

		global $woocommerce;
		
        $shopId = sanitize_text_field(get_option('neutralcart_id', false));
		$cartItems = $woocommerce->cart->get_cart();

		if(sizeof($cartItems) > 0) {

			if(isset($_SESSION['neutralcart_cart_contents']) && isset($_SESSION['neutralcart_co2cost']) && $_SESSION['neutralcart_co2cost'] > 0) {
				
		    	$checkCart =serialize($cartItems);
				$checkSession =serialize($_SESSION['neutralcart_cart_contents']);
				

				//if the items is not already in session
				if ($checkCart != $checkSession) {
					
					//store items in session
					$_SESSION['neutralcart_cart_contents'] = $cartItems;
					
					//get an updated co2cost and store in session
					$_SESSION['neutralcart_co2cost'] = $this->calculate_cost_from_api($shopId, $this->filter_cart_items($cartItems));
					

				}
		    	
			}

			//if the session is not set:
			else {
					//store items in session
					$_SESSION['neutralcart_cart_contents'] = $cartItems;
					
					//get an updated co2cost and store in session
					$_SESSION['neutralcart_co2cost'] = $this->calculate_cost_from_api($shopId, $this->filter_cart_items($cartItems));
					
			}

		}

		// sanitize everything
        $_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        

        // https://facetwp.com/is_admin-and-ajax-in-wordpress/
        if ( ! $_POST || ( is_admin() && ! is_ajax() ) ) {
        	return;
	    }

	    if ( isset( $_POST['post_data'] ) ) {
	        parse_str( $_POST['post_data'], $post_data );
	    } else {
	        $post_data = $_POST;
	    }

	    if (isset($post_data['neutralcart_woocommerce'])) {
	    	global $woocommerce;
	    	
	    	//adding the fee without tax and making it taxable (value = co2cost excl. tax, true => adding tax)
	        WC()->cart->add_fee(NEUTRALCART, $_SESSION['neutralcart_co2cost'], true, '' );
	    }
	}

	public static function filter_cart_items($items) {
      	
    	if( empty($items) ) {
    		return 0;
    	}

    	$filteredItems = array();

        // Iterating through each "line" items in the order
		foreach ($items as $item_id => $item_data) {

            $_product =  wc_get_product( $item_data['data']->get_id());

            if(empty($_product)) {
            	return 0;
            } 

		    array_push($filteredItems, array(
		    	'name' => wp_strip_all_tags($_product->get_title()),
		    	'quantity' => (int)$item_data['quantity'],
		    	'unitPrice' => get_post_meta($item_data['product_id'] , '_price', true)
		    ));
		}
		return $filteredItems;

    }


	public static function calculate_cost_from_api($shopId, $cartItems) {
		
		if( empty($shopId) || empty($cartItems) ) {
			return 0;
		}

		$co2cost = 0;
		$url = NEUTRALCART_API_URL . '/get-carbon-offset';
		$args = array(
			'headers' 		=> array('Content-Type' => 'application/json; charset=utf-8'),
			'body' 			=> json_encode(array(
				'shopId'		=> $shopId,
				'items' 		=> $cartItems,
				'currency' 		=> get_woocommerce_currency()
			)),
			'data_format'	=> 'body'
		);


		$response = wp_remote_post( $url, $args );

		if ( is_array( $response ) && ! is_wp_error( $response ) ) {
			$http_code  = wp_remote_retrieve_response_code( $response );
			if($http_code == '200') {

				$body = wp_remote_retrieve_body( $response );	
				$body = json_decode($body, true);
				if( is_numeric($body['co2cost']) ) {
					return $body['co2cost'];
				}

			} elseif ($http_code == '502') {
				neutralcart_error_reporting($response);
			}
		}

		return $co2cost;

	}


	public static function add_option_to_checkout( $checkout ){
		global $woocommerce;
					
		// don't output anything if the sessions are not set 
		if(isset($_SESSION['neutralcart_co2cost']) && $_SESSION['neutralcart_co2cost'] > 0) {

			$neutralcart_logo 			 = sanitize_text_field(get_option('neutralcart_logo', 'co2donate-badge-standard.svg'));
			$neutralcart_status 		 = sanitize_text_field(get_option('neutralcart_status', 'enable'));
			$neutralcart_status_feedback = '';
			$neutralcart_co2cost_including_tax = $_SESSION['neutralcart_co2cost']*(1+self::$neutralcart_tax_percentage);
			$neutralcart_co2cost_including_tax_wc_rounded = floor($neutralcart_co2cost_including_tax * 100) / 100;

			if($neutralcart_status == 'test') {
				$neutralcart_status_feedback = '<span class="neutralcart_status_feedback">'.__('[TEST MODE]', 'neutralcart').'</span>';
			}

			echo '<div id="neutralcart_label">';
			echo '<div id="neutralcart_label_checkbox">';
		    woocommerce_form_field( 'neutralcart_woocommerce', array(
		        'type'          => 'checkbox',
		        'required'		=> false,
		        'class'         => array('neutralcart_woocommerce form-row-wide'),
		        'label'         => '<span class="neutralcart_label_text">'.__('I want to carbon offset my purchase' ,'neutralcart').
		        					' <span class="neutralcart_no_wrap">('.wc_price($neutralcart_co2cost_including_tax_wc_rounded).') '.
		        					'<span class="neutralcart_tooltip">'.
		        						'<span class="dashicons dashicons-info"></span>'.
		        						'<span class="neutralcart_tooltiptext">'.
		        							__('Neutral Cart makes it easy to offset your carbon footprint. ', 'neutralcart').
		        							' <a href="'.__(NEUTRALCART_BADGE_LINK,'neutralcart').'?utm_source='.$_SERVER['SERVER_NAME'].'" target="_blank">'.__('Read more about Neutral Cart and why you should make climate compensate your purchases ', 'neutralcart').'</a>'.
	        							'</span>
									</span>
									'.$neutralcart_status_feedback.'<!-- end neutralcart_tooltip --></span><!-- end neutralcart_no_wrap --></span><!-- neutralcart_label_text -->',
		        'placeholder'   => '',
		        ), $neutralcart_co2cost_including_tax );
		    echo '</div> <!-- end of neutralcart_label_checkbox -->';
		    echo '<a href="'.__(NEUTRALCART_BADGE_LINK,'neutralcart').'?utm_source='.$_SERVER['SERVER_NAME'].'" target="_blank" id="neutralcart_label_logo">';
		    echo '<img src="'.NEUTRALCART_IMG.$neutralcart_logo.'" alt="badge" id="compensate_label_logo_svg" />';
		    echo '</a><!-- end of neutralcart_label_logo -->'; 
		    echo '</div><!-- end of neutralcart_label -->';
		
		}
	}


}new Neutralcart_Frontend();



?>