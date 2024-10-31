<?php
class Neutralcart_Main
{
	public function __construct()
	{
		add_action('woocommerce_init', array('Neutralcart_Admin', 'init'));
		add_action('admin_init',array($this, 'neutralcart_admin_scripts'));
    	add_action('wp_enqueue_scripts', array($this,'neutralcart_frontend_scripts'));

		$this->neutralcart_activated(); // run this to make sure that it's an active shop
		
		add_action('woocommerce_order_status_changed', array($this, 'neutralcart_transaction_manager'),99,3);
		
	}

	public static function neutralcart_frontend_scripts() {
		wp_register_style('neutralcart', NEUTRALCART_AST.'/css/style.css');
    	wp_enqueue_style('neutralcart');

		if (!wp_style_is( 'fontawesome', 'enqueued' ) || !wp_style_is( 'font-awesome', 'enqueued' )) {
			wp_register_style( 'fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/fontawesome.min.css', false, '5.11.2' );
			wp_enqueue_style( 'fontawesome' );
		} 

	    if ( ! wp_script_is( 'jquery', 'enqueued' )) {
	        //Enqueue
	        wp_enqueue_script( 'jquery' );
		}
	}

	public static function neutralcart_admin_scripts(){
		wp_register_script('neutralcart', NEUTRALCART_JS.'/neutralcart.js', array('jquery'),'1.1', true);
		wp_enqueue_script('neutralcart');
	}


	
	function signupShop() {

		$url = NEUTRALCART_API_URL . '/add-shop';
		$args = array(
			'headers' 	  => array('Content-Type' => 'application/json; charset=utf-8'),
			'body' 		  => json_encode(array(
				'domain'  => esc_url_raw($_SERVER['SERVER_NAME']),
	       		'email'   => sanitize_email(get_option('admin_email'))
			)),
			'data_format' => 'body'
		);

		$response = wp_remote_post( $url, $args );

		if ( is_array( $response ) && ! is_wp_error( $response ) ) {
			$http_code  = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );	
			if($http_code == '200') {
				$body = json_decode($body, true);
				add_option('neutralcart_id', sanitize_text_field($body['shopId']));
			} elseif ($http_code == '502') {
				$message =	$result . "\n" . "\n" .
	   					"api-call: " . $url . "\n" . 
	   				  	"domain: " . $_SERVER['SERVER_NAME'] . "\n";
	   			neutralcart_error_reporting($message);
			}
		}
	}

	//This function is called when the user activates the plugin.
	function neutralcart_activated() {
	    
	    if (!get_option('neutralcart_id', false)) { //if neutralcart_id is not stored in the database:
	        
	    	//signup shop
	        $this->signupShop();

	    }
	}

	function neutralcart_transaction_manager($order_id, $old_status, $new_status) {

		global $woocommerce;
        $shopId = get_option('neutralcart_id', false);
        $order = wc_get_order($order_id);
        $fees = $order->get_fees();
        $neutralcart_status	= get_option('neutralcart_status','test');


		// Here are the status definitions for reference:
		//  - Pending – Order received (unpaid)
		//  - Failed – Payment failed or was declined (unpaid)
		//  - Processing – Payment received and stock has been reduced- the order is awaiting fulfilment
		//  - Completed – Order fulfilled and complete – requires no further action
		//  - On-Hold – Awaiting payment – stock is reduced, but you need to confirm payment
		//  - Cancelled – Cancelled by an admin or the customer – no further action required
		//  - Refunded – Refunded by an admin – no further action required
		//  - flow chart: https://docs.woocommerce.com/wp-content/uploads/2013/05/woocommerce-order-process-diagram.png

		/** LOGIC **/
		// if new_status = completed or processing => try to update the order with status = pending
		// if update does not find any matching order, create new order instead
		// for all other new_status, update order with new status = 'cancelled'


        switch ($new_status) {
            case "completed":
            case "processing":
                
                //update the transaction with new status = pending [response is either 0 or the httpcode from the API]
                $response = $this->updateTransaction($shopId, $order_id, 'pending');

                //if the order_id is not yet stored in the DB (error code 405 from above API call), create a new order
                if($response == 405) {

	                $co2offsetValue = 0;
	                $neutralcartdTransaction = false;

	                //check if the purchaser donated/compensated
	                foreach ($fees as $fee) {
	                    
	                    if ($fee->get_name() == NEUTRALCART) {
	                       	$neutralcartdTransaction = true;
	                        break;
	                    }
	                }

	                //co2offsetValue including tax
	                $co2offsetValue = $_SESSION['neutralcart_co2cost']*(1+Neutralcart_Frontend::$neutralcart_tax_percentage);

		            //prepare and filter Items
		            $filteredItems = $this->filterItems($order->get_items());
		            
		            //create the transaction
		            $response = $this->createTransaction($shopId, $order_id, $filteredItems, $co2offsetValue, $neutralcartdTransaction, $neutralcart_status);


					unset($_SESSION['neutralcart_cart_contents']);
	                unset($_SESSION['neutralcart_co2cost']);
	            }
                break;

            case "refunded":
            case "failed":
            case "on-hold":
            case "cancelled":
            case "pending":
                foreach ($fees as $fee) {

                    if ($fee->get_name() == NEUTRALCART) {
                        //update the transaction with new status = cancelled [response is either 0 or the httpcode from the API]
                        $this->updateTransaction($shopId, $order_id, 'cancelled');
                        break;
                    }
                }
                break;
        }
    }

    function updateTransaction($shopId, $orderId, $orderStatus) {
    	
    	if( empty($shopId) || empty($orderId) ) {
			return 0;
		}


    	$data = array(
	       'shopId' => sanitize_text_field($shopId),
	       'orderId' => (int)$orderId,
	       'status' => sanitize_text_field($orderStatus)
	    );

	    $url = NEUTRALCART_API_URL . '/update-transaction-status';
		$args = array(
			'headers' 	  => array('Content-Type' => 'application/json; charset=utf-8'),
			'body' 		  => json_encode($data),
			'data_format' => 'body'
		);

		$response = wp_remote_post( $url, $args );
		$http_code = 0;

		if ( is_array( $response ) && ! is_wp_error( $response ) ) {
			$http_code  = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );	
			if($http_code == '502') {
				$message =	$result . "\n" . "\n" .
	   					"api-call: " . $url . "\n" . 
	   				  	"domain: " . $_SERVER['SERVER_NAME'] . "\n" . 
	   				  	"orderId: " . $orderId . "\n";
	   			neutralcart_error_reporting($message);
			}
		}

	    return $http_code;

    }



	function createTransaction($shopId, $orderId, $items, $co2Cost, $neutralcartdTransaction,$neutralcart_status) {

    	if( empty($shopId) || empty($orderId) || is_bool($neutralcartdTransaction) === false ) {
			return 0;
		}

    	$data = array(
	       'shopId' 	=> sanitize_text_field($shopId),
	       'orderId' 	=> (int)$orderId,
	       'items' 		=> $items,
	       'co2Cost' 	=> (double)$co2Cost,
	       'donate' 	=> (boolean)$neutralcartdTransaction,
	       'mode' 		=> sanitize_text_field($neutralcart_status),
	       'currency' 	=> get_woocommerce_currency()
	    );

	    $url = NEUTRALCART_API_URL . '/add-transaction';
		$args = array(
			'headers' 	  => array('Content-Type' => 'application/json; charset=utf-8'),
			'body' 		  => json_encode($data),
			'data_format' => 'body'
		);

		$response = wp_remote_post( $url, $args );

		if ( is_array( $response ) && ! is_wp_error( $response ) ) {
			$http_code  = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );
			if($http_code == '502') {
			    //checking only for error code = 502 - all other errors are managed at the backend.
		   		$message =	$result . "\n" . "\n" .
		   					"api-call: " . $url . "\n" .
		   					"shopId: " . $shopId . "\n" . 
		   				  	"domain: " . $_SERVER['SERVER_NAME'] . "\n" . 
		   				  	"orderId: " . $orderId . "\n" .
		   				  	"items: " . print_r($items, true) . "\n" .
		   				  	"co2Cost: " . $co2Cost;
		
		   		neutralcart_error_reporting($message);
			}
		}

	    return $response;
    }


    public static function filterItems($items) {

    	if( empty($items) ) {
    		return 0;
    	}
      	
      	$filteredItems = array();

        // Iterating through each "line" items in the order
		foreach ($items as $item_id => $item_data) {

		    array_push($filteredItems, array(
		    	'name' => wp_strip_all_tags($item_data->get_product()->get_name()),
		    	'description' => wp_strip_all_tags($item_data->get_product()->get_description()),
		    	'categories' => wp_strip_all_tags(wc_get_product_category_list( $item_data->get_product_id() )),
		    	'quantity' => (int)$item_data->get_quantity(),
		    	'unitPrice' => (double)($item_data->get_subtotal()+$item_data->get_subtotal_tax())/$item_data->get_quantity()
		    ));
		}
		return $filteredItems;
    }


}new Neutralcart_Main();


register_activation_hook( __FILE__, array('neutralcart_Main', 'neutralcartActivated'));



?>