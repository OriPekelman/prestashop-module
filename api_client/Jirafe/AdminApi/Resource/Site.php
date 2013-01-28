<?php

/*
 * This file is part of the Jirafe.
 * (c) Jirafe <http://www.jirafe.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Jirafe API site object.
 *
 * @author knplabs.com
 */
class Jirafe_AdminApi_Resource_Site extends Jirafe_AdminApi_Object
{
    /**
     * Initializes site object.
     *
     * @param   integer                     $id         site ID
     * @param   Jirafe_AdminApi_Collection_Sites $collection sites collection
     * @param   Jirafe_AdminApi_Client               $client     API client
     */
    public function __construct($id, Jirafe_AdminApi_Collection_Sites $collection, Jirafe_AdminApi_Client $client)
    {
        parent::__construct($id, $collection, $client);
    }
}
