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

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;
use Thelia\Model\ProfileQuery;

/**
 * Class ApiCreateForm
 * @package LegacyApi\Form\Api
 * @author Manuel Raynaud <manu@raynaud.io>
 */
class ApiCreateForm extends BaseForm
{
    protected function buildForm(): void
    {
        $this->formBuilder
            ->add(
                'label',
                TextType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
                'label' => Translator::getInstance()->trans('label'),
                'label_attr' => ['for' => 'api_label']
            ])
            ->add(
                'profile',
                ChoiceType::class,
                array(
                    "choices" => ProfileQuery::getProfileList(),
                    "constraints" => array(
                        new NotBlank(),
                    ),
                    "label" => Translator::getInstance()->trans('Profile'),
                    "label_attr" => array(
                        "for" => "profile",
                    ),
                )
            )
        ;
    }

    /**
     * @return string the name of you form. This name must be unique
     */
    public static function getName(): string
    {
        return 'thelia_api_create';
    }
}
