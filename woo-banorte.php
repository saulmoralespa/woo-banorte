<?php
/*
Plugin Name: Banorte Woocommerce
Description: Integración del banco de banorte con woocommerce
Version: 1.0.1
Author: Saul Morales Pacheco
Author URI: http://saulmoralespa.com
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: woo-banorte
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; //Exit if accessed directly
}
if(!defined('BANORTE_BANK_BB_VERSION')){
    define('BANORTE_BANK_BB_VERSION', '1.0.1');
}
add_action('plugins_loaded','banorte_bank_bb_init',0);

function banorte_bank_bb_init(){
    load_plugin_textdomain('woo-banorte', FALSE, dirname(plugin_basename(__FILE__)) . '/languages');

    if (!requeriments_banorte_bank_bb()){
        return;
    }

    banorte_bank_bb()->banorte_run();
}

add_action('notices_action_tag_banorte_bank_bb', 'banorte_bank_bb_notices', 10, 1);
function banorte_bank_bb_notices($notice){
    ?>
    <div class="error notice">
        <p><?php echo $notice; ?></p>
    </div>
    <?php
}

function requeriments_banorte_bank_bb(){
    if ( version_compare( '5.6.0', PHP_VERSION, '>' ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            $php = __( 'Banorte Woocommerce: Requires php version 5.6.0 or higher.', 'woo-banorte' );
            do_action('notices_action_tag_banorte_bank_bb', $php);
        }
        return false;
    }

    $openssl_warning = __( 'Banorte Woocommerce: Requires OpenSSL >= 1.0.1 to be installed on your server', 'woo-banorte' );
    if ( ! defined( 'OPENSSL_VERSION_TEXT' ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            do_action('notices_action_tag_banorte_bank_bb', $openssl_warning);
        }
        return false;
    }

    preg_match( '/^(?:Libre|Open)SSL ([\d.]+)/', OPENSSL_VERSION_TEXT, $matches );
    if ( empty( $matches[1] ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            do_action('notices_action_tag_banorte_bank_bb', $openssl_warning);
        }
        return false;
    }

    if ( ! version_compare( $matches[1], '1.0.1', '>=' ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            do_action('notices_action_tag_banorte_bank_bb', $openssl_warning);
        }
        return false;
    }

    if (!in_array(get_woocommerce_currency(), array('MXN'))){
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            $currency = __('Banorte Woocommerce: Requires currency to be MXN ', 'woo-banorte' )  . sprintf(__('%s', 'woo-banorte' ), '<a href="' . admin_url() . 'admin.php?page=wc-settings&tab=general#s2id_woocommerce_currency">' . __('Click here to configure', 'woo-banorte') . '</a>' );
            do_action('notices_action_tag_banorte_bank_bb', $currency);
        }
        return false;
    }

    return true;
}

function banorte_bank_bb(){
    static $plugin;
    if(!isset($plugin)){
        require_once ('includes/class-banorte-bank-bb-plugin.php');
        $plugin = new Banorte_Bank_BB_Plugin(__FILE__, BANORTE_BANK_BB_VERSION);

    }
    return $plugin;
}