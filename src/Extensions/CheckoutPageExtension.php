<?php

namespace XD\SilverStripeFacebookServerSidePixel\Extensions;

use SilverStripe\Control\Director;
use SilverShop\Cart\ShoppingCart;
use SilverShop\Page\CheckoutPage;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Extension;
use XD\SilverStripeFacebookServerSidePixel\Client\Client;

class CheckoutPageExtension extends Extension
{
    public function contentcontrollerInit()
    {
        if (Director::is_ajax()) {
            return;
        }
        
        $controller = Controller::curr();
        $req = $controller->getRequest();
        if (empty($req->param('Action')) && $order = ShoppingCart::curr()) {
            $facebookClient = new Client();
            $cart = $order->toFacebookContent();
            $facebookClient->sendInitiateCheckoutEvent($cart);
        }
    }
}
