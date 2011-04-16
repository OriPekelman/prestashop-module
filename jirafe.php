<?php

// Set to true if you want to debug the Jirafe API
define ('JIRAFE_DEBUG', false);

// Add this to the include path so that we can use Zend_Http_Client
set_include_path(get_include_path() . PATH_SEPARATOR. dirname(__FILE__));

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
	public function __construct()
	{
		
		//Name of your module. It must have the same name than the class
		$this->name = 'jirafe';
		
		//You must choose an existing tab amongst the ones that are available
		$this->tab = 'analytics_stats';
		
		//The version of your module. Do not forget to increment the version for each modification
		$this->version = '1.0';

		//The constructor must be called after the name has been set, but before you try to use any functions like $this->l()
		parent::__construct();

		//Name displayed in the module list
		$this->displayName = $this->l('Jirafe');
		
		//Short description displayed in the module list
		$this->description = $this->l('Integrated analytics for ecommerce managers');
	}
    
	//You must implement the following methods if your module need to create a table, add configuration variables, or hook itself somewhere.
	//-------------------------------
	public function install()
	{
        // Get the application information needed by Jirafe
        $app = PsApi::getApplication();

        // Check if there is a token (probably not since we are installing) and if not, get one from Jirafe
        if (empty($app['token'])) {

            // Create the application in Jirafe
            $app = JirafeApi::createApplication($app);
            
            // Set the application information in Prestashop
            PsApi::setApplication($app);
        }
        
        // Gather information neeeded to run our initial sync
        $users = PsApi::getUsers();
        $sites = PsApi::getSites();
        
        // Sync the information in PS with Jirafe
        $results = JirafeApi::sync($app, $users, $sites);
        
        // Save information back in Prestashop
        PsApi::setUsers($results['users']);
        PsApi::setSites($results['sites']);
        
        // Add hooks for stats and tags
		return (
            parent::install()  // Get Jirafe ID, perform initial sync
//            && $this->registerHook('backOfficeHeader')  // Check to see if we should sync
            && $this->registerHook('header')  // Install Jirafe tags
//            && $this->registerHook('productfooter')  // Product specific information
//            && $this->registerHook('home')  // Do we need this?
//            && $this->registerHook('shoppingCartExtra')  // Shopping cart information
//            && $this->registerHook('orderConfirmation'));  // Goal tracking
        );
	}
    
	public function uninstall()
	{
		if (!Configuration::deleteByName('JIRAFE_TOKEN')
            || !Configuration::deleteByName('JIRAFE_ID')
            || !Configuration::deleteByName('JIRAFE_SITES')
            || !Configuration::deleteByName('JIRAFE_USERS')
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

	public function hookHeader($params)
	{
        // Set some variables needed to generate the ad tag
        $siteId = (int)Configuration::get('JIRAFE_SITES')[1]['site_id'];
        $visitorType = PsApi::getVisitorType();
        
        // Get the HTML
        $html = '
    <script type="text/javascript">
    var _paq = _paq || [];
    (function(){
        var u=(("https:" == document.location.protocol) ? "https://data.jirafe.com/" : "http://data.jirafe.com/");
        _paq.push([\'setSiteId\', '.$siteId.']);
        _paq.push([\'setTrackerUrl\', u+\'piwik.php\']);
        _paq.push([\'enableLinkTracking\']);
        _paq.push([\'setCustomVariable\',\'1\',\'U\',\''.$visitorType.'\']);
        _paq.push([\'trackPageView\']);
        
        var d=document,
            g=d.createElement(\'script\'),
            s=d.getElementsByTagName(\'script\')[0];
            g.type=\'text/javascript\';
            g.defer=true;
            g.async=true;
            g.src=u+\'piwik.js\';
            s.parentNode.insertBefore(g,s);
    })();
    </script>';
    
        return $html;
    }
}

?>