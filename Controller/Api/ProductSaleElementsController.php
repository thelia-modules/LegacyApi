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

use Exception;
use LegacyApi\Form\Api\ProductSaleElements\ProductSaleElementsForm;
use LegacyApi\Service\LoopProvider;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Propel;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Response;
use Thelia\Core\Event\ProductSaleElement\ProductSaleElementCreateEvent;
use Thelia\Core\Event\ProductSaleElement\ProductSaleElementDeleteEvent;
use Thelia\Core\Event\ProductSaleElement\ProductSaleElementUpdateEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Loop\ProductSaleElements as ProductSaleElementsLoop;
use Thelia\Model\Country;
use Thelia\Model\Currency;
use Thelia\Model\Map\ProductSaleElementsTableMap;
use Thelia\Model\Product;
use Thelia\Model\ProductPrice;
use Thelia\Model\ProductPriceQuery;
use Thelia\Model\ProductQuery;
use Thelia\Model\ProductSaleElements;
use Thelia\Model\ProductSaleElementsQuery;
use Thelia\Model\TaxRuleQuery;
use Thelia\TaxEngine\Calculator;

/**
 * Class ProductSaleElementsController
 * @package LegacyApi\Controller\Api
 * @author Benjamin Perche <bperche@openstudio.fr>
 *
 * API Controller for Product sale elements management
 */
class ProductSaleElementsController extends BaseApiController
{
    public function __construct(protected LoopProvider $loopProvider)
    {
    }

    /**
     * Read actions
     */

    /**
     * List a product pses
     */
    public function listAction($productId, Request $request): ?JsonResponse
    {
        $this->checkAuth(AdminResources::PRODUCT, [], AccessManager::VIEW);

        if (null !== $response = $this->checkProduct($productId)) {
            return $response;
        }

        if ($request->query->has('id')) {
            $request->query->remove('id');
        }

        $params = array_merge(
            array(
                "limit" => 10,
                "offset" => 0,
            ),
            $request->query->all(),
            array(
                "product" => $productId,
            )
        );

        return new JsonResponse($this->getProductSaleElements($params));
    }

    /**
     * Get a pse details
     */
    public function getPseAction($pseId, Request $request): JsonResponse
    {
        $this->checkAuth(AdminResources::PRODUCT, [], AccessManager::VIEW);

        $params = array_merge(
            $request->query->all(),
            [
                'id' => $pseId,
            ]
        );

        $results = $this->getProductSaleElements($params);

        if ($results->getCount() === 0) {
            return new JsonResponse(
                sprintf(
                    "The product sale elements id '%d' doesn't exist",
                    $pseId
                ),
                404
            );
        }

        return new JsonResponse($results);
    }

    /**
     * Create action
     */

    /**
     * @return JsonResponse
     *
     * Create product sale elements
     */
    public function createAction(EventDispatcherInterface $dispatcher, Request $request): JsonResponse
    {
        $this->checkAuth(AdminResources::PRODUCT, [], AccessManager::CREATE);

        $baseForm = $this->createForm(ProductSaleElementsForm::getName(), FormType::class, [], [
            'validation_groups' => ["create", "Default"],
            'csrf_protection' => false,
        ]);

        $con = Propel::getConnection(ProductSaleElementsTableMap::DATABASE_NAME);
        $con->beginTransaction();

        $createdIds = array();

        try {
            $form = $this->submitAndValidateForm($baseForm, $request);

            $entries = $form->getData();

            foreach ($entries["pse"] as $entry) {
                $createEvent = new ProductSaleElementCreateEvent(
                    ProductQuery::create()->findPk($entry["product_id"]),
                    $entry["attribute_av"],
                    $entry["currency_id"]
                );

                $createEvent->bindForm($form);

                $dispatcher->dispatch($createEvent, TheliaEvents::PRODUCT_ADD_PRODUCT_SALE_ELEMENT);

                $this->processUpdateAction(
                    $entry,
                    $pse = $createEvent->getProductSaleElement(),
                    $createEvent->getProduct(),
                    $dispatcher
                );

                $createdIds[] = $pse->getId();
            }

            $con->commit();
        } catch (Exception $e) {
            $con->rollBack();

            return new JsonResponse(["error" => $e->getMessage()], 500);
        }

        return new JsonResponse(
            $this->getProductSaleElements(
                array_merge(
                    $request->query->all(),
                    ["id" => implode(",", $createdIds)]
                )
            ),
            201
        );
    }

