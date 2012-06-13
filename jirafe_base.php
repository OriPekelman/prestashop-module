<?php

class Jirafe_Base extends Module
{
    // The Jirafe Client communicates with the Jirafe web service
    private $jirafeClient;

    public function __construct()
    {
        // Require/Autoload the other files
        require_once _PS_MODULE_DIR_ . 'jirafe/vendor/jirafe-php-client-src/Jirafe/Autoloader.php';
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

    public function getJirafeClient()
    {
        if (null === $this->jirafeClient) {
            // Get client connection
            $timeout = 10;
            $useragent = 'jirafe-ecommerce-phpclient/' . $this->version;
            $connection = new Jirafe_HttpConnection_Curl(JIRAFE_API_URL, JIRAFE_API_PORT, $timeout, $useragent);
            // Get client
            $ps = $this->getPrestashopClient();
            $this->jirafeClient = new Jirafe_Client($ps->get('token'), $connection);
        }

        return $this->jirafeClient;
    }
}
