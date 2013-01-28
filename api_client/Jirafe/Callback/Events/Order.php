<?php

class Jirafe_Callback_Events_Order extends Jirafe_Callback_Event
{
    const NEW_ORDER         = 'new';
    const PAYMENT_PENDING   = 'pendingPayment';
    const PROCESSING        = 'processing';
    const COMPLETE          = 'complete';
    const CLOSED            = 'closed';
    const CANCELED          = 'canceled';
    const HELD              = 'held';
    const PAYMENT_REVIEW    = 'paymentReview';
    const UNKNOWN           = 'unknown';

    public function data() {
      $items = array();
      foreach ($this->data['items'] as $item)
      {
          $items[] = $item->data();
      }
      return array(
        'orderId'                   => $this->data['identifier'],
        'status'                    => $this->data['status'],
        'customerHash'              => $this->data['customerData'],
        'visitorId'                 => $this->data['visitorIdentifier'],
        'time'                      => strtotime($this->data['createdAt']),
        'grandTotal'                => $this->formatAmount($this->data['grandTotal']),
        'subTotal'                  => $this->formatAmount($this->data['subTotal']),
        'taxAmount'                 => $this->formatAmount($this->data['taxAmount']),
        'shippingAmount'            => $this->formatAmount($this->data['shippingAmount']),
        'discountAmount'            => $this->formatAmount(abs($this->data['discountAmount'])),
        'items'                     => $items
      );
    }
}
