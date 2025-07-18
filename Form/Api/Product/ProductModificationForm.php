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

namespace LegacyApi\Form\Api\Product;

use Thelia\Form\ProductModificationForm as BaseProductModificationForm;

/**
 * Class ProductModificationForm
 * @package LegacyApi\Form\Api\Product
 * @author manuel raynaud <manu@raynaud.io>
 */
class ProductModificationForm extends BaseProductModificationForm
{
    public static function getName()
    {
        return 'thelia_api_product_update';
    }
}
