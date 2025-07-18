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

namespace LegacyApi\Form\Api;

use Thelia\Form\EmptyForm;

/**
 * Class ApiEmptyForm
 * @package LegacyApi\Form\Api
 * @author Benjamin Perche <bperche@openstudio.fr>
 */
class ApiEmptyForm extends EmptyForm
{
    public static function getName()
    {
        return 'thelia_api_empty';
    }
}
