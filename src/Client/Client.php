<?php

namespace XD\SilverStripeFacebookServerSidePixel\Client;

use Exception;
use FacebookAds\Api;
use FacebookAds\Object\ServerSide\ActionSource;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\UserData;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Security\Security;

class Client
{
    use Configurable;
    use Injectable;
    use Extensible;

    private static $send_member_data = false;

    protected $events = [];

    /**
     * Init the facebook api
     */
    public function __construct()
    {
        $accessToken = Environment::getEnv('FB_ACCESS_TOKEN');
        $pixelId = Environment::getEnv('FB_PIXEL_ID');

        if (!$accessToken || !$pixelId) {
            return false;
        }

        Api::init(null, null, $accessToken, false);
    }

    /**
     * Add events to send
     */
    public function createEvent($name, UserData $userData = null, CustomData $customData = null)
    {
        $controller = Controller::curr();
        $req = $controller->getRequest();
        $sourceUrl = Director::absoluteURL($_SERVER['REQUEST_URI']);

        $event = (new Event())
            ->setEventName($name)
            ->setEventTime(time())
            ->setEventSourceUrl($sourceUrl)
            ->setUserData($userData)
            ->setCustomData($customData)
            ->setActionSource(ActionSource::WEBSITE);

        if (isset($_SESSION['EVENTID']) && $event_id = $_SESSION['EVENTID']) {
            $event->setEventId($event_id);
        }

        return $event;
    }

    public function addEvent(Event $event)
    {
        array_push($this->events, $event);
        return $this;
    }

    /**
     * Send the event list
     */
    public function sendEvents()
    {
        if (!$pixelId = Environment::getEnv('FB_PIXEL_ID')) {
            return false;
        }

        $data = [];
        if ($testEvent = Environment::getEnv('FB_TEST_EVENT_CODE')) {
            $data['test_event_code'] = $testEvent;
        }

        try {
            $request = (new EventRequest($pixelId, $data))
                ->setEvents($this->events);

            // Execute the request
            $response = $request->execute();

            // Clear the event list
            $this->events = [];

            return $response;
        } catch (Exception $e) {
            $this->events = [];
            return false;
        }
    }

    /**
     * Create user data based on the request
     * If a user is logged in use their data (if enabled)
     */
    public function createUserData()
    {
        $controller = Controller::curr();
        $req = $controller->getRequest();

        $userData = (new UserData())
            ->setClientIpAddress($req->getIP())
            ->setClientUserAgent($_SERVER['HTTP_USER_AGENT']);

        if (self::config()->get('send_member_data') && $member = Security::getCurrentUser()) {
            $userData
                ->setEmail($member->Email)
                ->setFirstName($member->FirstName)
                ->setLastName($member->Surname);
        }

        if ($fbp = Cookie::get('_fbp')) {
            $userData->setFbp($fbp);
        }

        if ($fbc = Cookie::get('_fbc')) {
            $userData->setFbc($fbc);
        }

        $this->extend('updateUserData', $userData);

        return $userData;
    }

    /**
     * Default page view event
     */
    public function sendPageViewEvent()
    {
        $userData = $this->createUserData();
        $event = $this->createEvent('PageView', $userData);
        return $this->addEvent($event)->sendEvents();
    }

    /**
     * Default purchase event
     * Pass the bought items trough the customData prop
     */
    public function sendPurchaseEvent(CustomData $customData, UserData $userData = null)
    {
        if (!$userData) {
            $userData = $this->createUserData();
        }

        $event = $this->createEvent('Purchase', $userData, $customData);
        return $this->addEvent($event)->sendEvents();
    }

    /**
     * Default checkout event
     * Pass the cart items trough the customData prop
     */
    public function sendInitiateCheckoutEvent(CustomData $customData, UserData $userData = null)
    {
        if (!$userData) {
            $userData = $this->createUserData();
        }

        $event = $this->createEvent('InitiateCheckout', $userData, $customData);
        return $this->addEvent($event)->sendEvents();
    }

    /**
     * Default add to cart event
     * Pass the cart items trough the customData prop
     */
    public function sendAddToCartEvent(CustomData $customData, UserData $userData = null)
    {
        if (!$userData) {
            $userData = $this->createUserData();
        }

        $event = $this->createEvent('AddToCart', $userData, $customData);
        return $this->addEvent($event)->sendEvents();
    }
}
