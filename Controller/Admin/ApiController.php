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

namespace LegacyApi\Controller\Admin;

use LegacyApi\Event\Api\ApiCreateEvent;
use LegacyApi\Event\Api\ApiDeleteEvent;
use LegacyApi\Event\Api\ApiUpdateEvent;
use LegacyApi\Event\Events;
use LegacyApi\Form\Api\ApiCreateForm;
use LegacyApi\Form\Api\ApiUpdateForm;
use LegacyApi\Model\Api;
use LegacyApi\Model\ApiQuery;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Tools\URL;

/**
 * Class ApiController
 * @package Thelia\Controller\Admin
 * @author Manuel Raynaud <manu@raynaud.io>
 */
class ApiController extends BaseAdminController
{
    protected $api;

    public function downloadAction($api_id)
    {
        if (null === $response = $this->checkApiAccess($api_id)) {
            $response = $this->retrieveSecureKey($this->api);
        }

        return $response;
    }

    public function deleteAction(EventDispatcherInterface $dispatcher)
    {
        $api_id = $this->getRequest()->request->get('api_id');

        if (null === $response = $this->checkApiAccess($api_id)) {
            $response = $this->deleteApi($this->api, $dispatcher);
        }

        return $response;
    }

    private function deleteApi(Api $api, EventDispatcherInterface $dispatcher)
    {
        $event = new ApiDeleteEvent($api);

        $dispatcher->dispatch($event, Events::API_DELETE);

        return new RedirectResponse(URL::getInstance()->absoluteUrl('/admin/module/LegacyApi'));
    }

    /**
     * @param  Api                                        $api
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function retrieveSecureKey(Api $api)
    {
        $response = new Response($api->getSecureKey());
        $response->headers->add([
            'Content-Type' => 'application/octet-stream',
            'Content-disposition' => sprintf('filename=%s.key', $api->getApiKey())
        ]);

        return $response;
    }

    public function createAction(EventDispatcherInterface $dispatcher)
    {
        if (null !== $response = $this->checkAuth([AdminResources::API], [], AccessManager::CREATE)) {
            return $response;
        }

        $form = $this->createForm(ApiCreateForm::getName());
        $error_msg = null;
        try {
            $createForm = $this->validateForm($form);

            $event = new ApiCreateEvent(
                $createForm->get('label')->getData(),
                $createForm->get('profile')->getData() ?: null
            );

            $dispatcher->dispatch($event, Events::API_CREATE);

            return new RedirectResponse($form->getSuccessUrl());
        } catch (FormValidationException $e) {
            $error_msg = $this->createStandardFormValidationErrorMessage($e);
        } catch (\Exception $e) {
            $error_msg = $e->getMessage();
        }

        if (false !== $error_msg) {
            $this->setupFormErrorContext(
                $this->getTranslator()->trans("%obj creation", array('%obj' => 'Api')),
                $error_msg,
                $form,
                $e
            );

            // At this point, the form has error, and should be redisplayed.
            return $this->renderList();
        }
    }

    public function updateAction($api_id)
    {
        if (null === $response = $this->checkApiAccess($api_id)) {
            $form = $this->createForm(ApiUpdateForm::getName(), FormType::class, ['profile' => $this->api->getProfileId()]);

            $this->getParserContext()->addForm($form);

            $response = $this->renderList($api_id);
        }

        return $response;
    }

    public function processUpdateAction($api_id, EventDispatcherInterface $dispatcher)
    {
        if (null === $response = $this->checkApiAccess($api_id)) {
            $response = $this->doUpdate($this->api, $dispatcher);
        }

        return $response;
    }

    private function doUpdate(Api $api, EventDispatcherInterface $dispatcher)
    {
        $error_msg = null;
        $form = $this->createForm(ApiUpdateForm::getName());
        try {
            $updateForm = $this->validateForm($form);

            $event = new ApiUpdateEvent($api, $updateForm->get('profile')->getData() ?: null);

            $dispatcher->dispatch($event, Events::API_UPDATE);

            $response = new RedirectResponse(URL::getInstance()->absoluteUrl('/admin/module/LegacyApi'));
        } catch (FormValidationException $e) {
            $error_msg = $this->createStandardFormValidationErrorMessage($e);
        } catch (\Exception $e) {
            $error_msg = $e->getMessage();
        }

        if (null !== $error_msg) {
            $this->setupFormErrorContext(
                "foo",
                $error_msg,
                $form,
                $e
            );

            $response = $this->renderList($api->getId());
        }

        return $response;
    }

    private function checkApiAccess($api_id)
    {
        if (null === $response = $this->checkAuth([AdminResources::API], [], AccessManager::UPDATE)) {
            $this->api = ApiQuery::create()->findPk($api_id);

            if (null === $this->api) {
                $response = $this->errorPage(Translator::getInstance()->trans("api id %id does not exists", ['%id' => $api_id]));
            }
        }

        return $response;
    }

    protected function renderList($api_id = null)
    {
        return new RedirectResponse(
            URL::getInstance()->absoluteUrl(
                '/admin/module/LegacyApi',
                $api_id ? ['api_id' => $api_id] : null
            )
        );
    }
}
