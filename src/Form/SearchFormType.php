<?php

namespace App\Form;

use App\DTO\SearchDTO;
use App\Entity\Ingredient;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('searchTerm', TextType::class,['required'=>false, 'label'=>'Nom de la recette'])
            ->add('ingredientFilter', EntityType::class,[
                'class'=> Ingredient::class,
                'choice_label'=>'nom',
                'placeholder'=>'Ingredient',
                'required'=>false,
                'label'=>'Ingredient',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'=>SearchDTO::class
        ]);
    }
}
