<?php

/*
 * This file is part of the Jirafe.
 * (c) Jirafe <http://www.jirafe.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Jirafe Tracker client
 *
 * @author jirafe.com
 */
class Jirafe_TrackerApi_Client
{
    // Visitor Types
    const VISITOR_ALL       = 'A';
    const VISITOR_BROWSERS  = 'B';
    const VISITOR_ENGAGED   = 'C';
    const VISITOR_READY2BUY = 'D';
    const VISITOR_CUSTOMER  = 'E';

    private $baseUrl;
    private $appToken;

    public function __construct($baseUrl, $appToken)
    {
        $this->baseUrl = $baseUrl;
        $this->appToken = $appToken;
    }

    /**
     * Saves given cart state.
     *
     * @param int siteId
     * @param int visitorId
     * @param array cart
     * Cart should have following format:
     * array(
     *    'visitorId' => 143,
     *    'siteId' => 5331,
     *    'totalPrice' => 4212.39,
     *    'userName' => 'Abitbol', // optional
     *    'userEmail' => 'abitbol@example.com', // optional
     *    'entries' => array(
     *        'productCode' => 'sk4123',
     *        'productName' => 'myProduct',
     *        'categoryName' => 'myCategory',
     *        'unitPrice' => 12.4,
     *        'quantity' => 1
     *     )
     * );
     */
    public function updateCart(array $cart)
    {
        $tracker = $this->getTracker($cart['siteId']);

        $tracker->setVisitorId($cart['visitorId']);
        $tracker->setCustomVariable(1, 'U', self::VISITOR_READY2BUY);

        if (!empty($cart['userName'])) {
            $tracker->setCustomVariable(4, 'firstName', $cart['userName']);
        }
        if (!empty($cart['userEmail'])) {
            $tracker->setCustomVariable(3, 'email', $cart['userEmail']);
        }

        foreach ($cart['entries'] as $entry) {
            $tracker->addEcommerceItem(
                $entry['productCode'],
                $entry['productName'],
                $entry['categoryName'],
                $entry['unitPrice'],
                $entry['quantity']
            );
        }

        try {
            $response = $tracker->doTrackEcommerceCartUpdate($cart['totalPrice']);
        } catch (Exception $e) {
            throw new Jirafe_Exception("Failed to update cart: {$e->getMessage()}");
        }
    }

    /**
     * Send order creation
     *
     * @param int siteId
     * @param int visitorId
     * @param array order
     * Order should have following format:
     * array(
     *    'visitorId' => 143,
     *    'siteId' => 5331,
     *    'subTotal' => 3950.39,
     *    'shippingAmount' => 62,
     *    'discountAmount' => 0,
     *    'taxAmount' => 0,
     *    'grandTotal' => 4212.39,
     *    'entries' => array(
     *        'productCode' => 'sk4123',
     *        'productName' => 'myProduct',
     *        'categoryName' => 'myCategory',
     *        'unitPrice' => 12.4,
     *        'quantity' => 1
     *     )
     * );
     */
    public function createOrder(array $order)
    {
        $tracker = $this->getTracker($order['siteId']);

        $tracker->setVisitorId($order['visitorId']);
        $tracker->setCustomVariable(1, 'U', self::VISITOR_CUSTOMER);

        $tracker->setCustomVariable(5, 'orderId', $order['orderId']);

        foreach ($order['entries'] as $entry) {
            $tracker->addEcommerceItem(
                $entry['productCode'],
                $entry['productName'],
                $entry['categoryName'],
                $entry['unitPrice'],
                $entry['quantity']
            );
        }

        try {
            $response = $tracker->doTrackEcommerceOrder(
                $order['orderId'],
                $order['grandTotal'],
                $order['subTotal'],
                $order['taxAmount'],
                $order['shippingAmount'],
                $order['discountAmount']
            );
        } catch (Exception $e) {
            throw new Jirafe_Exception("Failed to create order: {$e->getMessage()}");
        }
    }

    private function getTracker($siteId)
    {
        $tracker = new Jirafe_TrackerApi_PiwikTracker($siteId, $this->baseUrl);
        $tracker->setTokenAuth($this->appToken);
        $tracker->setIp($_SERVER['REMOTE_ADDR']);
        $tracker->disableCookieSupport();

        return $tracker;
    }
}
