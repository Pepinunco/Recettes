<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EditFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('profilePicture', FileType::class, ['label' => 'Photo de profil', 'mapped' =>false, 'required' => false,
                'constraints'=>[new File(['maxSize'=>'1024k', 'mimeTypes'=>['image/jpeg', 'image/png', 'image/gif', 'image/jpg'], 'mimeTypesMessage'=>'jpeg,png,gif ou jpg acceptés'])]])
            ->add('email', EmailType::class, ['attr' => ['class' => 'custom-class']])
            ->add('pseudo', TextType::class, ['required' => false, 'attr' => ['class' => 'custom-class']])
            ->add('Enregistrer', SubmitType::class,['label'=>'Enregistrer'])
            ->add('Supprimer', SubmitType::class,['label'=>'Supprimer'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }


}
