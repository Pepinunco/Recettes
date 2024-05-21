<?php

namespace App\services;

use App\DTO\SearchDTO;
use App\Entity\Ingredient;
use App\Entity\Ratings;
use App\Entity\Recette;
use App\Entity\RecetteIngredient;
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

    public function getEntrees(int $page, int $limit): \Doctrine\ORM\Tools\Pagination\Paginator
    {
        return $this->recetteRepository->findEntrees($page, $limit);
    }

    public function getPlats(int $page, int $limit): \Doctrine\ORM\Tools\Pagination\Paginator
    {
        return $this->recetteRepository->findPlats($page, $limit);
    }

    public function getDesserts(int $page, int $limit): \Doctrine\ORM\Tools\Pagination\Paginator
    {
        return $this->recetteRepository->findDesserts($page, $limit);
    }

    public function getRecipeByName(Recette $recette)
    {
        return $this->recetteRepository->findRecipeById($recette->getId());
    }

    public function handleRecipeForm(Request $request,
                                     FormInterface $recetteForm,
                                     $recette,
                                     UserInterface $user,)
    {
        $recetteForm->handleRequest($request);

        if ($recetteForm->isSubmitted() && $recetteForm->isValid())
        {
            $recette->setAuteur($user);
            $recette->setDateCreated(new \DateTime());
            $this->entityManager->persist($recette);
            $this->entityManager->flush();

            return $recette;
        }
        return null;
    }

    public function handleSearchForm(Request $request, FormInterface $searchForm, SearchDTO $searchDTO)
    {
        $searchForm->handleRequest($request);

        if ($searchForm->isSubmitted() && $searchForm->isValid()){
            return  $this->recetteRepository->findRecipesBySearch($searchDTO->getSearchTerm(), $searchDTO->getIngredientFilter());
        }
        return null;
    }

    public function handleIngredientsForm(Request $request, FormInterface $ingredientsForm, Recette $recette)
    {
        $ingredientsForm->handleRequest($request);

        if ($ingredientsForm->isSubmitted() && $ingredientsForm->isValid()){
            $formDataJSON = $request->request->get('ingredientsData');
            $formData = json_decode($formDataJSON, true);

            foreach ($formData as $data){
                $ingredient = $this->entityManager->getRepository(Ingredient::class)->find($data['Ingredient']);

                if ($ingredient){
                    $recetteIngredient = new RecetteIngredient();
                    $recetteIngredient->setRecette($recette);
                    $recetteIngredient->setIngredient($ingredient);
                    $recetteIngredient->setQuantite($data['Quantite']);
                    $this->entityManager->persist($recetteIngredient);
                }
            }
            $this->entityManager->flush();
            return $formData;
        }
        return null;
    }

    public function handleModifRecette(Request $request, FormInterface $modifForm, Recette $recette): bool
    {
        $modifForm->handleRequest($request);

        if ($modifForm->isSubmitted() && $modifForm->isValid()) {
            $clickedButton = $modifForm->getClickedButton();
            if ($clickedButton && 'Enregistrer' === $clickedButton->getName()) {
                $this->entityManager->persist($recette);
                $this->entityManager->flush();
                return true;
            }
            if ($clickedButton && 'Supprimer' === $clickedButton->getName()) {
                $this->entityManager->remove($recette);
                $this->entityManager->flush();
                return true;
            }
        }
        return false;
    }

    public function handleModifIngredients(Request $request, Recette $recette): bool
    {
        if ($request->isMethod('POST')) {
            $ingredientsData = json_decode($request->request->get('ingredientsData', true), true);
            $existingIngredients = [];
            foreach ($recette->getIngredients() as $ingredient) {
                $existingIngredients[$ingredient->getId()] = $ingredient;
            }

            foreach ($ingredientsData as $ingredientData) {
                $ingredientName = $ingredientData['Ingredient'];
                $ingredientQuantity = $ingredientData['Quantite'];

                $ingredientEntity = $this->entityManager->getRepository(Ingredient::class)->find($ingredientName);
                $ingredientId = $ingredientEntity->getId();

                if ($ingredientId && isset($existingIngredients[$ingredientId])) {
                    $ingredient = $existingIngredients[$ingredientId];
                    unset($existingIngredients[$ingredientId]);
                } else {
                    $ingredient = new RecetteIngredient();
                    $ingredient->setRecette($recette);
                }

                $ingredient->setIngredient($ingredientEntity);
                $ingredient->setQuantite($ingredientQuantity);
                $this->entityManager->persist($ingredient);
            }

            foreach ($existingIngredients as $ingredient) {
                $this->entityManager->remove($ingredient);
            }

            $this->entityManager->flush();
            return true;
        }
        return false;
    }

    public function getExistingRating(UserInterface $user, Recette $recette)
    {
       return $this->entityManager->getRepository(Ratings::class)->findOneBy([
            'User'=> $user,
            'Recette'=>$recette
        ]);
    }

    public function handleRatings(Request $request,Utilisateur $user, Recette $recette, FormInterface $ratingForm, Ratings $rating )
    {
        $ratingForm->handleRequest($request);
        if ($ratingForm->isSubmitted() && $ratingForm->isValid()){
            $rating->setRecette($recette);
            $rating->setUser($user);

            $this->entityManager->persist($rating);
            $this->entityManager->flush();
            $recette->updateNote();
            $this->entityManager->persist($recette);
            $this->entityManager->flush();
            return true;
        }
        return false;
    }
}