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

namespace LegacyApi\Form\Api\ProductSaleElements;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Thelia\Core\Form\Type\ProductSaleElementsType;
use Thelia\Form\BaseForm;

/**
 * Class ProductSaleElementsForm
 * @author Benjamin Perche <bperche@openstudio.fr>
 */
class ProductSaleElementsForm extends BaseForm
{
    protected function buildForm()
    {
        $this->formBuilder
            ->add(
                "pse",
                CollectionType::class, [
                    "entry_type" => ProductSaleElementsType::class,
                    "allow_add" => true,
                    "required" => true,
                ]
            )
        ;
    }

    /**
     * @return string the name of you form. This name must be unique
     */
    public static function getName()
    {
        return 'thelia_api_product_sale_elements_form';
    }
}
