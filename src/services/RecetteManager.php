<?php

namespace App\services;

use App\Entity\Recette;
use App\Entity\Utilisateur;
use App\Form\RecetteType;
use App\Repository\RecetteRepository;
use Doctrine\ORM\EntityManagerInterface;
use http\Client\Curl\User;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\Date;

class RecetteManager
{

    private $recetteRepository;
    private $entityManager;
    private $formFactory;

    public function __construct(RecetteRepository $recetteRepository,
                                EntityManagerInterface $entityManager,
                                FormFactoryInterface $formFactory)
    {
        $this->recetteRepository = $recetteRepository;
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
    }

    public function getBestRecipes()
    {
        return $this->recetteRepository->findBestRecipes();
    }

    public function getNewestRecipes()
    {
        return $this->recetteRepository->findNewestRecipes();
    }

    public function getEntrees()
    {
        return $this->recetteRepository->findEntrees();
    }

    public function getPlats()
    {
        return $this->recetteRepository->findPlats();
    }

    public function getDesserts()
    {
        return $this->recetteRepository->findDesserts();
    }

    public function getRecipeByName(int $id)
    {
        return $this->recetteRepository->findRecipeById($id);
    }

    public function createRecipeForm(UserInterface $user,
                                     Recette $recette): FormInterface
    {

        $recette->setAuteur($user);
        $recette->setDateCreated(new \DateTime());

        return $this->formFactory->create(RecetteType::class, $recette);

    }

    public function handleRecipeForm(Request $request,
                                     FormInterface $recetteForm,
                                     $recette)
    {
        $recetteForm->handleRequest($request);

        if ($recetteForm->isSubmitted() && $recetteForm->isValid())
        {
            $this->entityManager->persist($recette);
            $this->entityManager->flush();

            return $recette;
        }
        return null;
    }
}