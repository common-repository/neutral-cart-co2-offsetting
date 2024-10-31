<?php
class Neutralcart_Admin
{
	public static function init(){
		add_action('admin_menu', array( 'Neutralcart_Admin', 'extra_post_info_menu' ) );
		add_action('admin_init', array('Neutralcart_Admin_Settings',"register_admin_settings"));
	}
	public static function extra_post_info_menu(){
		$page_title = 'Neutral Cart';
		$menu_title = 'Neutral Cart';
		$capability = 'manage_options';
		$menu_slug  = 'neutralcart-admin-menu';
		$function   = array('Neutralcart_Admin_Settings','admin_settings');
		$icon_url   = 'dashicons-admin-site-alt';
		$position   = 20;
		add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url ); 
	}


}new Neutralcart_Admin();

?>