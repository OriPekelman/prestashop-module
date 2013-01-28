<?php

/*
 * This file is part of the Jirafe.
 * (c) Jirafe <http://www.jirafe.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Jirafe API orders report.
 *
 * @author Fooman Ltd
 */
class Jirafe_AdminApi_Collection_Orders extends Jirafe_AdminApi_Collection
{
    /**
     * Initializes orders collection.
     *
     * @param   Jirafe_AdminApi_Resource_Site        $parent site resource
     * @param   Jirafe_AdminApi_Client                   $client API client
     */
    public function __construct(Jirafe_AdminApi_Resource_Site $parent, Jirafe_AdminApi_Client $client)
    {
        parent::__construct($parent, $client);
    }

    public function status()
    {
        return new Jirafe_AdminApi_Resource_Status($this, $this->getClient());
    }
}
