<?php
/*
Plugin Name: WooCommerce Trustpay Gateway
Description: TrustPay payment gateway for WooCommerce.
Version: 1.1.0
Author: trustpay
Author URI: http://trustpay.biz

License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

//wctrustpay

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Trustpay class which sets the gateway up for us
 */
class WC_Trustpay {

	/**
	 * Constructor
	 */
	function __construct() {
		define( 'TP_OAUTH_PROVIDER_DEPENDENCY', 'oauth-provider/oauth-provider.php' );
		define( 'WC_TRUSTPAY_VERSION', '1.1.0' );
		define( 'WC_TRUSTPAY_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
		define( 'WC_TRUSTPAY_MAIN_FILE', __FILE__ );

		// Deactivate plugin if OAuth Provider plugin is not present/activated
		add_action( 'admin_init', array( $this, 'activation_check' ) );

		// Actions
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ) );
	}

	/**
	 * Deactivate TrustPay Gateway if OAuth Provider is not activated
	 */
	function activation_check() {
		if ( ! $this->oauth_provider_check() ) {
			if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
				deactivate_plugins( plugin_basename( __FILE__ ) );
				add_action( 'admin_notices', array( $this, 'disabled_notice' ) );
				if ( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}
			}
		}
	}

	/**
	 * Method that checks if plugin could be initiated.
	 */
	function oauth_provider_check() {

		if ( is_multisite() ) {
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}

			if ( is_plugin_active_for_network( TP_OAUTH_PROVIDER_DEPENDENCY ) ) {
				return true;
			}
		} else {
			$active_plugins = get_option( 'active_plugins' );
			foreach ( $active_plugins as $plugin ) {
				if ( TP_OAUTH_PROVIDER_DEPENDENCY == $plugin ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Disabled notice. Will be shown when OAuth Provider is not present
	 * or not activated.
	 */
	function disabled_notice() {
		?>
			<div class="error">
				<p>
					<?php echo __( sprintf( 'WooCommerce Trustpay Gateway requires <a href="%s" target="_blank">OAuth Provider</a> plugin to be installed!', 'https://wordpress.org/plugins/oauth-provider/'), 'wctrustpay' ); ?>
				</p>
			</div>
		<?php
	}

	/**
	 * Init localisations and files
	 */
	function init() {

		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_fallback_notice' ) );
			return;
		}

		// Includes
		include_once( 'includes/class-wp-gateway-trustpay.php' );

		// Localisation
		load_plugin_textdomain( 'wctrustpay', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	function woocommerce_fallback_notice() {
		return
			'<div class="error">' .
				'<p>' . __( sprintf( 'WooCommerce TrustPay Gateway depends on the last version of <a href="%s" target="_blank">WooCommerce</a> to work!', 'http://wordpress.org/extend/plugins/woocommerce/' ), 'wctrustpay' ) . '</p>' .
			'</div>';
	}

	/**
	 * Register the gateway for use
	 */
	function register_gateway( $methods ) {
		$methods[] = 'WC_Gateway_Trustpay';
		return $methods;
	}

}

$wc_trustpay = new WC_Trustpay();
