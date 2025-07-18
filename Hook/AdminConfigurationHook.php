<?php
/*************************************************************************************/
/*      Copyright (c) Open Studio                                                    */
/*      web : https://open.studio                                                    */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE      */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

/**
 * Created by Franck Allimant, OpenStudio <fallimant@openstudio.fr>
 * Date: 08/07/2025 10:17
 */
namespace LegacyApi\Hook;

use LegacyApi\Model\ApiQuery;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;

class AdminConfigurationHook extends BaseHook
{
    public function configuration(HookRenderEvent $event):void
    {
        $event->add(
            $this->render('module-configuration.html', [
                'api_list' => ApiQuery::create()->find()->toArray()
            ])
        );
    }

    public function configurationJs(HookRenderEvent $event):void
    {
        $event->add(
            $this->render('module-configuration-js.html')
        );
    }
}
