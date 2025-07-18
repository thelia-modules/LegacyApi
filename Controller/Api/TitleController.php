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
use Thelia\Core\Template\Loop\Title;

/**
 * Class TitleController
 * @package LegacyApi\Controller\Api
 * @author Benjamin Perche <bperche@openstudio.fr>
 * @author Manuel Raynaud <manu@raynaud.io>
 */
class TitleController extends AbstractReadOnlyApiController
{
    public function __construct(protected LoopProvider $loopProvider)
    {
        parent::__construct(
            $this->loopProvider,
            "customer title",
            AdminResources::TITLE
        );
    }

    protected function getLoop()
    {
        return new Title();
    }
}
