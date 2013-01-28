<?php

class Jirafe_Callback_Object
{
    //const TRACKER_URL = "http://test-data.jirafe.com";
    const TRACKER_URL = "https://data.jirafe.com";

    public static function baseUrl()
    {
        return rtrim(self::TRACKER_URL, '/');
    }
}
