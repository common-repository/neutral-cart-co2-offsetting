<?php
class Neutralcart_Admin_Settings
{
	public static function register_admin_settings(){
		add_settings_section("neutralcart-option-section", __("Neutral Cart Settings",'neutralcart'), null, "neutralcart-options");

		register_setting("neutralcart-option-section", "neutralcart_status");
		register_setting("neutralcart-option-section", "neutralcart_label_css");
		register_setting("neutralcart-option-section", "neutralcart_auto_checked");
		register_setting("neutralcart-option-section", "neutralcart_label_placement");
		register_setting("neutralcart-option-section", "neutralcart_label_style");
		register_setting("neutralcart-option-section", "neutralcart_logo");

	  	//register default admin settings
        if(empty(get_option('neutralcart_status'))) { 
        	add_option( 'neutralcart_status', 'test');
        }
        if(empty(get_option('neutralcart_label_css'))) {
        	add_option( 'neutralcart_label_css', '');
        }
        if(empty(get_option('neutralcart_auto_checked'))) {
        	add_option( 'neutralcart_auto_checked', 'disable');
        }
        if(empty(get_option('neutralcart_label_placement'))) {
        	add_option( 'neutralcart_label_placement', 'woocommerce_review_order_before_payment');
        }
        if(empty(get_option('neutralcart_label_style'))) {
        	add_option( 'neutralcart_label_style', 1);
        }
        if(empty(get_option('neutralcart_logo'))) {
        	add_option( 'neutralcart_logo', 'neutral-cart-badge-standard.svg');
        }

        write_log('neutralcart_status: '. get_option('neutralcart_status') );
	}

