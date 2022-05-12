<?php

namespace XD\SilverStripeFacebookServerSidePixel\Extensions;

use Exception;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\Content;
use FacebookAds\Object\ServerSide\DeliveryCategory;
use SilverShop\Extension\ShopConfigExtension;
use SilverShop\Model\Order;
use SilverShop\Model\Product\OrderItem;
use SilverShop\Model\Variation\OrderItem as VariationOrderItem;
use SilverShop\Page\ProductCategory;
use SilverStripe\Control\Cookie;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use XD\SilverStripeFacebookServerSidePixel\Client\Client;

/**
 * @property Order owner
 */
class OrderExtension extends DataExtension
{

    private static $db = [
        'UserAgent' => 'Varchar',
        'Fbp' => 'Varchar',
        'Fbc' => 'Varchar'
    ];

     public function onPaid()
     {
         // order is not set to paid when this part is enabled
         $facebookClient = new Client();
         $cart = $this->owner->toFacebookContent();

         $userData = $facebookClient->createUserData();
         $userData->setEmail($this->owner->Email);
         $userData->setFirstName($this->owner->FirstName);
         $userData->setLastName($this->owner->Surname);
         $userData->setClientUserAgent($this->owner->UserAgent);
         if( $this->owner->Fbp ) {
             $userData->setFbp($this->owner->Fbp);
         }
         if( $this->owner->Fbc ) {
             $userData->setFbc($this->owner->Fbc);
         }

         $facebookClient->sendPurchaseEvent($cart, $userData);
     }

    public function afterAdd($item, $buyable, $quantity, $filter)
    {
        $facebookClient = new Client();
        $cart = $this->owner->toFacebookContent();
        $facebookClient->sendAddToCartEvent($cart);
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if( empty($this->owner->UserAgent) ) {
            $this->owner->UserAgent = $_SERVER['HTTP_USER_AGENT'];
        }
        if( empty($this->owner->Fbp) && $fbp = Cookie::get('_fbp') ) {
            $this->owner->Fbp = $fbp;
        }
        if( empty($this->owner->Fbc) && $fbc = Cookie::get('_fbc') ) {
            $this->owner->Fbc = $fbc;
        }
    }

    public function toFacebookContent()
    {
        $products = [];
        foreach ($this->owner->Items() as $item) {
            /** @var OrderItem $item */
            if ($item instanceof OrderItem) {
                $product = (new Content())
                    ->setProductId($item->Product()->ID)
                    ->setTitle($item->Product()->getTitle())
                    ->setQuantity($item->Quantity)
                    ->setItemPrice($item->UnitPrice)
                    ->setDeliveryCategory(DeliveryCategory::HOME_DELIVERY);

                if (($category = $item->Product()->Parent()) && $category instanceof ProductCategory) {
                    $product = $product->setCategory($category->Title);
                }

                if ($item instanceof VariationOrderItem) {
                    $product = $product->setDescription($item->SubTitle());
                }

                $this->owner->extend('updateToFacebookContentItem', $product, $item);
                array_push($products, $product);
            }
        }

        $currency = ShopConfigExtension::config()->get('base_currency');
        $cart = (new CustomData())
            ->setOrderId($this->owner->Reference)
           ->setContents($products)
           ->setCurrency($currency)
           ->setContentType('product')
           ->setValue($this->owner->GrandTotal());
        
        $this->owner->extend('updateToFacebookContent', $cart);
        return $cart;
    }
}
