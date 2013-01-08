<?php

//Your class must have the same name than this file.
class Jirafe extends Jirafe_Base
{
    // The Prestashop Client communicates with the Prestashop ecommerce platform
    private $prestashopClient;

    public function getPrestashopClient()
    {
        if (null === $this->prestashopClient) {
            // Prestashop Ecommerce Client
            $this->prestashopClient = new Jirafe_Platform_Prestashop14();

            $this->prestashopClient->trackerUrl = JIRAFE_TRACKER_URL;
        }

        return $this->prestashopClient;
    }

    public function install()
    {
        $ps = $this->getPrestashopClient();
        $jf = $this->getJirafeAdminClient();

        // Get the application information needed by Jirafe
        $app = $ps->getApplication();

        // Check if there is a token (probably not since we are installing) and if not, get one from Jirafe
        if (empty($app['token'])) {
            try {
                $app = $jf->applications()->create($app['name'], $app['url'], 'prestashop', _PS_VERSION_, JIRAFE_MODULE_VERSION);
            } catch (Exception $e) {
                // TODO: display error msg
                /* $this->_errors[] = $this->l('The Jirafe Web Service is unreachable. Please try again when the connection is restored.'); */
                return false;
            }

            // Set the application information in Prestashop
            $ps->setApplication($app);
            // Set the token in the Jirafe client for later
            $jf->setToken($app['token']);
        }

        // Sync for the first time
        try {
            $results = $jf->applications($app['app_id'])->resources()->sync($ps->getSites(), $ps->getUsers(), array(
                'platform_type' => 'prestashop',
                'platform_version' => _PS_VERSION_,
                'plugin_version' => JIRAFE_MODULE_VERSION,
                'opt_in' => false // @TODO, enable onboarding when ready
            ));
        } catch (Exception $e) {
            /* $this->_errors[] = $this->l('The Jirafe Web Service is unreachable. Please try again when the connection is restored.'); */
            return false;
        }

        // Save information back in Prestashop
        $ps->setUsers($results['users']);
        $ps->setSites($results['sites']);

        // Add hooks for stats and tags
        return (
            parent::install()  // Get Jirafe ID, perform initial sync
            && $this->registerHook('backOfficeTop')  // Check to see if we should sync
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
        $tab = new Tab();
        $tab->name = array(1 => 'Jirafe Analytics', 2 => 'Jirafe Analytics');
        $tab->class_name = 'AdminJirafeDashboard';
        $tab->module = 'jirafe';
        $tab->id_parent = Tab::getIdFromClassName('AdminStats');
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

    /**
    * Check to see if someone saved something we need to update Jirafe about
    *
    * @param array $params Information from the user, like cookie, etc
    */
    public function hookBackOfficeTop($params)
    {
       $ps = $this->getPrestashopClient();
       $jf = $this->getJirafeAdminClient();

        // Back Office Top hook is called twice - once before saving, and once after. So, when we initially come here, we have not saved yet.
        // We just set a flag. The second time we come here, we have already saved, and so we check the flag and sync.

        if ($ps->get('sync')) {
            $ps->set('sync', false);

            // Sync the changes
            $app = $ps->getApplication();
            try {
                $results = $jf->applications($ps->get('app_id'))->resources()->sync($ps->getSites(), $ps->getUsers(), array(
                    'platform_type' => 'prestashop',
                    'platform_version' => _PS_VERSION_,
                    'plugin_version' => JIRAFE_MODULE_VERSION,
                    'opt_in' => false // @TODO, enable onboarding when ready
                ));
            } catch (Exception $e) {
                // TODO find a way to display error messages
                /* $this->_errors[] = $this->displayError($this->l('The Jirafe Web Service is unreachable. Please try again when the connection is restored.')); */
                return false;
            }

            // Save information back in Prestashop
            $ps->setUsers($results['users']);
            $ps->setSites($results['sites']);
        }

        if ($ps->isDataChanged($params)) {
            $ps->set('sync', true);
        }
    }

    public function hookBackOfficeHeader($params)
    {
        $prefix = JIRAFE_ASSETS_URL_PREFIX;

        return <<<EOT
            <link type="text/css" rel="stylesheet" href="{$prefix}/css/prestashop_ui.css" media="all" />
            <style type="text/css">.ui-daterangepicker .ui-widget-content { display:block; }</style>
            <script type="text/javascript" src="{$prefix}/js/prestashop_ui.js"></script>
EOT;
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

}
