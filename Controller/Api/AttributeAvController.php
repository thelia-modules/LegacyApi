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

use LegacyApi\Service\LoopProvider;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Template\Loop\AttributeAvailability;

/**
 * Class AttributeAvController
 * @package LegacyApi\Controller\Api
 * @author Benjamin Perche <bperche@openstudio.fr>
 */
class AttributeAvController extends AbstractReadOnlyApiController
{
    public function __construct(protected LoopProvider $loopProvider)
    {
        parent::__construct(
            $loopProvider,
            "attribute av",
            AdminResources::ATTRIBUTE,
            [],
            [],
            []
        );
    }

    /**
     * @return \Thelia\Core\Template\Element\BaseLoop
     *
     * Get the entity loop instance
     */
    protected function getLoop()
    {
        return new AttributeAvailability();
    }
}
