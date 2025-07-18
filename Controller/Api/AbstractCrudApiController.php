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
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Form\BaseForm;

/**
 * Class AbstractCrudApiController
 * @package LegacyApi\Controller\Api
 * @author Benjamin Perche <bperche@openstudio.fr>
 */
abstract class AbstractCrudApiController extends BaseApiController
{
    /**
     * @var string
     *
     * The entity name to display errors
     */
    protected $objName;

    /**
     * @var mixed|array
     *
     * ACL resources used for rights checking
     */
    protected $resources;

    /**
     * @var array
     *
     * Events to call on entity creation
     */
    protected $createEvents;

    /**
     * @var array
     *
     * Events to call on entity update
     */
    protected $updateEvents;

    /**
     * @var array
     *
     * Events to call on entity deletion
     */
    protected $deleteEvents;

    /**
     * @var mixed|array
     *
     * ACL modules used for rights checking
     */
    protected $modules;

    /**
     * @var integer
     *
     * limit for the list operation
     */
    protected $defaultLoopArgs;

    /**
     * @var string
     *
     * The id parameter used to filter in the loop
     */
    protected $idParameterName;

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
        $createEvents,
        $updateEvents,
        $deleteEvents,
        $modules = [],
        $defaultLoopArgs = null,
        $idParameterName = "id"
    ) {
        $this->objName = $objName;
        $this->resources = $resources;

        $this->initializeEvents([
            "createEvents" => $createEvents,
            "updateEvents" => $updateEvents,
            "deleteEvents" => $deleteEvents,
        ]);

        $this->modules = $modules;
        $this->defaultLoopArgs = $defaultLoopArgs ?: ["limit" => 10, "offset" => 0, "visible" => "*"];
        $this->idParameterName = $idParameterName;
    }

    /**
     * @return JsonResponse
     *
     * The method provides the "list" feed for an entity.
     */
    public function listAction(Request $request)
    {
        $this->checkAuth($this->resources, $this->modules, AccessManager::VIEW);

        if ($request->query->has('id')) {
            $request->query->remove('id');
        }

        $params = array_merge(
            $this->defaultLoopArgs,
            $request->query->all()
        );

        try {
            $results = $this->getLoopResults($params);
        } catch (\Exception $e) {
            throw new HttpException(500, json_encode(["error" => $e->getMessage()]));
        }

        return new JsonResponse($results);
    }

    /**
     * @param $entityId
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * This method provides the "GET" feed for an entity,
     * you can define a route like this:
     *
     * <route id="api.my-entity.get" path="/api/my-entity/{entityId}" methods="get">
     *   <default key="_controller">Thelia:Api\MyEntity:get</default>
     *   <requirement key="entityId">\d+</requirement>
     * </route>
     */
    public function getAction($entityId, Request $request)
    {
        $this->checkAuth($this->resources, $this->modules, AccessManager::VIEW);

        $params = array_merge(
            $request->query->all(),
            [
                $this->idParameterName => $entityId,
            ]
        );

        $result = $this->getLoopResults($params);

        if ($result->isEmpty()) {
            $this->entityNotFound($entityId);
        }

        return new JsonResponse($result);
    }

    /**
     * @return JsonResponse
     *
     * This feed creates your entity.
     */
    public function createAction(EventDispatcherInterface $dispatcher, Request $request)
    {
        $this->checkAuth($this->resources, $this->modules, AccessManager::CREATE);

        $baseForm = $this->getCreationForm();

        $con = Propel::getConnection();
        $con->beginTransaction();

        try {
            $form = $this->submitAndValidateForm($baseForm, $request);
            $data = $form->getData();

            $event = $this->getCreationEvent($data);

            foreach ($this->createEvents as $eventName) {
                $dispatcher->dispatch($event, $eventName);
            }

            $this->afterCreateEvents($event, $data, $dispatcher);

            $con->commit();
        } catch (HttpException $e) {
            $con->rollBack();

            return new JsonResponse(["error" => $e->getMessage()], $e->getStatusCode());
        } catch (\Exception $e) {
            $con->rollBack();

            return new JsonResponse(["error" => $e->getMessage()], 500);
        }

        $obj = $this->extractObjectFromEvent($event);
        $id = $this->extractIdFromObject($obj);

        return new JsonResponse(
            $this->getLoopResults(
                array_merge(
                    $request->query->all(),
                    [$this->idParameterName => $id]
                )
            ),
            201
        );
    }

    /**
     * @return JsonResponse
     *
     * Generic action to update an entity
     */
    public function updateAction(EventDispatcherInterface $dispatcher, Request $request)
    {
        $this->checkAuth($this->resources, $this->modules, AccessManager::UPDATE);

        $baseForm = $this->getUpdateForm();

        $baseForm->getFormBuilder()
            ->addEventListener(
                FormEvents::PRE_SUBMIT,
                [$this, "hydrateUpdateForm"]
            )
        ;

        $con = Propel::getConnection();
        $con->beginTransaction();

        try {
            $form = $this->submitAndValidateForm($baseForm, $request);
            $data = $form->getData();
            $event = $this->getUpdateEvent($data);

            foreach ($this->updateEvents as $eventName) {
                $dispatcher->dispatch($event, $eventName);
            }

            $this->afterUpdateEvents($event, $data, $dispatcher);

            $con->commit();
        } catch (HttpException $e) {
            $con->rollBack();

            return new JsonResponse(["error" => $e->getMessage()], $e->getStatusCode());
        } catch (\Exception $e) {
            $con->rollBack();

            return new JsonResponse(["error" => $e->getMessage()], 500);
        }

        $obj = $this->extractObjectFromEvent($event);
        $id = $this->extractIdFromObject($obj);

        return new JsonResponse(
            $this->getLoopResults(
                array_merge(
                    $request->query->all(),
                    [$this->idParameterName => $id]
                )
            ),
            201
        );
    }

    /**
     * @param $entityId
     * @return JsonResponse|\Thelia\Core\HttpFoundation\Response
     *
     * generic feed for deleting an entity
     */
    public function deleteAction($entityId, EventDispatcherInterface $dispatcher)
    {
        $this->checkAuth($this->resources, $this->modules, AccessManager::DELETE);

        $con = Propel::getConnection();
        $con->beginTransaction();

        try {
            $event = $this->getDeleteEvent($entityId);

            $obj = $this->extractObjectFromEvent($event);

            if (null === $obj || false === $obj) {
                $this->entityNotFound($entityId);
            }

            foreach ($this->deleteEvents as $eventName) {
                $dispatcher->dispatch($event, $eventName);
            }

            $con->commit();
        } catch (HttpException $e) {
            $con->rollBack();

            return new JsonResponse(["error" => $e->getMessage()], $e->getStatusCode());
        } catch (\Exception $e) {
            $con->rollBack();

            return new JsonResponse(["error" => $e->getMessage()], 500);
        }

        return $this->nullResponse(204);
    }

    /**
     * Returns the current class' loop results with the given parameters
     */
    protected function getLoopResults($loopParams): LoopResult
    {
        return $this->loopProvider->getLoopResults(
            $this->getLoop(),
            array_merge($this->defaultLoopArgs, $loopParams)
        );
    }

    // Helpers

    /**
     * @param $entityId
     *
     * Throws a 404 exception building an error message with $this->objName and the entity id
     */
    protected function entityNotFound($entityId)
    {
        throw new NotFoundHttpException(
            json_encode([
                "error" => sprintf("%s %s %d not found", $this->objName, $this->idParameterName, $entityId)
            ], JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @param $eventsDefinition
     *
     * Register events into the class' variables
     */
    protected function initializeEvents($eventsDefinition)
    {
        foreach ($eventsDefinition as $variableName => $eventValue) {
            $value = array();

            if (!empty($eventValue) && !is_array($eventValue)) {
                $value = [$eventValue];
            } elseif (is_array($eventValue)) {
                $value = $eventValue;
            }

            $this->$variableName = $value;
        }
    }

    /**
     * @param $locale
     * @param string $parameterName
     *
     * This helper defines the lang into the query ( you can use it to force a lang into a loop )
     */
    protected function setLocaleIntoQuery($locale, $parameterName = 'lang')
    {
        $request = $this->getRequest();
        if (!$request->query->has($parameterName)) {
            $request->query->set($parameterName, $locale);
        }
    }

    // Inner logic, override those methods to use your logic

    /**
     * @param mixed $obj
     * @return mixed
     *
     * After having extracted the object, now extract the id.
     */
    protected function extractIdFromObject($obj)
    {
        return $obj->getId();
    }

    /**
     * @param FormEvent $event
     *
     * This method in called on your update form FormEvents::PRE_SUBMIT event.
     *
     * You can treat the given form, rewrite some data ...
     */
    public function hydrateUpdateForm(FormEvent $event)
    {
        // This method is called on FormEvents::PRE_SUBMIT
    }

    /**
     * @param Event $event
     * @param array $data
     *
     * Hook after the entity creation
     */
    protected function afterCreateEvents(Event $event, array &$data, EventDispatcherInterface $dispatcher)
    {
        // This method is called after dispatches in createAction
    }

    /**
     * @param Event $event
     * @param array $data
     *
     * Hook after the entity update
     */
    protected function afterUpdateEvents(Event $event, array &$data, EventDispatcherInterface $dispatcher)
    {
        // This method is called after dispatches in updateAction
    }

    // Abstract methods

    /**
     * @return \Thelia\Core\Template\Element\BaseLoop
     *
     * Get the entity loop instance
     */
    abstract protected function getLoop();

    /**
     * @param array $data
     * @return \Thelia\Form\BaseForm
     *
     * Gives the form used for entities creation.
     */
    abstract protected function getCreationForm(array $data = array());

    /**
     * @param array $data
     * @return \Thelia\Form\BaseForm
     *
     * Gives the form used for entities update
     */
    abstract protected function getUpdateForm(array $data = array());

    /**
     * @param Event $event
     * @return null|mixed
     *
     * Get the object from the event
     *
     * if return null or false, the action will throw a 404
     */
    abstract protected function extractObjectFromEvent(Event $event);

    /**
     * @param array $data
     * @return \Symfony\Contracts\EventDispatcher\Event
     *
     * Hydrates an event object to dispatch on creation.
     */
    abstract protected function getCreationEvent(array &$data);


    /**
     * @param array $data
     * @return \Symfony\Contracts\EventDispatcher\Event
     *
     * Hydrates an event object to dispatch on update.
     */
    abstract protected function getUpdateEvent(array &$data);

    /**
     * @param mixed $entityId
     * @return \Symfony\Contracts\EventDispatcher\Event
     *
     * Hydrates an event object to dispatch on entity deletion.
     */
    abstract protected function getDeleteEvent($entityId);
}
