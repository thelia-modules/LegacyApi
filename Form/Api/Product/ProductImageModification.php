<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LegacyApi\Form\Api\Product;

use DatabasesManager\Form\BaseForm;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\Image;
use Thelia\Core\Form\Type\StandardFieldsType;

class ProductImageModification extends BaseForm
{
    public function buildForm(): void
    {
        $this->formBuilder
            ->add(
                'file',
                FileType::class,
                [
                    'required' => false,
                    'constraints' => [
                        new Image([
                        ]),
                    ]
                ]
            )->add('i18n', CollectionType::class, [
                'entry_type' => StandardFieldsType::class,
                'allow_add' => true,
                'allow_delete' => true,
            ]);
    }

    public static function getName(): string
    {
        return 'thelia_api_product_image_modification';
    }
}
