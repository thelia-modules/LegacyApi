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

namespace LegacyApi\Controller\Api;

use LegacyApi\Service\LoopProvider;
use Propel\Runtime\Propel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\EventDispatcher\Event;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Exception\NotImplementedException;
use Thelia\Form\BaseForm;

/**
 * Class AbstractCrudApiController
 * @package LegacyApi\Controller\Api
 * @author Benjamin Perche <bperche@openstudio.fr>
 */
abstract class AbstractReadOnlyApiController extends AbstractCrudApiController
{

    /**
     * @param $objName
     * @param $resources
     * @param $createEvents
     * @param $updateEvents
     * @param $deleteEvents
     * @param array $modules The module codes related to the ACL
     * @param array $defaultLoopArgs The loop default arguments
     * @param string $idParameterName The "id" parameter name in your loop. Generally "id"
     */
    public function __construct(
        protected LoopProvider $loopProvider,
        $objName,
        $resources,
        $modules = [],
        $defaultLoopArgs = null,
        $idParameterName = "id"
    ) {
        parent::__construct(
            $loopProvider,
            $objName,
            $resources,
            [],
            [],
            [],
            $modules,
            $defaultLoopArgs,
            $idParameterName
        );
    }

    protected function getCreationForm(array $data = array())
    {
        throw new NotImplementedException();
    }

    protected function getUpdateForm(array $data = array())
    {
        throw new NotImplementedException();
    }

    protected function extractObjectFromEvent(Event $event)
    {
        throw new NotImplementedException();
    }

    protected function getCreationEvent(array &$data)
    {
        throw new NotImplementedException();
    }

    protected function getUpdateEvent(array &$data)
    {
         throw new NotImplementedException();
    }

    protected function getDeleteEvent($entityId)
    {
        throw new NotImplementedException();
    }
}
