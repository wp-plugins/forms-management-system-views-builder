<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
function fvb_get_option( $option, $section, $default = '' ) {

	$options = get_option( $section );

	if ( isset( $options[ $option ] ) ) {
		return $options[ $option ];
	}

	return $default;
}

function fvb_get_contact_forms() {
	$array    = array();
	$forms    = get_posts( array( 'post_type' => 'fms_contact_forms', 'numberposts' => - 1 ) );
	$array[0] = __( "None", 'fvb' );
	if ( $forms ) {
		foreach ( $forms as $form ) {
			$array[ $form->ID ] = $form->post_title;
		}
	}

	return $array;
}

function fvb_get_posting_forms() {
	$array = array();
	$forms = get_posts( array( 'post_type' => 'fms_forms', 'numberposts' => - 1, 'suppress_filters' => false ) );

	$array[0] = __( 'Select Form', 'fvb' );
	if ( $forms ) {
		foreach ( $forms as $form ) {
			$array[ $form->ID ] = $form->post_title;
		}
	}

	return $array;
}

function fvb_get_user_roles() {
	global $wp_roles;

	$roles                   = array();
	$roles['logged_in_only'] = "Logged In Users Only";

	if ( ! isset( $wp_roles ) ) {
		$wp_roles = new WP_Roles();
	}

	$roles = array_merge( $roles, $wp_roles->get_names() );

	return $roles;
}

function FVB_CanAccess( $view_settings ) {

	$user      = wp_get_current_user();
	$canAccess = false;
	foreach ( $view_settings['roles'] as $role ) {
		if ( in_array( $role, $user->roles ) ) {
			$canAccess = true;
		}
	}

	if ( ( $canAccess == false ) && is_user_logged_in() && in_array( 'logged_in_only', $view_settings['roles'] ) ) {
		$canAccess = true;
	}

	return $canAccess;
}