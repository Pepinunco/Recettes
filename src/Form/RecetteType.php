<?php

namespace App\Form;

use App\Entity\Categorie;
use App\Entity\Ingredient;
use App\Entity\Recette;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecetteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Nom')
            ->add('TempsPreparation', TimeType::class, [
                'input' => 'datetime',
                'widget' => 'choice',
                'hours' => range(0, 23),
                'minutes' => range(0, 59),
                'with_seconds' => false,
            ])
            ->add('TempsCuisson', TimeType::class, [
                'input' => 'datetime',
                'widget' => 'choice',
                'hours' => range(0, 23),
                'minutes' => range(0, 59),
                'with_seconds' => false,
            ])
            ->add('Portions')
            ->add('Instructions')
            ->add('Categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nom',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recette::class,
        ]);
    }
}
