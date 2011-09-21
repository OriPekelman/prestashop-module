<?php

if (!defined('_CAN_LOAD_FILES_'))
	exit;

// Set to true if you want to debug the Jirafe API in the test sandbox
define ('JIRAFE_DEBUG', true);

/*
    REQUIREMENTS
    
    Use E_STRICT as error reporting level.
    In php.ini:
        - error_reporting = E_ALL | E_STRICT
        - display_errors = On
        
    The name of the module folder and mail PHP file must be in lower case (Windows may not be case sensitive, but Unix based OS are).
    Use the following folder architecture
    - module folder
        -> module file (same name than the module folder with .php at the end)
        -> images folder
        -> css folder
        -> logo.gif (16x16, will appear in the module list of the back office)
    Do not forget to translate your module.
    Do not forget to remove hidden files (like .svn, .DS_store, .Thumbs) or temporary files (#file#, file~)

    If you want to use Ajax/Javascript, use firebug (a plugin for Firefox) to debug you script and check the compatibility with all browser.
*/
    
//Your class must have the same name than this file.
class Jirafe extends Module
{
    // The Jirafe Client communicates with the Jirafe web service
    private $jirafeClient;
    // The Prestashop Client communicates with the Prestashop ecommerce platform
    private $prestashopClient;
    
    public function __construct()
    {
        // Require/Autoload the other files
        require_once _PS_MODULE_DIR_ . 'jirafe/vendor/jirafe-php-client/src/Jirafe/Autoloader.php';
        require_once _PS_MODULE_DIR_ . 'jirafe/JirafeDashboardTab.php';
//        require_once _PS_MODULE_DIR_ . 'jirafe/PiwikTracker.php';
        
        Jirafe_Autoloader::register();
        spl_autoload_register('__autoload');  // Re-register the default autoloader used by Prestashop (or ours will be the only autoloader)
        
        //Name of your module. It must have the same name than the class
        $this->name = 'jirafe';
        
        //You must choose an existing tab amongst the ones that are available
        $this->tab = 'analytics_stats';
        $this->tabClassName = 'JirafeDashboardTab';
        $this->tabParentName = ''; //in this example you add subtab under Tools tab. You may also declare new tab here
        
        //The version of your module. Do not forget to increment the version for each modification
        $this->version = '1.1';
        
        //The constructor must be called after the name has been set, but before you try to use any functions like $this->l()
        parent::__construct();
        
        //Name displayed in the module list
        $this->displayName = $this->l('Jirafe Analytics');
        
        //Short description displayed in the module list
        $this->description = $this->l('Integrated analytics for ecommerce managers');
        
        // Confirmation of uninstall
        $this->confirmUninstall = $this->l('Are you sure you want to remove Jirafe analytics integration for your site?');
        
        // Prestashop Ecommerce Client
        $ps = new Jirafe_Platform_Prestashop();
        $this->prestashopClient = $ps;
        
        // Jirafe Client
        if (JIRAFE_DEBUG) {
            $ps->trackerUrl = 'test-data.jirafe.com';
            $connection = new Jirafe_HttpConnection_Curl('https://test-api.jirafe.com/v1',443);
            $this->jirafeClient = new Jirafe_Client($ps->get('token'), $connection);
        } else {
            $this->jirafeClient = new Jirafe_Client($ps->get('token'));
        }
    }
    
    //You must implement the following methods if your module need to create a table, add configuration variables, or hook itself somewhere.
    //-------------------------------
    public function install()
    {
        if (!$id_tab) {
            $tab = new Tab();
            $tab->class_name = $this->tabClassName;
            $tab->id_parent = Tab::getIdFromClassName($this->tabParentName);
            $tab->module = $this->name;
            $languages = Language::getLanguages();
            foreach ($languages as $language)
                $tab->name[$language['id_lang']] = $this->displayName;
            $tab->add();
        }

        $ps = $this->prestashopClient;
        $jf = $this->jirafeClient;
        
        // Get the application information needed by Jirafe
        $app = $ps->getApplication();

        // Check if there is a token (probably not since we are installing) and if not, get one from Jirafe
        if (null === $app['token']) {
            $app = $jf->applications()->create($app['name'], $app['url']);
            
            // Set the application information in Prestashop
            $ps->setApplication($app);
            // Set the token in the Jirafe client for later
            $jf->setToken($app['token']);
        }
        
        // Sync for the first time
        $results = $jf->applications($app['app_id'])->resources()->sync($ps->getSites(), $ps->getUsers());
            
        // Save information back in Prestashop
        $ps->setUsers($results['users']);
        $ps->setSites($results['sites']);
            
        // Add hooks for stats and tags
        return (
            parent::install()  // Get Jirafe ID, perform initial sync
            && $this->registerHook('backOfficeTop')  // Check to see if we should sync
            && $this->registerHook('header')         // Install Jirafe tags
            && $this->registerHook('cart')           // When adding items to the cart
//            && $this->registerHook('productfooter')  // Product specific information
//            && $this->registerHook('home')  // Do we need this?
//            && $this->registerHook('shoppingCartExtra')  // Shopping cart information
            && $this->registerHook('orderConfirmation')    // Goal tracking
        );
    }
    
