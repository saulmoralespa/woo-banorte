<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 30/07/18
 * Time: 10:23 AM
 */

class Banorte_Bank_BB_Plugin
{

    /**
     * Filepath of main plugin file.
     *
     * @var string
     */
    public $file;
    /**
     * Plugin version.
     *
     * @var string
     */
    public $version;
    /**
     * Absolute plugin path.
     *
     * @var string
     */
    public $plugin_path;
    /**
     * Absolute plugin URL.
     *
     * @var string
     */
    public $plugin_url;
    /**
     * Absolute path to plugin includes dir.
     *
     * @var string
     */
    public $includes_path;
    /**
     * Flag to indicate the plugin has been boostrapped.
     *
     * @var bool
     */
    private $_bootstrapped = false;
    /**
     * @var WC_Logger
     */
    public $logger;

    public function __construct($file, $version)
    {
        $this->file = $file;
        $this->version = $version;
        // Path.
        $this->plugin_path = trailingslashit(plugin_dir_path($this->file));
        $this->plugin_url = trailingslashit(plugin_dir_url($this->file));
        $this->includes_path = $this->plugin_path . trailingslashit('includes');
        $this->logger = new WC_Logger();
    }

    public function banorte_run()
    {
        try {
            if ($this->_bootstrapped) {
                throw new Exception(__('Banorte Woocommerce: can only be called once', 'woo-banorte'));
            }
            $this->_run();
            $this->_bootstrapped = true;
        } catch (Exception $e) {
            if (is_admin() && !defined('DOING_AJAX')) {
                do_action('notices_action_tag_banorte_bank_bb', $e->getMessage());
            }
        }
    }

    protected function _run()
    {
        require_once($this->includes_path . 'class-wc-payment-banorte-bank-bb.php');
        add_filter('plugin_action_links_' . plugin_basename($this->file), array($this, 'plugin_action_links'));
        add_filter('woocommerce_payment_gateways', array($this, 'woocommerce_banorte_bank_add_gateway'));
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp', array($this, 'return_params_banorte_bank_bb'));
    }

    public function plugin_action_links($links)
    {
        $plugin_links = array();
        $plugin_links[] = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=banorte_bank') . '">' . esc_html__('Settings', 'woo-banorte') . '</a>';
        $plugin_links[] = '<a href="https://github.com/saulmoralespa/woo-banorte" target="_blank">' . esc_html__('Documentation', 'woo-banorte') . '</a>';
        return array_merge($plugin_links, $links);
    }

    public function woocommerce_banorte_bank_add_gateway($methods)
    {
        $methods[] = 'WC_Payment_Banorte_Bank_BB';
        return $methods;
    }

    public function enqueue_scripts()
    {
        if (is_wc_endpoint_url( 'order-pay' )){
            wp_enqueue_script( 'banorte-bank-bb', $this->plugin_url . 'assets/js/banorte-bank-bb.js', array( 'jquery' ), $this->version, true );
            wp_enqueue_script( 'card-banorte-bank-bb', $this->plugin_url . 'assets/js/card.js', array( 'jquery' ), $this->version, true );
            wp_localize_script( 'banorte-bank-bb', 'banorteBankWoo', array(
                'msjvalidCard' => __('Be sure to enter a valid card number','woo-banorte'),
                'msjTypeCard' => __('Type of card not allowed, try with visa, mastercard','woo-banorte'),
                'redirect' => __('Redirecting to banorte..','woo-banorte')
            ) );
            wp_enqueue_style('frontend-banorte-bank-bb', $this->plugin_url . 'assets/css/banorte-bank-bb.css', array(), $this->version, null);
        }
    }

    public function return_params_banorte_bank_bb()
    {
        if (!isset($_REQUEST['NUMERO_CONTROL']) && !isset($_REQUEST['RESULTADO_PAYW']))
            return;

        $request = $_SERVER['REQUEST_METHOD'];
        $reference = $_REQUEST['REFERENCIA'];

        $order_id = (int)$_REQUEST['NUMERO_CONTROL'];
        $order = new WC_Order($order_id);
        $wc_banorte_bank_bb = new WC_Payment_Banorte_Bank_BB();

        $message = '';
        $messageClass = '';

        $result = $_REQUEST['RESULTADO_PAYW'];

        if ( $order->get_status() != 'completed' &&  $order->get_status() != 'processing'){
            switch ($result){
                case 'A':
                    $order->payment_complete($order_id);
                    $message = __('Successful payment','woo-banorte');
                    $messageClass = 'woocommerce-message';
                    $order->add_order_note(sprintf(__('Successful payment, reference:(%s).', 'woo-banorte'), $reference));
                    break;
                case 'D':
                case 'R':
                case 'T':
                    $message = __('Payment failed','woo-banorte');
                    $messageClass = 'woocommerce-error';
                    $order->update_status('failed');
                    $order->add_order_note(__('Payment failed','woo-banorte'));
                    $wc_banorte_bank_bb->restore_order_stock($order_id);
                    break;
            }
        }elseif($order->get_status() == 'completed'){
            $message = __('Successful payment','woo-banorte');
            $messageClass = 'woocommerce-message';
        }elseif ($order->get_status() == 'processing'){
            $message = __('Payment in the initiated state','woo-banorte');
            $messageClass = 'woocommerce-info';
        }

        if ($request == 'GET'){
            $redirect_url = add_query_arg( array('msg'=> urlencode($message), 'type'=> $messageClass), $order->get_checkout_order_received_url() );
            wp_redirect( $redirect_url );
        }

    }
}