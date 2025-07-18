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

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Form\CustomerCreateForm as BaseCustomerCreateForm;

/**
 * Class CustomerCreateForm
 * @package LegacyApi\Form\Api\Customer
 * @author manuel raynaud <manu@raynaud.io>
 */
class CustomerCreateForm extends BaseCustomerCreateForm
{
    public function buildForm(): void
    {
        parent::buildForm();

        $this->formBuilder
            ->remove('email_confirm')
            ->remove('password_confirm')
            ->remove('agreed')
            ->add('lang_id', IntegerType::class, [
                'constraints' => [
                    new NotBlank(),
                ]
            ]);
    }

    public static function getName()
    {
        return 'thelia_api_customer_create';
    }
}
