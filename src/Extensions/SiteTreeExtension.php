<?php

namespace XD\SilverStripeFacebookServerSidePixel\Extensions;

use SilverStripe\Core\Extension;
use XD\SilverStripeFacebookServerSidePixel\Client\Client;

class SiteTreeExtension extends Extension
{
    public function contentcontrollerInit()
    {
        $facebookClient = new Client();
        $facebookClient->sendPageViewEvent();
    }
}
