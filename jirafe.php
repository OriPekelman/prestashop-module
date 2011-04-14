<?php

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
		return (parent::install()
            && $this->registerHook('header')
            && $this->registerHook('backOfficeHome');
	}
	
	public function uninstall()
	{
		return parent::uninstall();
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
	
    public function hookBackOfficeHome($params)
    {
        
    }
	public function hookHeader($params)
	{
		// <script type="text/javascript" src="'.__PS_BASE_URI__.'modules/'.$this->name.'/piwik.js"></script>
		$html = '
		<script type="text/javascript">
			var _paq = _paq || [];
			(function(){
				_paq.push([\'setSiteId\', '.(int)Configuration::get('JIRAFE_SITE_ID').']);
				_paq.push([\'setTrackerUrl\', \''.__PS_BASE_URI__.'modules/'.$this->name.'/piwik.php\']);
				_paq.push([\'enableLinkTracking\']);
				_paq.push([\'setCustomVariable\',\'1\',\'U\',\''.addslashes($this->getPiwikVisitorType()).'\']);
				_paq.push([\'trackPageView\']);
				
				var d=document,
					g=d.createElement(\'script\'),
					s=d.getElementsByTagName(\'script\')[0];
					g.type=\'text/javascript\';
					g.defer=true;
					g.async=true;
					g.src=\''.__PS_BASE_URI__.'modules/'.$this->name.'/piwik.js\';
					s.parentNode.insertBefore(g,s);
			})();
		</script>';
		return $html;
	}
}

?>