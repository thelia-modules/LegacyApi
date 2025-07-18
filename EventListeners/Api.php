<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace LegacyApi\EventListeners;

use LegacyApi\Event\Api\ApiCreateEvent;
use LegacyApi\Event\Api\ApiDeleteEvent;
use LegacyApi\Event\Api\ApiUpdateEvent;
use LegacyApi\Event\Events;
use LegacyApi\Model\Api as ApiModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Action\BaseAction;

/**
 * Class Api
 * @package Thelia\Action
 * @author Manuel Raynaud <manu@raynaud.io>
 */
class Api extends BaseAction implements EventSubscriberInterface
{
    public function createApi(ApiCreateEvent $event)
    {
        $api = new ApiModel();

        $api->setLabel($event->getLabel())
            ->setProfileId($event->getProfile())
            ->save()
        ;
    }

    public function deleteApi(ApiDeleteEvent $event)
    {
        $api = $event->getApi();

        $api->delete();
    }

    public function updateApi(ApiUpdateEvent $event)
    {
        $api = $event->getApi();

        $api->setProfileId($event->getProfile())
            ->save();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::API_CREATE => ['createApi', 128],
            Events::API_DELETE => ['deleteApi', 128],
            Events::API_UPDATE => ['updateApi', 128],
        ];
    }
}
