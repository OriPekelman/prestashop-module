<?php

class Jirafe_Callback_Events_Item extends Jirafe_Callback_Event
{
    public function __construct($data)
    {
      $this->data = $data;
    }

    public function data() {
      return array(
        'sku'      => $this->data['sku'],
        'name'     => $this->data['name'],
        'price'    => $this->formatAmount($this->data['price']),
        'quantity' => $this->data['quantity'],
        'category' => $this->data['category'],
      );
    }
}