	public static function admin_settings(){
		?>
			
			<form method="post" action="options.php">
				<?php
				settings_fields("neutralcart-option-section");
	            do_settings_sections("neutralcart-options");
	            $neutralcart_status				= sanitize_text_field(get_option('neutralcart_status','test'));
	            $neutralcart_label_css			= get_option('neutralcart_label_css', '');
	            $neutralcart_auto_checked		= sanitize_text_field(get_option('neutralcart_auto_checked','disable'));
	           	$neutralcart_label_placement 	= sanitize_text_field(get_option('neutralcart_label_placement', 'woocommerce_review_order_before_payment'));
	           	$neutralcart_label_style   		= sanitize_text_field(get_option('neutralcart_label_style',1));
	           	$neutralcart_logo   			= sanitize_text_field(get_option('neutralcart_logo','neutral-cart-badge-standard.svg'));

	            ?>
	  
	            <?php settings_errors(); ?>
	            <table class="form-table">
	            	<tbody>
						<tr>
							<th scope="row"><label><?php _e( 'Status', 'neutralcart'); ?><label></th>
							<td>
								<select name="neutralcart_status" id="neutralcart_status">
									<option value="enable" <?php if($neutralcart_status=='enable'){echo 'selected';} ?>>
										<?php _e('Enable', 'neutralcart'); ?>
									</option>
									<option value="test" <?php if($neutralcart_status=='test'){echo 'selected';} ?>>
										<?php _e('Test mode (Neutral Cart checkout box only visible for editors/admin)', 'neutralcart'); ?>
									</option>
									<option value="disable" <?php if($neutralcart_status=='disable'){echo 'selected';} ?>><?php _e('Disable', 'neutralcart'); ?></option>
								</select>
							</td>
						</tr>

						<tr>
							<th scope="row"><label><?php _e( 'Select label style', 'neutralcart'); ?><label></th>
							<td>
								<select name="neutralcart_label_style" id="neutralcart_label_style">
									<option value="1" <?php if($neutralcart_label_style==1){echo 'selected';} ?>><?php _e('Default (bright)', 'neutralcart'); ?></option>
									<option value="2" <?php if($neutralcart_label_style==2){echo 'selected';} ?>><?php _e('Dark', 'neutralcart'); ?></option>
								</select>
							</td>
						</tr>

						<tr>
							<th scope="row"><label><?php _e( 'Select Neutral Cart logo style', 'neutralcart'); ?><label></th>
							<td>
								<select name="neutralcart_logo" id="neutralcart_logo">
									<option value="neutral-cart-badge-standard.svg" <?php if($neutralcart_logo=='neutral-cart-badge-standard.svg'){echo 'selected';} ?>><?php _e('Default (green)', 'neutralcart'); ?></option>
									<option value="neutral-cart-badge-dark.svg" <?php if($neutralcart_logo=='neutral-cart-badge-dark.svg'){echo 'selected';} ?>><?php _e('Dark', 'neutralcart'); ?></option>
									<option value="neutral-cart-badge-bright.svg" <?php if($neutralcart_logo=='neutral-cart-badge-bright.svg'){echo 'selected';} ?>><?php _e('Bright', 'neutralcart'); ?></option>
								</select>
							</td>
						</tr>

						<tr>
							<th scope="row"><label><?php _e( 'Custom CSS', 'neutralcart'); ?></labe></td>
							<td>
								<textarea class="large-text code" name="neutralcart_label_css" id="neutralcart_label_css" placeholder=""><?php echo $neutralcart_label_css; ?></textarea>
								
							</td>
						</tr>
						
						<tr>
							<th scope="row"><label><?php _e( 'Auto-checked / Auto-applied the fees', 'neutralcart'); ?><label></th>
							<td>
								<select name="neutralcart_auto_checked" id="neutralcart_auto_checked">
									<option value="enable" <?php if($neutralcart_auto_checked=='enable'){echo 'selected';} ?>>
										<?php _e('Enable', 'neutralcart'); ?>
									</option>
									<option value="disable" <?php if($neutralcart_auto_checked=='disable'){echo 'selected';} ?>>
										<?php _e('Disable', 'neutralcart'); ?>
									</option>
								</select>
							</td>
						</tr>
						
						
						<tr>
							<th scope="row"><label><?php _e( 'Placement', 'neutralcart'); ?></labe></td>
							<td>
				                <select name="neutralcart_label_placement" style="display:block;" id="neutralcart_label_placement">
				                    
			                     	<option value="woocommerce_review_order_before_payment" <?php if($neutralcart_label_placement == 'woocommerce_review_order_before_payment') echo "selected" ?>>
						                    	<?php _e('Before payment (default)', 'neutralcart'); ?>
				                    </option>

				                    <option value="woocommerce_checkout_before_customer_details" <?php if($neutralcart_label_placement == 'woocommerce_checkout_before_customer_details') echo "selected" ?>>
						                    	<?php _e('Before customer details', 'neutralcart'); ?>
				                    </option>

				                    <option value="woocommerce_after_order_notes" <?php if($neutralcart_label_placement == 'woocommerce_woocommerce_after_order_notes') echo "selected" ?>>
				                    	<?php _e('After order notes', 'neutralcart'); ?>
				                	</option>
				                    <option value="woocommerce_after_checkout_billing_form" <?php if($neutralcart_label_placement == 'woocommerce_after_checkout_billing_form') echo "selected" ?>>
						                    	<?php _e('After billing details form', 'neutralcart'); ?>
				                    </option>

				                     

				                </select>

							</td>
						</tr>
						
					</tbody>
	            </table>                

	            <div id="wps_custom_fees_add_more">
	            	<input type="hidden" id="current_number_fees" value="1" />
	            </div>
	            <div class="wafoc-bottom-line" style="width: 100%; height: 50px;">
	            	<div class="wafoc-bottom-line-button" style="float: left;">
	            		<?php 
							submit_button();
						?>
	            	</div>
	            </div>	
			</form>
			<div class="neutralcart-contact-details">
				<p>&nbsp;</p>
				<h3><?php _e('Contact', 'neutralcart'); ?></h3>
				<p>
					<a href="mailto:<?php echo NEUTRALCART_AUTHOR_EMAIL; ?>"><?php echo NEUTRALCART_AUTHOR_EMAIL; ?></a>
				</p>
				<p>
					<a href="<?php echo NEUTRALCART_AUTHOR_WEB; ?>"><?php echo NEUTRALCART_AUTHOR_WEB; ?></a>
				</p>
			</div>
			

		    <style type="text/css">

		    	.neutralcart-contact-details {
		    		margin:30px 0px;
		    	}

		    	h2{
				    border-bottom: 1px solid;
				    padding: 8px;
				    font-size: 24px;
				    font-weight: 200;
				    margin-right: 20px;
		    	}
		    	
		    	input[type="text"], select{
		    		height: 34px !important;
				  width: 99% !important;
				  border-radius: 3px;
				  border: 1px solid transparent;
				  border-top: none;
				  border-bottom: 1px solid #DDD;
				
		    	}
		    </style>
		<?php
	}

}new Neutralcart_Admin_Settings();

?>