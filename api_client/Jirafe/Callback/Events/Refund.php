<?php

class Jirafe_Callback_Events_Refund extends Jirafe_Callback_Event
{
    public function data()
    {
      $items = array();
      foreach ($this->data['items'] as $item)
      {
          $items[] = $item->data();
      }
      return array(
        'refundId'                  => $this->data['identifier'],
        'orderId'                   => $this->data['orderIdentifier'],
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
