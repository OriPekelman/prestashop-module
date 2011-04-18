<?php

// Add this to the include path so that we can use Zend_Http_Client
set_include_path(get_include_path() . PATH_SEPARATOR. dirname(__FILE__));

class PsApi
{
    public static function getApplication()
    {
        $data = array(
            'name' => Configuration::get('PS_SHOP_NAME'),
            'url' => Tools::getShopDomain(true)
        );
        
        $token = Configuration::get('JIRAFE_TOKEN');
        if (!empty($token)) {
            $data['token'] = $token;
        }
        
        $appId = Configuration::get('JIRAFE_ID');
        if (!empty($appId)) {
            $data['app_id'] = $appId;
        }
        
        return $data;
    }
    
    public static function setApplication($app)
    {
        if (!empty($app['app_id'])) {
            Configuration::updateValue('JIRAFE_ID', $app['app_id']);
        }
        if (!empty($app['token'])) {
            Configuration::updateValue('JIRAFE_TOKEN', $app['token']);
        }
    }
    
    /**
     * Get the Jirafe users, which are the PS employees with their Jirafe tokens
     *
     * @return array A list of Jirafe users
     */
    public static function getUsers()
    {
        $users = array();
        
        // Get the Prestashop Employees
        $psEmployees = PsApi::getPsEmployees();

        // Get the Jirafe specific information about PS Employees
        $jirafeUsers = unserialize(base64_decode(Configuration::get('JIRAFE_USERS')));
        
        foreach ($psEmployees as $psEmployee) {
            
            // Get the ID of the employee
            $id = $psEmployee['id_employee'];
            
            // Check to see if username is already set
            $username = PsApi::getUsername($psEmployee['email']);
            
            // Set the information into the user
            $user = array(
                'email' => $username,
                'username' => $username,
                'first_name' => $psEmployee['firstname'],
                'last_name' => $psEmployee['lastname']
            );
            
            // Check to see if there is a token already for this employee - if so, add to the array
            if (!empty($jirafeUsers[$username]['token'])) {
                $user['token'] = $jirafeUsers[$username]['token'];
            }
            
            // Add this user to the list of users to return
            $users[] = $user;
        }
        
        return $users;
    }
    
    public static function setUsers($users)
    {
        $jirafeUsers = array();
        
        foreach ($users as $user) {
            
            // Save Jirafe specific information to the DB
            if (!empty($user['token'])) {
                $jirafeUsers[$user['username']]['token'] = $user['token'];
            }
        }
        
        Configuration::updateValue('JIRAFE_USERS', base64_encode(serialize($jirafeUsers)));
    }
    
    /**
     * Get Jirafe specific information from PS
     *
     * @return array $sites An array of site information as per Jirafe API spec
     */
    public static function getSites()
    {
        // Return an array of sites, even though there is just 1 site in Prestashop
        $sites = array();
        
        // Get the Jirafe specific information about Prestashop sites
        $jirafeSites = unserialize(base64_decode(Configuration::get('JIRAFE_SITES')));
        
        $site = array();
        $site['external_id'] = 1;  // There is only 1 site in prestashop
        $site['description'] = Configuration::get('PS_SHOP_NAME');
        $site['url'] = 'http://' . Configuration::get('PS_SHOP_DOMAIN');
        $site['timezone'] = Configuration::get('PS_TIMEZONE');
        $site['currency'] = PsApi::getCurrency();

        $sites[] = $site;
        
        return $sites;
    }
    
    public static function getSiteId()
    {
        $sites = Configuration::get('JIRAFE_SITES');
        $sites = base64_decode($sites);
        $sites = unserialize($sites);
        $site = $sites[1];
        $siteId = $site['site_id'];
        
        return $siteId;
    }
    
    /**
     * Set Jirafe specific information from a list of sites
     *
     * @param array $sites An array of site information as per Jirafe API spec
     */
    public static function setSites($sites)
    {
        $jirafeSites = array();
        
        foreach ($sites as $site) {
            
            // Save Jirafe specific information to the DB
            if (!empty($site['site_id'])) {
                $jirafeSites[$site['external_id']]['site_id'] = $site['site_id'];
            }
            if (!empty($site['checkout_goal_id'])) {
                $jirafeSites[$site['external_id']]['checkout_goal_id'] = $site['checkout_goal_id'];
            }
        }
        
        Configuration::updateValue('JIRAFE_SITES', base64_encode(serialize($jirafeSites)));
    }
    
    /**
     * @todo There must be a better way to get Employees from PS than go to the DB direct!
     *
     * @return array a list of active PS Employees which will be Jirafe users
     */
    public static function getPsEmployees()
    {
		$dbEmployees = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
    		SELECT `id_employee`, `email`, `firstname`, `lastname`
    		FROM `'._DB_PREFIX_.'employee`
            WHERE `active` = 1
    		ORDER BY `id_employee` ASC');
        
        return $dbEmployees;
    }
    
