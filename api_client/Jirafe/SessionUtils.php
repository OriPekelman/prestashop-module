<?php

class Jirafe_SessionUtils
{
    public function getVisitorId($siteId)
    {
        $tracker = new Jirafe_TrackerApi_PiwikTracker($siteId);
        return $tracker->getVisitorId();
    }
}
