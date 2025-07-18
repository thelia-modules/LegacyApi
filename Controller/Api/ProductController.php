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

use LegacyApi\Form\Api\Product\ProductCreationForm;
use LegacyApi\Form\Api\Product\ProductModificationForm;
use LegacyApi\Service\LoopProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Thelia\Core\Event\Product\ProductCreateEvent;
use Thelia\Core\Event\Product\ProductDeleteEvent;
use Thelia\Core\Event\Product\ProductUpdateEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Template\Loop\Product;
use Thelia\Model\ProductQuery;

/**
 * Class ProductController
 * @package LegacyApi\Controller\Api
 * @author Manuel Raynaud <manu@raynaud.io>
 */
class ProductController extends BaseApiController
{
    public function __construct(protected LoopProvider $loopProvider)
    {
    }

    public function listAction(Request $request)
    {
        $this->checkAuth(AdminResources::PRODUCT, [], AccessManager::VIEW);

        if ($request->query->has('id')) {
            $request->query->remove('id');
        }

        $params = array_merge(
            $request->query->all(),
            [
                'limit' => $request->query->get('limit', 10),
                'offset' => $request->query->get('offset', 0)
            ]
        );

        return new JsonResponse($this->baseProductSearch($params));
    }

    public function getProductAction($productId, Request $request)
    {
        $this->checkAuth(AdminResources::PRODUCT, [], AccessManager::VIEW);

        $params = array_merge(
            $request->query->all(),
            ['id' => $productId]
        );

        $results = $this->baseProductSearch($params);

        if ($results->isEmpty()) {
            return new JsonResponse(
                array(
                    'error' => sprintf('product with id %d not found', $productId)
                ),
                404
            );
        }

        return new JsonResponse($results);
    }

    private function baseProductSearch($params)
    {
        return $this->loopProvider->getLoopResults(new Product(), $params);
    }

    public function createAction(EventDispatcherInterface $dispatcher, Request $request)
    {
        $this->checkAuth(AdminResources::PRODUCT, [], AccessManager::CREATE);

        $form = $this->createForm(ProductCreationForm::getName(), FormType::class, [], ['csrf_protection' => false]);

        try {
            $creationForm = $this->submitAndValidateForm($form, $request);

            $event = new ProductCreateEvent();
            $event->bindForm($creationForm);

            $dispatcher->dispatch($event, TheliaEvents::PRODUCT_CREATE);

            $product = $event->getProduct();

            $updateEvent = new ProductUpdateEvent($product->getId());

            $updateEvent->bindForm($creationForm);

            $dispatcher->dispatch($updateEvent, TheliaEvents::PRODUCT_UPDATE);

            $request->query->set('lang', $creationForm->get('locale')->getData());
            $response = $this->getProductAction($product->getId(), $request);
            $response->setStatusCode(201);

            return $response;
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function updateAction($productId, EventDispatcherInterface $dispatcher, Request $request)
    {
        $this->checkAuth(AdminResources::PRODUCT, [], AccessManager::UPDATE);

        $this->checkProductExists($productId);

        $form = $this->createForm(
            ProductModificationForm::getName(),
            FormType::class,
            ['id' => $productId],
            [
                'csrf_protection' => false,
                'method' => 'PUT'
            ]
        );

        $data = $request->request->all();
        $data['id'] = $productId;
        $request->request->add($data);

        try {
            $updateForm = $this->submitAndValidateForm($form, $request);

            $event = new ProductUpdateEvent($productId);
            $event->bindForm($updateForm);

            $dispatcher->dispatch($event, TheliaEvents::PRODUCT_UPDATE);

            return new JsonResponse(null, 204);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteAction($productId, EventDispatcherInterface $dispatcher)
    {
        $this->checkAuth(AdminResources::PRODUCT, [], AccessManager::DELETE);

        $this->checkProductExists($productId);

        try {
            $event = new ProductDeleteEvent($productId);

            $dispatcher->dispatch($event, TheliaEvents::PRODUCT_DELETE);
            return new Response('', 204);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @param $productId
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function checkProductExists($productId)
    {
        $product = ProductQuery::create()
            ->findPk($productId);

        if (null === $product) {
            throw new HttpException(404, sprintf('{"error": "product with id %d not found"}', $productId), null, [
                "Content-Type" => "application/json"
            ]);
        }
    }
}
