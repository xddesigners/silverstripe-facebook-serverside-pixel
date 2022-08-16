<?php

namespace XD\SilverStripeFacebookServerSidePixel\Extensions;

use SilverStripe\Control\Director;
use SilverStripe\Core\Extension;
use XD\SilverStripeFacebookServerSidePixel\Client\Client;

class SiteTreeExtension extends Extension
{
    public function contentcontrollerInit()
    {
        if (Director::is_ajax()) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] != 'GET') {
            return;
        }

        $facebookClient = new Client();
        
        if (Client::config()->get('create_event_id')) {
            $facebookClient->createEventId();
        }
        
        $facebookClient->sendPageViewEvent();
    }
}
