# SilverStripe Facebook Serverside pixel

Add serverside fb pixel tracking to your silverstripe site, or silvershop products (if installed).

## Install

```zsh
composer require xddesigners/silverstripe-facebook-serverside-pixel
```

Get your pixel id and access token and set these in your environment (.env) file.

```env
FB_ACCESS_TOKEN="FB_ACCESS_TOKEN"
FB_PIXEL_ID="FB_PIXEL_ID"
FB_TEST_EVENT_CODE="OPTIONAL"
```

## Configuration

By default the pageview event is added to the SiteTree class. If you have a SilverShop you'll also want to enable the extensions on Order and CheckoutPage:

```yml
SilverShop\Model\Order:
  extensions:
    - XD\SilverStripeFacebookServerSidePixel\Extensions\OrderExtension
SilverShop\Page\CheckoutPage:
  extensions:
    - XD\SilverStripeFacebookServerSidePixel\Extensions\CheckoutPageExtension
```

## Add custom events

You can push custom events by using the following methods:

```php
// Initiate the Client
$client = new XD\SilverStripeFacebookServerSidePixel\Client\Client();

// Generate user date from the current session. 
$userData = $client->createUserData();

// Enricht the user data (Optional)
$userData->setEmail('user@email.com');

// Create the custom data to send to your event, for example a cart (Optional)
$product = (new Content())
    ->setProductId('my_product_id')
    ->setTitle('product_title')
    ->setQuantity(2)
    ->setItemPrice(57.25)
    ->setDeliveryCategory(DeliveryCategory::HOME_DELIVERY);
            
$customData = (new CustomData())
    ->setOrderId('my_order_id')
    ->setContents([$product])
    ->setCurrency('eur')
    ->setContentType('product')
    ->setValue(114.50);

// add the event (multiple events can be chained) and send the events
$client
    ->addEvent('MyCustomEvent', $userData, $customData)
    ->sendEvents();
```