    public function viewAccess($disable = false) {
            $result = true;
            return $result;
    }

    public function uninstall()
    {
        $id_tab = Tab::getIdFromClassName($this->tabClassName);
        if ($id_tab) {
            $tab = new Tab($id_tab);
            $tab->delete();
        }
        
        $ps = $this->prestashopClient;
        
        if (!$ps->delete('app_id')
            || !$ps->delete('sites')
            || !$ps->delete('users')
            || !$ps->delete('sync')
            || !$ps->delete('token')
            || !parent::uninstall()) {
            return false;
        }
        return true;
    }
    
    //-------------------------------
    
    //Display Configuration page of your module.
    public function getContent()
    {
        global $currentIndex;
        
        $html = '<h2>'.$this->displayName.'</h2>';

        if (Tools::isSubmit('submitJirafe')) {
                Configuration::updateValue('JIRAFE_SITE_ID', (int)Tools::getValue('JIRAFE_SITE_ID'));
                $html .= $this->displayConfirmation($this->l('Configuration updated'));
        }
		
        $conflink = $currentIndex.'&configure='.$this->name.'&token='.Tools::getValue('token');
        $html .= '
		<fieldset><legend>'.$this->l('Configuration').'</legend>
			<form action="'.htmlentities($conflink).'" method="post">
				<label>'.$this->l('Site ID').'</label>
				<div class="margin-form">
					<input type="text" name="JIRAFE_SITE_ID" value="'.Tools::getValue('JIRAFE_SITE_ID', Configuration::get('JIRAFE_SITE_ID')).'" />
				</div>
				<div class="clear">&nbsp;</div>
				<input type="submit" name="submitJirafe" value="'.$this->l('   Save   ').'" class="button" />
        	</form>
		</fieldset>
		<div class="clear">&nbsp;</div>';
        return $html;
    }

    /**
     * Check to see if someone saved something we need to update Jirafe about
     *
     * @param array $params Information from the user, like cookie, etc
     * @todo Iterate through the exact employee fields that trigger a sync.  For now, every save triggers a sync.
     */
    public function hookBackOfficeTop($params)
    {
        $ps = $this->prestashopClient;
        
        // Back Office Top hook is called twice - once before saving, and once after.  So, when we initially come here, we have not saved yet.
        //  We just set a flag.  The second time we come here, we have already saved, and so we check the flag and sync.
        
        if ($ps->get('sync')) {
            $ps->set('sync', false);
            
            // Sync the changes
            $app = $ps->getApplication();
            $results = $this->jirafeClient->applications($ps->get('app_id'))->resources()->sync($ps->getSites(), $ps->getUsers());
            
            // Save information back in Prestashop
            $ps->setUsers($results['users']);
            $ps->setSites($results['sites']);
        }
        if ($ps->isDataChanged($params)) {
            $ps->set('sync', true);
        }
    }
    
    /**
     * Hook which allows us to insert our analytics tag into the Front end
     *
     * @param array $params variables from the front end
     * @return string the additional HTML that we are generating in the header
     */
    public function hookHeader($params)
    {
        $ps = $this->prestashopClient;
        return $ps->getTag();
    }
    
    /**
     * Hook which gets called when a user adds something to their cart.
     * We then send Jirafe the updated cart information
     *
     * @param array $params variables from the front end
     */
    public function hookCart($params)
    {
        // Get the ecommerce client
        $ps = $this->prestashopClient;
        
        // First get the details of the cart to log to the server
        $cart = $ps->getCart($params);

        // Then get the details of the visitor
        //$visitor = $ps->getVisitor($params);
        
        // Log the cart update for this visitor
        $ps->logCartUpdate($cart);
    }
    
    /**
     * Hook which gets called when a user makes a new order
     * We then send Jirafe the order information
     *
     * @param array $params variables from the front end
     */
    public function hookOrderConfirmation($params)
    {
        // Get the ecommerce client
        $ps = $this->prestashopClient;
        
        // First get the details of the order to log to the server
        $order = $ps->getOrder($params);

        // Then get the details of the visitor
        //$visitor = $ps->getVisitor($params);
        
        // Log the order for this visitor
        $ps->logOrder($order);
    }
}

?>