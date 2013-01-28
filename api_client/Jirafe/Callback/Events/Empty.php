<?php

class Jirafe_Callback_Events_Empty extends Jirafe_Callback_Event
{
    public function __construct($eventVersion)
    {
      $this->version = $eventVersion;
      $this->action = Jirafe_Callback_Actions::NOOP;
    }

    public function data() {
      return array();
    }
}

