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
 * Date: 29/06/2021 12:07
 */
namespace LegacyApi\Form;

use LegacyApi\LegacyApi;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;

class ConfigurationForm extends BaseForm
{
    protected function buildForm(): void
    {
        $this->formBuilder
            ->add(
                'do_not_check_signature',
                CheckboxType::class,
                [
                    'required' => false,
                    'data' => (bool) LegacyApi::getConfigValue('do_not_check_signature', false),
                    'label'         => Translator::getInstance()->trans("Do not check requests signature (development only)", [], LegacyApi::DOMAIN_NAME),
                    'label_attr' => [
                        'help'   => Translator::getInstance()->trans(
                            "If this box is checked, requests signature will NOT be checked. This is useful for"
                                ."testing purposes, but creates a potential security breach.",
                            [],
                            LegacyApi::DOMAIN_NAME),
                    ],
                ]
            );
    }

    public static function getName(): string
    {
        return "legacy_api_form_configurationform";
    }
}
