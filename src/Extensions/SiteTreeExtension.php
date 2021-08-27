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
        
        $facebookClient = new Client();
        $facebookClient->sendPageViewEvent();
    }
}
