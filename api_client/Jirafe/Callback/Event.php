<?php

class Jirafe_Callback_Event extends Jirafe_Callback_Object
{
    protected $version;
    protected $action;
    protected $data;

    public function __construct($eventVersion, $eventAction, array $eventData)
    {
      $this->version = $eventVersion;
      $this->action = $eventAction;
      $this->data = $eventData;
    }

    private static function post($url, $data)
    {
        $parsedUrl = parse_url($url);
        $parsedUrl['query'] = $data;
        $port = isset($parsedUrl['port']) ? $parsedUrl['port'] : 80;
        $timeout = 30;

        $connection = fsockopen($parsedUrl['host'], $port, $errno, $errstr, $timeout);

        if (!$connection) {
            throw new Exception('Unable to POST to '.$url.' with '.$data);
        } else {
            $out = "POST " . $parsedUrl['path'] . " HTTP/1.1\r\n";
            $out.= "Host: " . $parsedUrl['host'] . "\r\n";
            $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
            $out.= "Content-Length: " . strlen($parsedUrl['query']) . "\r\n";
            $out.= "Connection: Close\r\n\r\n";
            if (isset($parsedUrl['query'])) {
                $out.= $parsedUrl['query'];
            }
            fwrite($connection, $out);
            while (!feof($connection))
            {
              fgets($connection);
            }
            fclose($connection);
            return true;
        }
    }

    public static function notify($siteId)
    {
        return self::post(self::callbackUrl(), 'siteId='.urlencode($siteId));
    }

    public static function send($siteId, $token, $timestamp, $events)
    {
        $eventWrappers = array();
        foreach ($events as $event) {
            $eventWrappers[] = array(
                'v' => $event->version(),
                'a' => $event->action(),
                'd' => $event->data()
            );
        }
        $data = array(
          'token' => $token,
          'siteId' => $siteId,
          'events' => json_encode($eventWrappers),
          'timestamp'=> $timestamp
        );
        return self::post(self::eventsUrl(), http_build_query($data));
    }

    public function version()
    {
      return (int)$this->version;
    }

    public function action()
    {
      return $this->action;
    }

    public function data()
    {
      return $this->data;
    }

    public function formatAmount($amount)
    {
        return sprintf("%01.4f", $amount);
    }

    public static function callbackUrl()
    {
        return self::baseUrl().'/cmb';
    }

    public static function eventsUrl()
    {
        return self::baseUrl().'/events';
    }
}

