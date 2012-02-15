<?php

if (!defined('_PS_VERSION_') || !defined('_CAN_LOAD_FILES_'))
    exit;

// Set to true if you want to debug the Jirafe API in the test sandbox
define ('JIRAFE_DEBUG', true);

//Your class must have the same name than this file.
class Jirafe extends Module
{
    // The Jirafe Client communicates with the Jirafe web service
    private $jirafeClient;
    // The Prestashop Client communicates with the Prestashop ecommerce platform
    private $prestashopClient;

    private static $syncUpdatedObject;

    public function __construct()
    {
        // Require/Autoload the other files
        require_once _PS_MODULE_DIR_ . 'jirafe/vendor/jirafe-php-client/src/Jirafe/Autoloader.php';
        Jirafe_Autoloader::register();

        $this->name = 'jirafe';
        $this->tab = 'analytics_stats';
        $this->version = '1.2';

        //The constructor must be called after the name has been set, but before you try to use any functions like $this->l()
        parent::__construct();

        $this->page = basename(__FILE__, '.php');

        $this->author = $this->l('Jirafe Inc.');
        $this->displayName = $this->l('Jirafe Analytics');
        $this->description = $this->l('The best analytics for ecommerce merchants.  Deeply integrated in the Prestashop platform.');

        // Confirmation of uninstall
        $this->confirmUninstall = $this->l('Are you sure you want to remove Jirafe analytics integration for your site?');
    }

    public function install()
    {
        $ps = $this->getPrestashopClient();
        $jf = $this->getjirafeClient();

        // Get the application information needed by Jirafe
        $app = $ps->getApplication();

        // Check if there is a token (probably not since we are installing) and if not, get one from Jirafe
        if (empty($app['token'])) {
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
            && $this->registerHook('actionObjectAddAfter')  // Check to see if we should sync
            && $this->registerHook('actionObjectUpdateBefore')  // Check to see if we should sync
            && $this->registerHook('actionObjectUpdateAfter')  // Check to see if we should sync
            && $this->registerHook('actionObjectDeleteAfter')  // Check to see if we should sync
            && $this->registerHook('backOfficeHeader')  // Add dashboard script
            && $this->registerHook('header')         // Install Jirafe tags
            && $this->registerHook('cart')           // When adding items to the cart
            && $this->registerHook('orderConfirmation')    // Goal tracking
            && $ps->set('logo', 'http://jirafe.com/bundles/jirafewebsite/images/logo.png')
            && self::installAdminDashboard()
        );
    }

    private function installAdminDashboard()
    {
        @copy(_PS_MODULE_DIR_.$this->name.'/logo.gif', _PS_IMG_DIR_.'t/'.$tabClass.'.gif');
        $tab = new Tab();
        $tab->name = array(1=>'Jirafe Analytics', 2=>'Mon onglet tutoriel');
        $tab->class_name = 'AdminJirafeDashboard';
        $tab->module = 'jirafe';
        $tab->id_parent = 0;
        return $tab->add();
    }

    public function uninstall()
    {
        $ps = $this->getPrestashopClient();

        // Remove values in the DB
        return (
            parent::uninstall()
            && $ps->delete('app_id')
            && $ps->delete('sites')
            && $ps->delete('users')
            && $ps->delete('sync')
            && $ps->delete('token')
            && $ps->delete('logo')
            && $this->unregisterHook('backOfficeTop')  // Check to see if we should sync
            && $this->unregisterHook('actionObjectAddAfter')  // Check to see if we should sync
            && $this->unregisterHook('actionObjectUpdateBefore')  // Check to see if we should sync
            && $this->unregisterHook('actionObjectUpdateAfter')  // Check to see if we should sync
            && $this->unregisterHook('actionObjectDeleteAfter')  // Check to see if we should sync
            && $this->unregisterHook('backOfficeHeader')  // Add dashboard script
            && $this->unregisterHook('header')         // Install Jirafe tags
            && $this->unregisterHook('cart')           // When adding items to the cart
            && $this->unregisterHook('orderConfirmation')    // Goal tracking
            && $this->uninstallAdminDashboard()
        );
    }

    private function uninstallAdminDashboard()
    {
        $tab = new Tab(Tab::getIdFromClassName('AdminJirafeDashboard'));

        return (
            parent::uninstall()
            && $tab->delete()
        );
    }

    //Display Configuration page of your module.
    public function getContent()
    {
        global $currentIndex;

        $ps = $this->getPrestashopClient();

        $html = '<h2>'.$this->displayName.'</h2>';

        if (Tools::isSubmit('submitJirafe')) {
            $ps->set('site_id', (int)Tools::getValue('JIRAFE_SITE_ID'));
            $html .= $this->displayConfirmation($this->l('Configuration updated'));
        }

        $conflink = $currentIndex.'&configure='.$this->name.'&token='.Tools::getValue('token');
        $html .= '
            <fieldset><legend>'.$this->l('Configuration').'</legend>
                <form action="'.htmlentities($conflink).'" method="post">
                    <label>'.$this->l('Site ID').'</label>
                    <div class="margin-form">
                        <input type="text" name="JIRAFE_SITE_ID" value="'.Tools::getValue('JIRAFE_SITE_ID', $ps->get('site_id')).'" />
                    </div>
                    <div class="clear">&nbsp;</div>
                    <input type="submit" name="submitJirafe" value="'.$this->l('   Save   ').'" class="button" />
                </form>
            </fieldset>
            <div class="clear">&nbsp;</div>';
        return $html;
    }