    /**
     * Create product sale elements
     */
    public function updateAction(EventDispatcherInterface $dispatcher, Request $request): JsonResponse
    {
        $this->checkAuth(AdminResources::PRODUCT, [], AccessManager::UPDATE);

        $baseForm = $this->createForm(ProductSaleElementsForm::getName(), FormType::class, [], [
            'validation_groups' => ["update", "Default"],
            'csrf_protection' => false,
            "method" => "PUT",
        ]);

        $baseForm->getFormBuilder()
            ->addEventListener(
                FormEvents::PRE_SUBMIT,
                [$this, "loadProductSaleElements"],
                192
            );

        $updatedId = array();

        $con = Propel::getConnection(ProductSaleElementsTableMap::DATABASE_NAME);
        $con->beginTransaction();

        try {
            $form = $this->submitAndValidateForm($baseForm, $request);

            $entries = $form->getData();

            foreach ($entries["pse"] as $entry) {
                $this->processUpdateAction(
                    $entry,
                    $pse = ProductSaleElementsQuery::create()->findPk($entry["id"]),
                    $pse->getProduct(),
                    $dispatcher
                );

                $updatedId[] = $pse->getId();
            }

            $con->commit();
        } catch (Exception $e) {
            $con->rollBack();

            return new JsonResponse(["error" => $e->getMessage()], 500);
        }

        return new JsonResponse(
            $this->getProductSaleElements(
                array_merge(
                    $request->query->all(),
                    [
                        "id" => implode(",", $updatedId),
                        "limit" => count($updatedId),
                    ]
                )
            ),
            201
        );
    }

    /**
     * Delete Action
     */

    /**
     * Delete a pse
     */
    public function deleteAction($pseId, EventDispatcherInterface $dispatcher): Response
    {
        $this->checkAuth(AdminResources::PRODUCT, [], AccessManager::VIEW);

        $results = $this->getProductSaleElements([
            'id' => $pseId
        ]);

        if ($results->getCount() === 0) {
            return new JsonResponse(
                sprintf(
                    "The product sale elements id '%d' doesn't exist",
                    $pseId
                ),
                404
            );
        }

        $event = new ProductSaleElementDeleteEvent($pseId, Currency::getDefaultCurrency()->getId());

        try {
            $dispatcher->dispatch($event, TheliaEvents::PRODUCT_DELETE_PRODUCT_SALE_ELEMENT);
        } catch (Exception $e) {
            return new JsonResponse(array("error" => $e->getMessage()), 500);
        }

        return $this->nullResponse(204);
    }

    /**
     * Process update on product sale elements values
     * @throws PropelException
     */
    protected function processUpdateAction(
        array $data,
        ProductSaleElements $pse,
        Product $product,
        EventDispatcherInterface $dispatcher
    ): void
    {
        [$price, $salePrice] = $this->extractPrices($data);

        $event = new ProductSaleElementUpdateEvent($product, $pse->getId());

        $event
            ->setWeight($data["weight"])
            ->setTaxRuleId($data["tax_rule_id"])
            ->setEanCode($data["ean_code"])
            ->setOnsale($data["onsale"])
            ->setReference($data["reference"])
            ->setIsdefault($data["isdefault"])
            ->setIsnew($data["isnew"])
            ->setCurrencyId($data["currency_id"])
            ->setPrice($price)
            ->setSalePrice($salePrice)
            ->setQuantity($data["quantity"])
            ->setFromDefaultCurrency($data["use_exchange_rate"])
        ;

        $dispatcher->dispatch($event, TheliaEvents::PRODUCT_UPDATE_PRODUCT_SALE_ELEMENT);
    }

