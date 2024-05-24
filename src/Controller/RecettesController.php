<?php

namespace App\Controller;

use App\DTO\SearchDTO;
use App\Entity\Commentaire;
use App\Entity\Ingredient;
use App\Entity\Ratings;
use App\Entity\Recette;
use App\Entity\RecetteIngredient;
use App\Form\CommentType;
use App\Form\ModifRecetteType;
use App\Form\RatingType;
use App\Form\RecetteIngredientType;
use App\Form\RecetteType;
use App\Form\SearchFormType;
use App\services\RecetteManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RecettesController extends AbstractController
{

    private RecetteManager $manager;
    private $logger;

    public function __construct(RecetteManager  $manager,
                                LoggerInterface $logger)
    {
        $this->manager = $manager;
        $this->logger = $logger;
    }
    /**
     * Affiche la page d'accueil avec les meilleures et les nouvelles recettes.
     */
    #[Route('/accueil', name: 'app_accueil', methods: ['GET'])]
    public function index(): Response
    {
        try {
            $meilleuresRecettes = $this->manager->getBestRecipes();
            $nouvellesRecettes = $this->manager->getNewestRecipes();
        } catch (Exception $e) {
            $this->logger->error("Error d'execution " . $e->getMessage());
            return $this->render('bundles/TwigBundle/Exception/error.html.twig', ['message' => 'Error en recuperant les recettes']);
        }
        return $this->render('recettes/index.html.twig', [

            'meilleuresRecettes' => $meilleuresRecettes,
            'nouvellesRecettes' => $nouvellesRecettes,
        ]);
    }
    /**
     * Affiche les recettes de type 'Entrées' avec pagination.
     */
    #[Route ('/entrees', name: 'app_entrees', methods: ['GET'])]
    public function entrees(Request $request): Response
    {
        try {
            $page = $request->query->getInt('page', 1);
            $limit = 10;
            $entrees = $this->manager->getEntrees($page, $limit);
            $entreesTotal = $entrees->count();
            $maxPages = ceil($entreesTotal / $limit);
        } catch (Exception $e) {
            $this->logger->error("Error d'execution " . $e->getMessage());
            return $this->render('bundles/TwigBundle/Exception/error.html.twig', ['message' => 'Error en recuperant les entrees']);
        }
        return $this->render('recettes/entrees.html.twig', [
            'entrees' => $entrees,
            'pageActuelle' => $page,
            'maxPages' => $maxPages
        ]);
    }
    /**
     * Affiche les recettes de type 'Plats' avec pagination.
     */
    #[Route ('/plats', name: 'app_plats', methods: ['GET'])]
    public function plats(Request $request): Response
    {
        try {
            $page = $request->query->getInt('page', 1);
            $limit = 10;
            $plats = $this->manager->getPlats($page, $limit);
            $total = $plats->count();
            $maxPages = ceil($total / $limit);
        } catch (Exception $e) {
            $this->logger->error("Error d'execution " . $e->getMessage());
            return $this->render('bundles/TwigBundle/Exception/error.html.twig', ['message' => 'Error en recuperant les plats']);
        }
        return $this->render('recettes/plats.html.twig', [
            'plats' => $plats,
            'pageActuelle' => $page,
            'maxPages' => $maxPages
        ]);
    }
    /**
     * Affiche les recettes de type 'Desserts' avec pagination.
     */
    #[Route ('/desserts', name: 'app_desserts', methods: ['GET'])]
    public function desserts(Request $request): Response
    {
        try {
            $page = $request->query->getInt('page', 1);
            $limit = 10;
            $desserts = $this->manager->getDesserts($page, $limit);
            $total = $desserts->count();
            $maxPages = ceil($total / $limit);
        } catch (Exception $e) {
            $this->logger->error("Error d'execution " . $e->getMessage());
            return $this->render('bundles/TwigBundle/Exception/error.html.twig', ['message' => 'Error en recuperant les desserts']);
        }
        return $this->render('recettes/desserts.html.twig', [
            'desserts' => $desserts,
            'pageActuelle' => $page,
            'maxPages' => $maxPages
        ]);
    }
    /**
     * Affiche les détails d'une recette spécifique avec possibilité de noter la recette.
     */
    #[Route ('/detail/{id}', name: 'app_detail', methods: ['GET', 'POST'])]
    public function detail(Recette $recette, EntityManagerInterface $entityManager, Request $request): Response
    {
        try {
            $recetteData = $this->manager->getRecipeDetails($recette);
            $comments = $recetteData->getCommentaires()->toArray();
            shuffle($comments);
            if (!$recetteData) {
                throw $this->createNotFoundException('Recette introuvable');
            }
            $user = $this->getUser();
            $existingRating = $this->manager->getExistingRating($user, $recette);
            $rating = new Ratings();
            $ratingForm = $this->createForm(RatingType::class, $rating);
            $ratingResponse = $this->manager->handleRatings($request, $user, $recette, $ratingForm, $rating);
            if ($ratingResponse) {
                return $this->redirectToRoute('app_accueil');
            }

            $existingComment = $this->manager->getExistingComment($user,$recette);
            $commentaire = new Commentaire();
            $commentForm = $this->createForm(CommentType::class,$commentaire);
            $commentResponse = $this->manager->handleComment($request, $user, $recette, $commentForm, $commentaire);
            if ($commentResponse){
                return $this->redirectToRoute('app_accueil');
            }
        } catch (Exception $e) {
            $this->logger->error("Error d'execution " . $e->getMessage());
            return $this->render('bundles/TwigBundle/Exception/error.html.twig', ['message' => 'Error en recuperant les details de la recette']);
        }
        return $this->render('recettes/detail.html.twig', [
            'recette' => $recetteData,
            'ratingForm' => $ratingForm->createView(),
            'existingRating' => $existingRating,
            'comments'=>$comments,
            'existingComment'=>$existingComment,
            'commentForm' => $commentForm->createView()
        ]);
    }
    /**
     * Affiche le formulaire pour ajouter une nouvelle recette.
     */
    #[Route('/nouvelleRecette', name: 'app_nouvelleRecette', methods: ['GET', 'POST'])]
    public function nouvelleRecette(Request $request): \Symfony\Component\HttpFoundation\RedirectResponse|Response
    {
        try {
            $user = $this->getUser();
            $recette = new Recette();
            $recetteForm = $this->createForm(RecetteType::class, $recette);
            if ($this->manager->handleRecipeForm($request, $recetteForm, $recette, $user)) {
                return $this->redirectToRoute('app_ajoutIngredients', ['id' => $recette->getId()]);
            }
        } catch (Exception $e) {
            $this->logger->error("Error d'execution " . $e->getMessage());
            return $this->render('bundles/TwigBundle/Exception/error.html.twig', ['message' => 'Error en ajoutant la recette']);
        }
        return $this->render('recettes/nouvelleRecette.html.twig',
            ['recetteForm' => $recetteForm]);
    }
    /**
     * Affiche le formulaire pour ajouter les ingrédients à une recette.
     */
    #[Route('/ajoutIngredient/{id}', name: 'app_ajoutIngredients', methods: ['GET', 'POST'])]
    public function ajoutIngredients(Recette $id, Request $request,): \Symfony\Component\HttpFoundation\RedirectResponse|Response
    {
        try {
            $recetteIngredient = new RecetteIngredient();
            $ingredientForm = $this->createForm(RecetteIngredientType::class, $recetteIngredient);
            $data = $this->manager->handleIngredientsForm($request, $ingredientForm, $id);
            if ($data) {
                return $this->redirectToRoute('app_accueil');
            }
        } catch (Exception $e) {
            $this->logger->error("Error d'execution " . $e->getMessage());
            return $this->render('bundles/TwigBundle/Exception/error.html.twig', ['message' => 'Error en ajoutant les ingredients']);
        }
        return $this->render('recettes/ajoutIngredients.html.twig',
            ['ingredientForm' => $ingredientForm->createView()]);
    }
    /**
     * Affiche les résultats de la recherche de recettes.
     */
    #[Route ('/recherche', name: 'app_recherche', methods: ['GET', 'POST'])]
    public function recherche(Request $request): Response
    {
        try {
            $searchDTO = new SearchDTO();
            $searchForm = $this->createForm(SearchFormType::class, $searchDTO);
            $recettes = new Recette();
            $recettes = $this->manager->handleSearchForm($request, $searchForm, $searchDTO);
            if ($recettes) {
                $searchDTO = new SearchDTO();
                $searchForm = $this->createForm(SearchFormType::class, $searchDTO);
            }
        } catch (Exception $e) {
            $this->logger->error("Error d'execution " . $e->getMessage());
            return $this->render('bundles/TwigBundle/Exception/error.html.twig', ['message' => 'Error en recuperant les recettes']);
        }
        return $this->render('recettes/recherche.html.twig',
            ['searchForm' => $searchForm->createView(),
                'recettes' => $recettes
            ]);
    }
    /**
     * Affiche le formulaire pour modifier une recette existante.
     */
    #[Route ('/modifRecette/{id}', name: 'app_modifRecette', methods: ['GET', 'POST'])]
    public function modifRecette(Recette $recette, Request $request, EntityManagerInterface $entityManager): \Symfony\Component\HttpFoundation\RedirectResponse|Response
    {
        try {
            $modifForm = $this->createForm(ModifRecetteType::class, $recette);

            $response = $this->manager->handleModifRecette($request, $modifForm, $recette);
            if ($response) {

                return $this->redirectToRoute('app_accueil');
            }
        } catch (Exception $e) {
            $this->logger->error("Error d'execution " . $e->getMessage());
            return $this->render('bundles/TwigBundle/Exception/error.html.twig', ['message' => 'Error en modifiant la recette']);
        }
        return $this->render('recettes/modifRecette.html.twig', [
            'modifForm' => $modifForm->createView(),
            'recette' => $recette
        ]);
    }
    /**
     * Affiche le formulaire pour modifier les ingrédients d'une recette existante.
     */
    #[Route('/modifIngredients/{id}', name: 'app_modifIngredients', methods: ['GET', 'POST'])]
    public function modifIngredients(Recette $recette, Request $request, EntityManagerInterface $entityManager): Response
    {
        try {
            $ingredientForms = [];
            $ingredients = $recette->getIngredients();
            if ($ingredients->count() === 0){
             $ingredientForm = $this->createForm(RecetteIngredientType::class);
             $ingredientForms[] = $ingredientForm->createView();
            }
                foreach ($ingredients as $ingredient) {
                    $ingredientForm = $this->createForm(RecetteIngredientType::class, $ingredient);
                    $ingredientForms[] = $ingredientForm->createView();
                }
            $response = $this->manager->handleModifIngredients($request, $recette);
            if ($response) {
                return $this->redirectToRoute('app_accueil');
            }
        } catch (Exception $e) {
            $this->logger->error("Error d'execution " . $e->getMessage());
            return $this->render('bundles/TwigBundle/Exception/error.html.twig', ['message' => 'error en modifiant les ingredients']);
        }
        return $this->render('recettes/modifyIngredients.html.twig', [
            'ingredientForms' => $ingredientForms
        ]);
    }
    /**
     * Récupère l'unité d'un ingrédient via une requête JSON.
     */
    #[Route('/modifIngredients/getIngredientUnit/{id}', name: 'app_getingredientunit', methods: ['GET'])]
    public function getIngredientUnit(Ingredient $ingredient): JsonResponse
    {
        return new JsonResponse(['unit' => $ingredient->getUnite()]);
    }
}