    /**
     * @todo There must be a better way to get Currencies from PS than go to the DB direct!
     *
     * @return array a list of PS Currencies so we can select the active one
     */
    public static function getPsCurrencies()
    {
		$dbCurrencies = Db::getInstance()->ExecuteS('
    		SELECT *
    		FROM `'._DB_PREFIX_.'currency`
    		WHERE `deleted` = 0
            AND `active` = 1
    		ORDER BY `name` ASC
        ');
        
        return $dbCurrencies;
    }
    
    /**
     * Check to see if something is about to change, so that we can sync
     */
    public static function checkSync($params)
    {
        $sync = false;
        
        // Saving employee information
        if (Tools::isSubmit('submitAddemployee')) {
            // Always sync a new user
            if (empty($_POST['id_employee'])) {
                $sync = true;
            } else {
                // For now always sync when a user is saved.  Modify in the future to only save when something we care about changes
                $sync = true;
            }
        }
        
        // Saving general configuration (enable store, timezone)
        if (Tools::isSubmit('submitgeneralconfiguration')) {
            // This is the list of fields we care about
            $fields = array('PS_SHOP_ENABLE', 'PS_TIMEZONE');
            // Loop through the fields to see if any changed
            foreach ($fields as $field) {
                if ($_POST[$field] != Configuration::get($field)) {
                    $sync = true;
                }
            }
        }
        
        // Saving currencies
        if (Tools::isSubmit('submitOptionscurrency')) {
            if ($_POST['PS_CURRENCY_DEFAULT'] != Configuration::get('PS_CURRENCY_DEFAULT')) {
                $sync = true;
            }
        }
        
        return $sync;
    }
    
    /**
     * Sync Prestashop information with Jirafe information
     */
    public static function sync()
    {
        // Gather information neeeded to run our initial sync
        $app = PsApi::getApplication();
        $users = PsApi::getUsers();
        $sites = PsApi::getSites();
        
        // Sync the information in PS with Jirafe
        $results = JirafeApi::sync($app, $users, $sites);
        
        // Save information back in Prestashop
        PsApi::setUsers($results['users']);
        PsApi::setSites($results['sites']);
    }
    
    /**
     * Gets the default currency for this store
     *
     * @return string The ISO Currency code
     */
    public static function getCurrency()
    {
        // The currency ISO code
        $currencyCode = false;
        
        // The default currency ID
        $currencyId = (int)Configuration::get('PS_CURRENCY_DEFAULT');
        
        // All active currencies in Prestashop
        $psCurrencies = PsApi::getPsCurrencies();
        
        // Loop through till we find the correct currency
        foreach ($psCurrencies as $psCurrency) {
            if ($psCurrency['id_currency'] == $currencyId) {
                $currencyCode = $psCurrency['iso_code'];
                break;
            }
        }
        
        return $currencyCode;
    }
    
    /**
     * Get a unique username based on the email and the Jirafe token for this site.  This is needed because usernames and emails must be unique in Jirafe, and for now, we are not allowing multi-site access in Jirafe.
     *
     * @return string the username generated from the application token and email, so should be unique across all Jirafe sites
     */
    public static function getUsername($email)
    {
        $token = Configuration::get('JIRAFE_TOKEN');
        return substr($token, 0, 6) . '_' . $email;
    }
    
    public static function getVisitorType($siteId)
	{
        // Ask Piwik to get the cookie values
        $tracker = new PiwikTracker($siteId);
        // Retrieve the variable
        $avt = $tracker->getCustomVariable(1);  // hard coded to the first custom variable slot
        $vt = (!empty($avt[1]) && (strpos('ABCDE', $avt[1]) !== false)) ? $avt[1] : '';
        
		$page = isset($_SERVER['PHP_SELF']) ? substr($_SERVER['PHP_SELF'], strlen(__PS_BASE_URI__)) : '';
		switch ($page)
		{
			// Product page tag
			case 'product.php': $vtnew = (empty($vt) ? 'A' : 'C'); break;
			// Order funnel tag
			case 'order.php':
			case 'order-opc.php': $vtnew = 'D'; break;
			// Order confirmation tag
			case 'order-confirmation.php': $vtnew = 'E'; break;
			// Default tag
			default: $vtnew = (empty($vt) ? 'A' : 'B');
		}
        
        // You cannot revert a visit to a lesser level
        if (!empty($vt) && ($vtnew < $vt)) {
            $vtnew = $vt;
        }
        
        return $vtnew;
	}
}

?>