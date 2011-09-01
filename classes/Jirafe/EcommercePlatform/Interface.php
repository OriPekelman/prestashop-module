<?php
interface Jirafe_EcommercePlatform_Interface
{
    /**
     * Get the value of a variable
     * @param name the name of the variable
     * @return the value of the variable
     */
    public function get($name);
    
    /**
     * Set a variable to a value
     * @param name the name of the variable
     * @param value the value in which to set the variable
     */
    public function set($name, $value);
    
    /**
     * Remove a previously saved variable
     * @param name the name of the variable to delete
     * @return whether the action was successful
     */
    public function delete($name);
    
    public function getApplication();
    
    public function setApplication($app);
    
    /**
     * Get the Jirafe users, which are the PS employees with their Jirafe tokens
     *
     * @return array A list of Jirafe users
     */
    public function getUsers();
    
    public function setUsers($users);
    
    /**
     * Get Jirafe specific information from PS
     *
     * @return array $sites An array of site information as per Jirafe API spec
     */
    public function getSites();
    
    public function getSiteId();
    
    /**
     * Set Jirafe specific information from a list of sites
     *
     * @param array $sites An array of site information as per Jirafe API spec
     */
    public function setSites($sites);
    
    /**
     * Check to see if something is about to change, so that we can sync
     */
    public function isDataChanged($params);

    /**
     * Gets the default currency for this store
     *
     * @return string The ISO Currency code
     */
    public function getCurrency();
    
    public function getTags($params);
}

?>