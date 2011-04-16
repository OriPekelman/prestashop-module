<?php

class PsApi
{
    public function getApplication()
    {
        $data = array(
            'name' => Configuration::get('PS_SHOP_NAME'),
            'url' => 'http://' . Configuration::get('PS_SHOP_DOMAIN')
        );
            
        if (!empty(Configuration::get('JIRAFE_TOKEN'))) {
            $data['token'] = Configuration::get('JIRAFE_TOKEN');
        }
        
        if (!empty(Configuration::get('JIRAFE_ID'))) {
            $data['app_id'] = Configuration::get('JIRAFE_ID');
        }
        
        return $response;
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
    
    public function getUsers($app)
    {
        $users = array();
        
        // Get the Prestashop Employees
        $psEmployees = PsApi::getPsEmployees();

        // Get the Jirafe specific information about PS Employees
        $jirafeUsers = Configuration::get('JIRAFE_USERS');
        
        foreach ($psEmployees as $psEmployee) {
            // Both email and username will be set to $username, so that there is not duplicates in Jirafe
            $username = JirafeApi::generateUsername($app, $psEmployee['email']);
            
            // Get the ID of the employee
            $id = $psEmployee['id_employee'];
            
            // Check to see if username is already set
            if (!empty($jirafeUsers[$id]['username'])) {
                $username = $jirafeUsers[$id]['username']
            } else {
                $username = PsApi::generateUsername($psEmployee['email']);
            }
            
            // Set the information into the user
            $user = array(
                'email' => $username,
                'username' => $username,
                'external_id' => $id
            );
            
            // Check to see if there is a token already for this employee - if so, add to the array
            if (!empty($jirafeUsers[$id]['token'])) {
                $user['token'] = $jirafeUsers[$id]['token'];
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
                $jirafeUsers[$user['external_id']]['token'] = $user['token'];
            }
            if (!empty($user['username'])) {
                $jirafeUsers[$user['external_id']]['username'] = $user['username'];
            }
        }
        
        Configuration::updateValue('JIRAFE_USERS', $jirafeUsers);
    }
    
    /**
     * Get Jirafe specific information from PS
     *
     * @return array $sites An array of site information as per Jirafe API spec
     */
    public function getSites()
    {
        // Return an array of sites, even though there is just 1 site in Prestashop
        $sites = array();
        
        // Get the Jirafe specific information about Prestashop sites
        $jirafeSites = Configuration::get('JIRAFE_SITES');
        
        // Get the Prestashop Currencies
        $psCurrencies = PsApi::getPsCurrencies();
        
        $site = array();
        $site['external_id'] = 1;  // There is only 1 site in prestashop
        $site['description'] = Configuration::get('PS_SHOP_NAME');
        $site['url'] = 'http://' . Configuration::get('PS_SHOP_DOMAIN');
        $site['timezone'] = Configuration::get('PS_TIMEZONE');
        $site['currency'] = $psCurrencies[(int)Configuration::get('PS_CURRENCY_DEFAULT')]['iso_code'];

        $sites[] = $site;
        
        return $sites;
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
        
        Configuration::updateValue('JIRAFE_SITES', $jirafeSites);
    }
    
    /**
     * @todo There must be a better way to get Employees from PS than go to the DB direct!
     *
     * @return array a list of active PS Employees which will be Jirafe users
     */
    public function getPsEmployees()
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
    public function getPsCurrencies()
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
     * Generate a username based on the email.  This is needed because usernames and emails must be unique in Jirafe, and for now, we are not allowing multi-site access in Jirafe.
     *
     * @return string the username generated from the application token and email, so should be unique across all Jirafe sites
     */
    public function generateUsername($email)
    {
        $token = Configuration::get('JIRAFE_TOKEN');
        return substr($app['token'], 0, 6) . '_' . $email;
    }
    
    private function getPiwikVisitorType()
	{
		// Todo
		$phpSelf = isset($_SERVER['PHP_SELF']) ? substr($_SERVER['PHP_SELF'], strlen(__PS_BASE_URI__)) : '';
		switch ($phpSelf)
		{
			// Homepage tag
			case 'index.php':
				return 42;
			// Product page tag
			case 'product.php':
				return 42;
			// Order funnel tag
			case 'order.php':
			case 'order-opc.php':
				return 42;
			// Order confirmation tag
			case 'order-confirmation.php':
				return 42;
			// Default tag
			default:
				return 42;
		}
	}
}

?>