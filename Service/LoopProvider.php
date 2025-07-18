<?php

/*      Copyright (c) OpenStudio */
/*      email : dev@thelia.net */
/*      web : http://www.thelia.net */

/*      For the full copyright and license information, please view the LICENSE.txt */
/*      file that was distributed with this source code. */

/**
 * Created by Franck Allimant, OpenStudio <fallimant@openstudio.fr>
 * Projet: ptc25
 * Date: 08/07/2025
 */

namespace LegacyApi\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Thelia\Core\Security\SecurityContext;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;

class LoopProvider
{
    public function __construct(
        protected ContainerInterface $container,
        protected RequestStack $requestStack,
        protected EventDispatcherInterface $eventDispatcher,
        protected SecurityContext $securityContext,
        protected TranslatorInterface $translator,
        protected bool $kernelDebug,
        protected array $theliaParserLoops,
        protected string $kernelEnvironment
    ) {
    }

    public function getLoopResults(BaseLoop $loop, $loopParams): LoopResult
    {
        $loop->init(
            $this->container,
            $this->requestStack,
            $this->eventDispatcher,
            $this->securityContext,
            $this->translator,
            $this->theliaParserLoops,
            $this->kernelEnvironment
        );

        $loop->initializeArgs($loopParams);

        return $loop->exec($pagination);
    }
}
