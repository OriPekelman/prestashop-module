<?php

class Jirafe_Base extends Module
{
    // The Jirafe Client communicates with the Jirafe admin web service
    private $jirafeAdminClient;
    private $jirafeTrackerClient;

    public function __construct()
    {
        // Require/Autoload the other files
        require_once _PS_MODULE_DIR_ . 'jirafe/api_client/Jirafe/Autoloader.php';
        Jirafe_Autoloader::register();

        // for prestashop 1.4
        if (function_exists('__autoload')) spl_autoload_register('__autoload');

        $this->name = 'jirafe';
        $this->tab = 'analytics_stats';
        $this->version = '1.2';

        //The constructor must be called after the name has been set, but before you try to use any functions like $this->l()
        parent::__construct();

        $this->page = basename(__FILE__, '.php');

        $this->author = $this->l('Jirafe Inc.');
        $this->displayName = $this->l('Analytics for ecommerce');
        $this->description = $this->l('The best analytics for ecommerce merchants.  Deeply integrated into the Prestashop platform.');

        /** Backward compatibility */
        require(_PS_MODULE_DIR_.'/jirafe/backward_compatibility/backward.php');

        // Confirmation of uninstall
        $this->confirmUninstall = $this->l('Are you sure you want to remove Jirafe analytics integration for your site?');
    }

    public function getJirafeAdminClient()
    {
        if (null === $this->jirafeAdminClient) {
            // Get client connection
            $timeout = 10;
            $useragent = 'jirafe-ecommerce-phpclient/' . $this->version;
            $connection = new Jirafe_HttpConnection_Curl(JIRAFE_API_URL, JIRAFE_API_PORT, $timeout, $useragent);
            // Get client
            $ps = $this->getPrestashopClient();
            $this->jirafeAdminClient = new Jirafe_AdminApi_Client($ps->get('token'), $connection);
        }

        return $this->jirafeAdminClient;
    }

    public function getJirafeTrackerClient()
    {
        if (null === $this->jirafeTrackerClient) {
            $ps = $this->getPrestashopClient();
            $this->jirafeTrackerClient = new Jirafe_TrackerApi_Client(JIRAFE_TRACKER_URL, $ps->get('token'));
        }

        return $this->jirafeTrackerClient;
    }

    /**
     * Hook which gets called when a user adds something to their cart.
     * We then send Jirafe the updated cart information
     *
     * @param array $params variables from the front end
     */
    public function hookCart($params)
    {
        $ps = $this->getPrestashopClient();
        $tc = $this->getJirafeTrackerClient();

        $cart = $ps->getCart($params);

        try {
            $tc->updateCart($cart);
        } catch (Exception $e) {
            // do nothing for now
        }
    }

    /**
     * Hook which gets called when a user makes a new order
     * We then send Jirafe the order information
     *
     * @param array $params variables from the front end
     */
    public function hookOrderConfirmation($params)
    {
        $ps = $this->getPrestashopClient();
        $tc = $this->getJirafeTrackerClient();

        $order = $ps->getOrder($params);

        try {
            $tc->createOrder($order);
        } catch (Exception $e) {
            // do nothing for now
        }
    }
}
