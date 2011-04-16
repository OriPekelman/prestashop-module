<?php

// Set to true if you want to debug the Jirafe API
define ('JIRAFE_DEBUG', false);

// Add this to the include path so that we can use Zend_Http_Client
set_include_path(get_include_path() . PATH_SEPARATOR. dirname(__FILE__));

// Autoload some Zend files to use Zend_Http_Client
require_once 'Zend/Loader.php';
require_once 'Zend/Loader/Autoloader.php';
Zend_Loader_Autoloader::getInstance();

/**
 * Class which communicates with the Jirafe API
 * @todo Hacked to add Zend Http Client - there should be a more elegant way
 */
class JirafeApi
{
    public static function createApplication($app)
    {
        $data = array('name' => $app['name'], 'url' => $app['url']);
        $response = JirafeApi::request(null, 'applications', 'post', $data);
        
        return $response;
    }
    
    public static function sync($app, $users, $sites)
    {
        if (empty($app['token'])) {
            return false;
        }
        
        $data = array('users' => $users, 'sites' => $sites);
        $response = JirafeApi::request($app, "applications/{$app['app_id']}/resources", 'post', $data);
        
        return $response;
    }
    
    /**
     * Make a request to the Jirafe API
     *
     * @param array $app The app information for this instance (token is needed for requests)
     * @param string $method The REST method that is being called (e.g. applications)
     * @param string $requestType The type of REST request (e.g. post, get)
     * @param array $data A list of name / value pairs that will be sent through to the request
     * @return array The JSON parsed response from the Jirafe API
     * @todo Hack that loads the Zend Http Client - should be done in another way
     */
    public static function request($app, $method, $requestType, $data = null)
    {
        // Get the URL that we would like to request
        $url = "https://api.jirafe.com/v1/$method";
        
        $conn = new Zend_Http_Client($url);
        $conn->setConfig(array('timeout' => 30, 'keepalive' => true));
        
        // Format request type for Zend_Http_Client
        switch($requestType) {
            case 'post' : $requestType = Zend_Http_Client::POST; break;
            case 'put' : $requestType = Zend_Http_Client::PUT; break;
            case 'delete' : $requestType = Zend_Http_Client::DELETE; break;
            default : $requestType = Zend_Http_Client::GET;
        }
        
        // Add Parameters to the request
        if (!empty($app['token'])) {
            $conn->setParameterGet('token', $app['token']);
        }
        if (JIRAFE_DEBUG) {
            $conn->setParameterGet('XDEBUG_SESSION_START', 'jirafe');
        }
        if (!empty($data)) {
            foreach ($data as $name => $value) {
                if ($requestType == Zend_Http_Client::POST || $method == Zend_Http_Client::PUT) {
                    $conn->setParameterPost($name, $value);
                } else {
                    $conn->setParameterGet($name, $value);
                }
            }
        }
        
        // Make the request
        try {
            $conn->request($requestType);
            $result = JirafeApi::_parseResponse($conn->getLastResponse());
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
            return false;
        }
        
        return $result;
    }
    
    private static function _parseResponse($response)
    {
        //check server response
        if ($response->isError()) {
            throw new Exception($response->getStatus() .' '. $response->getMessage());
        }
        //TODO: Jirafe API dev mode returns debug toolbar remove it from output here
        $reponseBody = preg_replace('/<!-- START of Symfony2 Web Debug Toolbar -->(.*?)<!-- END of Symfony2 Web Debug Toolbar -->/', '', $response->getBody());
        if(strpos($reponseBody,'You are not allowed to access this file.') !== false) {
            throw new Exception('Server Response: You are not allowed to access this file.');
        }
        if(strpos($reponseBody,'Call Stack:') !== false) {
            throw new Exception('Server Response contains errors');
        }
        if(strpos($reponseBody,'Fatal error:') !== false) {
            throw new Exception('Server Response contains errors');
        }

        //check for returned errors
        $result = json_decode($reponseBody,true);
        if(isset($result['errors']) && !empty($result['errors'])) {
            $errors = array();
            foreach ($result['errors'] as $error) {
                $errors[] = $error;
            }
            throw new Exception(implode(',',$errors));
        }
        
        return $result;
    }
}

?>