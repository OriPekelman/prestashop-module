<?php

class Jirafe_Base extends Module
{
    // The Jirafe Client communicates with the Jirafe web service
    private $jirafeClient;

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
