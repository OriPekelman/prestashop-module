<?php

/*
 * This file is part of the Jirafe.
 * (c) Jirafe <http://www.jirafe.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Jirafe Platform Ecommerce is an abstract class that implements common methods that ecommerce systems have
 *
 * @author jirafe.com
 */
abstract class Jirafe_Platform_Ecommerce implements Jirafe_Platform_Interface
{
    // Page Types
    const PAGE_HOME     = 1;
    const PAGE_PRODUCT  = 2;
    const PAGE_CATEGORY = 3;
    const PAGE_SEARCH   = 4;
    const PAGE_CART     = 5;
    const PAGE_CONFIRM  = 6;
    const PAGE_OTHER    = 7;

    // Visitor Types
    const VISITOR_ALL       = 'A';
    const VISITOR_BROWSERS  = 'B';
    const VISITOR_ENGAGED   = 'C';
    const VISITOR_READY2BUY = 'D';
    const VISITOR_CUSTOMER  = 'E';

    // URLs for the Jirafe web service
    public $scriptUrl = 'c.jirafe.com';

    public function getTag()
    {
        $aData = array(
            'id'        => $this->getCurrentSiteId(),
        );

        if ($this->trackerUrl != 'data.jirafe.com') {
            $aData['baseUrl'] = $this->trackerUrl;
        }

        switch ($this->getPageType()) {
        case self::PAGE_PRODUCT:
            $aData['product']  = $this->getProduct();
            break;
        case self::PAGE_CATEGORY:
            $aData['category'] = $this->getCategory();
            break;
        case self::PAGE_SEARCH:
            $aData['search'] = $this->getSearch();
            break;
        case self::PAGE_CART:
            $aData['cart'] = array('name' => $this->getCart());
            break;
        case self::PAGE_CONFIRM:
            $aData['confirm'] = array('name' => $this->getOrder());
            break;
        }

        $jirafeJson = json_encode($aData);
        $scriptUrl = $this->scriptUrl;

        return <<<EOF
<!-- Jirafe:START -->
<script type="text/javascript">
var jirafe = {$jirafeJson};
(function(){
    var d=document,g=d.createElement('script'),s=d.getElementsByTagName('script')[0];
    g.type='text/javascript',g.defer=g.async=true;g.src=d.location.protocol+'//{$scriptUrl}/jirafe.js';
    s.parentNode.insertBefore(g, s);
})();
</script>
<!-- Jirafe:END -->

EOF;
    }
}
