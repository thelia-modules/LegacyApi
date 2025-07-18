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

namespace LegacyApi\Form\Api\Customer;

use Thelia\Core\Form\Type\Field\CustomerIdType;
use Thelia\Core\Form\Type\Field\LangIdType;
use Thelia\Form\CustomerUpdateForm as BaseCustomerUpdateForm;

/**
 * Class CustomerUpdateForm
 * @package LegacyApi\Form\Api\Customer
 * @author Manuel Raynaud <manu@raynaud.io>
 */
class CustomerUpdateForm extends BaseCustomerUpdateForm
{
    public function buildForm(): void
    {
        parent::buildForm();

        $this->formBuilder
            ->add('lang_id', LangIdType::class)
            ->add('id', CustomerIdType::class)
        ;
    }

    public static function getName()
    {
        return 'thelia_api_customer_update';
    }
}
