<?php

namespace Hengebytes\ResponseFilterBundle\Form;

use Hengebytes\ResponseFilterBundle\Entity\ResponseFilterRule;
use Hengebytes\ResponseFilterBundle\Enum\ResponseFilterRuleTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResponseFilterRuleType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('service', TextType::class, [
                'required' => true,
                'label' => 'Service',
            ])
            ->add('subService', TextType::class, [
                'required' => false,
                'label' => 'Sub Service',
            ])
            ->add('action', TextType::class, [
                'required' => true,
                'label' => 'Action',
            ])
            ->add('type', EnumType::class, [
                'required' => true,
                'class' => ResponseFilterRuleTypeEnum::class,
                'label' => 'Type',
            ])
            ->add('condition', TextType::class, [
                'required' => true,
                'label' => 'Condition',
                'empty_data' => '',
            ])
            ->add('field', TextType::class, [
                'required' => false,
                'label' => 'Field apply to',
            ])
            ->add('value', TextType::class, [
                'required' => false,
                'label' => 'Value',
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => ResponseFilterRule::class,
            ]
        );
    }
}
