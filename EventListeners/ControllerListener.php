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

use LegacyApi\Controller\Api\BaseApiController;
use LegacyApi\LegacyApi;
use LegacyApi\Model\ApiQuery;
use LegacyApi\Model\Api;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\SecurityContext;
use Thelia\Exception\AdminAccessDenied;
use Thelia\Log\Tlog;

/**
 * Class ControllerListener
 * @package Thelia\Core\EventListener
 * @author Manuel Raynaud <manu@raynaud.io>
 */
class ControllerListener implements EventSubscriberInterface
{
    public function __construct(protected SecurityContext $securityContext)
    {
    }

    public function adminFirewall(ControllerEvent $event): void
    {
        $controller = $event->getController();
        //check if an admin is logged in
        if (is_array($controller)
            &&
            ($controller[0] instanceof BaseAdminController)
            &&
            false === $this->securityContext->hasAdminUser()
            &&
            (int)$event->getRequest()->attributes->get('not-logged') !== 1) {
                throw new AdminAccessDenied('API access denied');
            }
    }

    public function apiFirewall(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (is_array($controller)
            &&
            $controller[0] instanceof BaseApiController
            &&
            (int) $event->getRequest()->attributes->get('not-logged') !== 1
        ) {
            $apiAccount = $this->checkApiAccess(
                $event->getRequest()
            );

            $controller[0]->setApiUser($apiAccount);
        }
    }

    private function checkApiAccess(Request $request): Api
    {
        $key = $request->headers->get('authorization');
        if (null !== $key) {
            $key = substr($key, 6);
        }

        Tlog::getInstance()->debug("Authorization token : " . $key);

        $apiAccount = ApiQuery::create()->findOneByApiKey($key);

        if (null === $apiAccount) {
            throw new UnauthorizedHttpException('Token');
        }

        $secureKey = pack('H*', $apiAccount->getSecureKey());

        $sign = hash_hmac('sha1', $request->getContent(), $secureKey);

        Tlog::getInstance()->debug("Content: " . $request->getContent());
        Tlog::getInstance()->debug("Expected signature: " . $sign);

        if (! LegacyApi::getConfigValue('do_not_check_signature', false)
            &&
            $sign !== $request->query->get('sign')) {
            Tlog::getInstance()->error("Got wrong signature: " . $request->query->get('sign'));

            throw new PreconditionFailedHttpException('wrong body request signature');
        }

        return $apiAccount;
    }

    /**
     * {@inheritdoc}
     * @api
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => [
                ['adminFirewall', 128],
                ['apiFirewall', 128]
            ]
        ];
    }
}
