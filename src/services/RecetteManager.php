<?php

namespace App\services;

use App\DTO\SearchDTO;
use App\Entity\Commentaire;
use App\Entity\Ingredient;
use App\Entity\Ratings;
use App\Entity\Recette;
use App\Entity\RecetteIngredient;
use App\Entity\Utilisateur;
use App\Form\RecetteType;
use App\Repository\RecetteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use http\Client\Curl\User;
use PHPUnit\Exception;
use Psr\Log\LoggerInterface;
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

    private $logger;

    public function __construct(RecetteRepository $recetteRepository,
                                EntityManagerInterface $entityManager,
                                FormFactoryInterface $formFactory,
                                LoggerInterface $logger)
    {
        $this->recetteRepository = $recetteRepository;
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
        $this->logger = $logger;
    }

    /**
     * Récupère les meilleures recettes.
     *
     * @return array|null Les meilleures recettes ou null en cas d'erreur.
     */
    public function getBestRecipes(): ?array
    {
        try {
        return $this->recetteRepository->findBestRecipes();
        }
        catch (Exception $e){
        $this->logger->error("Error recuperant les meilleurs recettes: ". $e->getMessage());
        return null;
        }
    }

    /**
     * Récupère les nouvelles recettes.
     *
     * @return array|null Les nouvelles recettes ou null en cas d'erreur.
     */
    public function getNewestRecipes(): ?array
    {
        try {
        return $this->recetteRepository->findNewestRecipes();
        }
        catch (Exception $e){
            $this->logger->error("Error recuperant les nouvelles recettes: ". $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les entrées paginées.
     *
     * @param int $page Le numéro de la page.
     * @param int $limit Le nombre maximal d'entrées par page.
     *
     * @return Paginator|null Les entrées paginées ou null en cas d'erreur.
     */
    public function getEntrees(int $page,
                               int $limit): ?Paginator
    {
        try {
        return $this->recetteRepository->findEntrees($page, $limit);
        }catch (Exception $e){
            $this->logger->error("Error recuperant les entrees: ". $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les plats paginées.
     *
     * @param int $page Le numéro de la page.
     * @param int $limit Le nombre maximal d'entrées par page.
     *
     * @return Paginator|null Les entrées paginées ou null en cas d'erreur.
     */
    public function getPlats(int $page,
                             int $limit): ?Paginator
    {
        try {
        return $this->recetteRepository->findPlats($page, $limit);
        }catch (Exception $e){
            $this->logger->error("Error recuperant les plats: ". $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les desserts paginées.
     *
     * @param int $page Le numéro de la page.
     * @param int $limit Le nombre maximal d'entrées par page.
     *
     * @return Paginator|null Les entrées paginées ou null en cas d'erreur.
     */
    public function getDesserts(int $page,
                                int $limit): ?Paginator
    {
        try {
        return $this->recetteRepository->findDesserts($page, $limit);
        }catch (Exception $e){
            $this->logger->error("Error recuperant les desserts: ". $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les details d'une recette
     *
     * @param Recette $recette L'objet Recette.
     *
     * @return Recette|null La recette correspondante ou null en cas d'erreur.
     */
    public function getRecipeDetails(Recette $recette): ?Recette
    {
        try {
        return $this->recetteRepository->findRecipeById($recette->getId());
        }catch (Exception $e){
            $this->logger->error("Error recuperant les recettes: ". $e->getMessage());
            return null;
        }
    }

    /**
     * Gère la soumission d'un formulaire de recette.
     *
     * @param Request         $request      La requête HTTP.
     * @param FormInterface   $recetteForm  Le formulaire de recette.
     * @param Recette $recette      L'objet Recette.
     * @param UserInterface   $user         L'utilisateur connecté.
     *
     * @return Recette|null La recette créée ou null en cas d'erreur.
     */
    public function handleRecipeForm(Request       $request,
                                     FormInterface $recetteForm,
                                     Recette       $recette,
                                     UserInterface $user): ?Recette
    {
        try {
        $recetteForm->handleRequest($request);
        if ($recetteForm->isSubmitted() && $recetteForm->isValid())
        {
            $recette->setAuteur($user);
            $recette->setDateCreated(new \DateTime());
            $this->entityManager->persist($recette);
            $this->entityManager->flush();

            return $recette;
        }
        }catch (Exception $e){
            $this->logger->error("Error ajoutant la recette: ". $e->getMessage());
        }
        return null;
    }

    /**
     * Gère la soumission d'un formulaire de recherche de recette.
     *
     * @param Request       $request     La requête HTTP.
     * @param FormInterface $searchForm  Le formulaire de recherche.
     * @param SearchDTO     $searchDTO   L'objet DTO de recherche.
     *
     * @return array|null Les recettes correspondantes à la recherche ou null en cas d'erreur.
     */
    public function handleSearchForm(Request $request,
                                     FormInterface $searchForm,
                                     SearchDTO $searchDTO): ?array
    {
        try {
        $searchForm->handleRequest($request);
        if ($searchForm->isSubmitted() && $searchForm->isValid()){
            return  $this->recetteRepository->findRecipesBySearch($searchDTO->getSearchTerm(), $searchDTO->getIngredientFilter());
        }
        }catch (Exception $e){
            $this->logger->error("Error cherchant la recette: ". $e->getMessage());
        }
        return null;
    }

    /**
     * Gère la soumission d'un formulaire d'ajout d'ingrédients à une recette.
     *
     * @param Request       $request           La requête HTTP.
     * @param FormInterface $ingredientsForm   Le formulaire d'ajout d'ingrédients.
     * @param Recette       $recette           L'objet Recette.
     *
     * @return array|null Les données des ingrédients ajoutés ou null en cas d'erreur.
     */
    public function handleIngredientsForm(Request $request,
                                          FormInterface $ingredientsForm,
                                          Recette $recette): ?array
    {
        try {
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
        }catch (Exception $e){
            $this->logger->error("Error ajoutant les ingredients: ". $e->getMessage());
        }
        return null;
    }

    /**
     * Gère la modification d'une recette.
     *
     * @param Request       $request    La requête HTTP.
     * @param FormInterface $modifForm  Le formulaire de modification.
     * @param Recette       $recette    L'objet Recette à modifier.
     *
     * @return bool true si la modification est effectuée avec succès, sinon false.
     */
    public function handleModifRecette(Request $request,
                                       FormInterface $modifForm,
                                       Recette $recette): bool
    {
        try {
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
        }catch (Exception $e){
            $this->logger->error("Error en modifiant la recette: ". $e->getMessage());
        }
        return false;
    }

    /**
     * Gère la modification des ingrédients d'une recette.
     *
     * @param Request $request La requête HTTP.
     * @param Recette $recette L'objet Recette à modifier.
     *
     * @return bool true si la modification est effectuée avec succès, sinon false.
     */
    public function handleModifIngredients(Request $request,
                                           Recette $recette): bool
    {
        try {
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
        }catch (Exception $e){
            $this->logger->error("Error en modifiant les ingredients: ". $e->getMessage());
        }
        return false;
    }
    /**
     * Récupère le rating existant pour une recette donnée par un utilisateur.
     *
     * @param UserInterface $user    L'utilisateur.
     * @param Recette       $recette La recette.
     *
     * @return Ratings|null Le rating existant ou null en cas d'erreur.
     */
    public function getExistingRating(UserInterface $user,
                                      Recette $recette): ?Ratings
    {
        try {
       return $this->entityManager->getRepository(Ratings::class)->findOneBy([
            'User'=> $user,
            'Recette'=>$recette
        ]);
        }catch (Exception $e){
            $this->logger->error("Error recuperant le Rating existant: ". $e->getMessage());
            return null;
        }
    }
    /**
     * Gère la soumission du formulaire de rating d'une recette.
     *
     * @param Request       $request     La requête HTTP.
     * @param Utilisateur   $user        L'utilisateur.
     * @param Recette       $recette     La recette.
     * @param FormInterface $ratingForm  Le formulaire de rating.
     * @param Ratings       $rating      L'objet Rating.
     *
     * @return bool true si le rating est ajouté avec succès, sinon false.
     */
    public function handleRatings(Request $request,
                                  Utilisateur $user,
                                  Recette $recette,
                                  FormInterface $ratingForm,
                                  Ratings $rating ): bool
    {
        try {
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
        }catch (Exception $e){
            $this->logger->error("Error ajoutant la note: ". $e->getMessage());
        }
        return false;
    }
    /**
     * Récupère le commentaire existant pour une recette donnée par un utilisateur.
     *
     * @param UserInterface $user    L'utilisateur.
     * @param Recette       $recette La recette.
     *
     * @return Commentaire|null Le commentaire existant ou null en cas d'erreur.
     */
    public function getExistingComment(UserInterface $user,
                                      Recette $recette): ?Commentaire
    {
        try {
            return $this->entityManager->getRepository(Commentaire::class)->findOneBy([
                'Auteur'=> $user,
                'recette'=>$recette
            ]);
        }catch (Exception $e){
            $this->logger->error("Error recuperant le Rating existant: ". $e->getMessage());
            return null;
        }
    }
    /**
     * Gère la soumission du formulaire de commentaires d'une recette.
     *
     * @param Request       $request     La requête HTTP.
     * @param Utilisateur   $user        L'utilisateur.
     * @param Recette       $recette     La recette.
     * @param FormInterface $commentaireForm  Le formulaire de rating.
     * @param Commentaire      $commentaire      L'objet Commentaire.
     *
     * @return bool true si le rating est ajouté avec succès, sinon false.
     */
    public function handleComment(Request $request,
                                  Utilisateur $user,
                                  Recette $recette,
                                  FormInterface $commentaireForm,
                                  Commentaire $commentaire ): bool
    {
        try {
            $commentaireForm->handleRequest($request);
            if ($commentaireForm->isSubmitted() && $commentaireForm->isValid()){
                $commentaire->setRecette($recette);
                $commentaire->setAuteur($user);
                $this->entityManager->persist($commentaire);
                $this->entityManager->flush();
                return true;
            }
        }catch (Exception $e){
            $this->logger->error("Error ajoutant le commentaire: ". $e->getMessage());
        }
        return false;
    }
}