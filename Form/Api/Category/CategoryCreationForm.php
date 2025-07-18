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

namespace LegacyApi\Form\Api\Category;

use Thelia\Form\CategoryCreationForm as BaseCategoryCreationForm;

/**
 * Class CategoryCreationForm
 * @package LegacyApi\Form\Api\Category
 * @author manuel raynaud <manu@raynaud.io>
 */
class CategoryCreationForm extends BaseCategoryCreationForm
{
    public static function getName()
    {
        return 'thelia_api_category_create';
    }
}