    /**
     * Return the untaxed prices to store
     * @throws PropelException
     */
    protected function extractPrices(array $data): array
    {
        $calculator = new Calculator();

        $calculator->loadTaxRuleWithoutProduct(
            TaxRuleQuery::create()->findPk($data["tax_rule_id"]),
            Country::getShopLocation()
        );

        $price = null === $data["price_with_tax"] ?
            $data["price"] :
            $calculator->getUntaxedPrice($data["price_with_tax"])
        ;

        $salePrice = null === $data["sale_price_with_tax"] ?
            $data["sale_price"] :
            $calculator->getUntaxedPrice($data["sale_price_with_tax"])
        ;

        return [$price, $salePrice];
    }

    /**
     * @throws PropelException
     */
    protected function retrievePrices(ProductSaleElements $pse): array
    {
        $query = ProductPriceQuery::create()
            ->useCurrencyQuery()
                ->orderByByDefault()
            ->endUse()
        ;

        $prices = $pse->getProductPrices($query);

        if ($prices->count() === 0) {
            return array(null, null, null, null);
        }

        /** @var ProductPrice $currentPrices */
        $currentPrices = $prices->get(0);

        return [
            $currentPrices->getPrice(),
            $currentPrices->getPromoPrice(),
            $currentPrices->getCurrencyId(),
            $currentPrices->getFromDefaultCurrency()
        ];
    }

    /**
     * @param FormEvent $event
     *
     * Loads initial pse data into a form.
     * It is used in for a form event on pse update
     * @throws PropelException
     */
    public function loadProductSaleElements(FormEvent $event): void
    {
        $productSaleElementIds = array();
        $data = array();

        foreach ($event->getData()["pse"] as $entry) {
            $productSaleElementIds[$entry["id"]] = $entry;
        }

        $productSaleElements = ProductSaleElementsQuery::create()
            ->findPks(array_keys($productSaleElementIds))
        ;

        /** @var ProductSaleElements $productSaleElement */
        foreach ($productSaleElements as $productSaleElement) {
            $product = $productSaleElement->getProduct();

            [$price, $salePrice, $currencyId, $fromDefaultCurrency] = $this->retrievePrices($productSaleElement);

            $data["pse"][$productSaleElement->getId()] = array_merge(
                [
                    "id" => $productSaleElement->getId(),
                    "reference" => $productSaleElement->getRef(),
                    "tax_rule_id" => $product->getTaxRuleId(),
                    "ean_code" => $productSaleElement->getEanCode(),
                    "onsale" => $productSaleElement->getPromo(),
                    "isdefault" => $productSaleElement->getIsDefault(),
                    "isnew" => $productSaleElement->getNewness(),
                    "quantity" => $productSaleElement->getQuantity(),
                    "weight" => $productSaleElement->getWeight(),
                    "price" => $price,
                    "sale_price" => $salePrice,
                    "currency_id" => $currencyId,
                    "use_exchange_rate" => $fromDefaultCurrency
                ],
                $productSaleElementIds[$productSaleElement->getId()]
            );
        }

        $event->setData($data);
    }

    /**
     * Checks if a productId exists
     */
    protected function checkProduct($productId): ?JsonResponse
    {
        if (null === ProductQuery::create()->findPk($productId)) {
            return new JsonResponse(
                [
                    "error" => sprintf(
                        "The product id '%d' doesn't exist",
                        $productId
                    )
                ],
                404
            );
        }

        return null;
    }

    /**
     * Return loop results for a product sale element
     */
    protected function getProductSaleElements($params): LoopResult
    {
        return $this->loopProvider->getLoopResults(new ProductSaleElementsLoop(), $params);
    }
}
