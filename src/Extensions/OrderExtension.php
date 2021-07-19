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
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use XD\SilverStripeFacebookServerSidePixel\Client\Client;

/**
 * @property Order owner
 */
class OrderExtension extends DataExtension
{
    // public function onPaid()
    // {
        // order is not set to paid when this part is enabled
        // $facebookClient = new Client();
        // $cart = $this->owner->toFacebookContent();
        
        // $userData = $facebookClient->createUserData();
        // $userData->setEmail($this->owner->getLatestEmail());
        // $userData->setFirstName($this->owner->FirstName);
        // $userData->setLastName($this->owner->Surname);

        // $facebookClient->sendPurchaseEvent($cart, $userData);
    // }

    public function afterAdd($item, $buyable, $quantity, $filter)
    {
        $facebookClient = new Client();
        $cart = $this->owner->toFacebookContent();
        $facebookClient->sendAddToCartEvent($cart);
    }

    public function toFacebookContent()
    {
        $products = [];
        foreach ($this->owner->Items() as $item) {
            /** @var OrderItem $item */
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