    public function getPrestashopClient()
    {
        if (null === $this->prestashopClient) {
            // Prestashop Ecommerce Client
            $this->prestashopClient = new Jirafe_Platform_Prestashop();

            if (JIRAFE_DEBUG) {
                $this->prestashopClient->trackerUrl = 'test-data.jirafe.com';
            }
        }

        return $this->prestashopClient;
    }

    public function getJirafeClient()
    {
        if (null === $this->jirafeClient) {
            $ps = $this->getPrestashopClient();
            // Create Jirafe Client
            if (JIRAFE_DEBUG) {
                $connection = new Jirafe_HttpConnection_Curl('https://test-api.jirafe.com/v1',443);
                $this->jirafeClient = new Jirafe_Client($ps->get('token'), $connection);
            } else {
                $this->jirafeClient = new Jirafe_Client($ps->get('token'));
            }
        }

        return $this->jirafeClient;
    }

    /**
    * Check to see if someone saved something we need to update Jirafe about
    *
    * @param array $params Information from the user, like cookie, etc
    */
    public function hookBackOfficeTop($params)
    {
        if ($this->getPrestashopClient()->isDataChanged($params)) {
            $this->_sync();
        }
    }

    /**
     * Check to see if someone created something we need to update Jirafe about
     */
    public function hookActionObjectAddAfter($params)
    {
        $object = $params['object'];

        if ($object instanceof Employee || $object instanceof Shop) {
            $this->_sync();
        }
    }

    public function hookActionObjectUpdateBefore($params)
    {
        // do not sync all updated object by default,
        // only if certain fields are updated
        self::$syncUpdatedObject = false;

        $object = $params['object'];

        // sync only if following fields are changed
        if ($object instanceof Employee) {
            $employee = new Employee();
            $oldObject = $employee->getByEmail($object->email);
            if ($oldObject) {
                if ($object->lastname != $oldObject->lastname ||
                $object->firstname != $oldObject->firstname ||
                $object->email != $oldObject->email ||
                $object->active != $oldObject->active) {
                    self::$syncUpdatedObject = true;
                }
            } else {
                // sync if email change
                self::$syncUpdatedObject = true;
            }
        }
        elseif ($object instanceof Shop) {
            $oldShop = Shop::getShop($object->id);
            if ($object->name != $oldShop['name'] || $object->active != $oldShop['active']) {
                self::$syncUpdatedObject = true;
            }
        }
    }

    /**
     * Check to see if someone updated something we need to update Jirafe about
     */
    public function hookActionObjectUpdateAfter($params)
    {
        // wtf? this hook is executed for each request of jirafe module page

        $object = $params['object'];

        if (($object instanceof Employee || $object instanceof Shop) && self::$syncUpdatedObject) {
            $this->_sync();
        }
    }

    /**
     * Check to see if someone deleted something we need to update Jirafe about
     */
    public function hookActionObjectDeleteAfter($params)
    {
        $object = $params['object'];

        if ($object instanceof Employee || $object instanceof Shop) {
            $this->_sync();
        }
    }

    public function hookBackOfficeHeader($params)
    {
        return '
            <link type="text/css" rel="stylesheet" href="https://jirafe.com/dashboard/css/prestashop_ui.css" media="all" />
            <script type="text/javascript" src="https://jirafe.com/dashboard/js/prestashop_ui.js"></script>';
    }

    /**
     * Hook which allows us to insert our analytics tag into the Front end
     *
     * @param array $params variables from the front end
     * @return string the additional HTML that we are generating in the header
     */
    public function hookHeader($params)
    {
        $ps = $this->getPrestashopClient();
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
        $ps = $this->getPrestashopClient();

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
        $ps = $this->getPrestashopClient();

        // First get the details of the order to log to the server
        $order = $ps->getOrder($params);

        // Then get the details of the visitor
        //$visitor = $ps->getVisitor($params);

        // Log the order for this visitor
        $ps->logOrder($order);
    }

    private function _sync()
    {
        $ps = $this->getPrestashopClient();
        $jf = $this->getJirafeClient();

        // Sync the changes
        $app = $ps->getApplication();
        $results = $jf->applications($ps->get('app_id'))->resources()->sync($ps->getSites(), $ps->getUsers());

        // Save information back in Prestashop
        $ps->setUsers($results['users']);
        $ps->setSites($results['sites']);
    }
}
