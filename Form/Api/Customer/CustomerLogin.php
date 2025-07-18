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

use Thelia\Form\CustomerLogin as BaseCustomerLogin;

/**
 * Customer login form for the API.
 *
 * Class CustomerLogin
 * @package LegacyApi\Form\Api\Customer
 * @author Baptiste Cabarrou <bcabarrou@openstudio.fr>
 */
class CustomerLogin extends BaseCustomerLogin
{
    public function buildForm(): void
    {
        parent::buildForm();

        $this->formBuilder->remove('remember_me');
        // "I am an existing customer"
        $this->formBuilder->get('account')->setEmptyData(1)->setDataLocked(true);
    }

    public static function getName()
    {
        return 'thelia_api_customer_login';
    }
}
