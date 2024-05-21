<?php

namespace App\Controller;

use App\DTO\SearchDTO;
use App\Entity\Ingredient;
use App\Entity\Ratings;
use App\Entity\Recette;
use App\Entity\RecetteIngredient;
use App\Form\ModifRecetteType;
use App\Form\RatingType;
use App\Form\RecetteIngredientType;
use App\Form\RecetteType;
use App\Form\SearchFormType;
use App\services\RecetteManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RecettesController extends AbstractController
{

    private RecetteManager $manager;

    public function __construct(RecetteManager $manager)
    {
        $this->manager = $manager;
    }
    #[Route('/accueil', name: 'app_accueil')]
    public function index(): Response
    {
        $meilleuresRecettes = $this->manager->getBestRecipes();
        $nouvellesRecettes = $this->manager->getNewestRecipes();

        return $this->render('recettes/index.html.twig', [

            'meilleuresRecettes' => $meilleuresRecettes,
            'nouvellesRecettes'=>$nouvellesRecettes,
        ]);
    }

    #[Route ('/entrees', name: 'app_entrees')]
    public function entrees(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 10;
        $entrees = $this->manager->getEntrees($page,$limit);
        $entreesTotal = $entrees->count();
        $maxPages = ceil($entreesTotal/$limit);

        return $this->render('recettes/entrees.html.twig',[
            'entrees' => $entrees,
            'pageActuelle'=>$page,
            'maxPages'=>$maxPages
        ]);
    }

     #[Route ('/plats', name: 'app_plats')]
    public function plats(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 1;
        $plats = $this->manager->getPlats($page, $limit);
        $total = $plats->count();
        $maxPages = ceil($total/$limit);

        return $this->render('recettes/plats.html.twig',[
            'plats' => $plats,
            'pageActuelle'=> $page,
            'maxPages'=> $maxPages
        ]);
    }

    #[Route ('/desserts', name: 'app_desserts')]
    public function desserts(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 1;
        $desserts = $this->manager->getDesserts($page, $limit);
        $total = $desserts->count();
        $maxPages = ceil($total/$limit);

        return $this->render('recettes/desserts.html.twig',[
            'desserts' => $desserts,
            'pageActuelle'=> $page,
            'maxPages'=> $maxPages
        ]);
    }

    #[Route ('/detail/{id}', name: 'app_detail')]
    public function detail(Recette $recette,EntityManagerInterface $entityManager, Request $request): Response
    {
        $recetteData = $this->manager->getRecipeByName($recette);
        if (!$recetteData)
        {
            throw $this->createNotFoundException('Recette introuvable');
        }

        $user = $this->getUser();
        $existingRating = $this->manager->getExistingRating($user,$recette);
            $rating = new Ratings();
            $ratingForm = $this->createForm(RatingType::class,$rating);

            $response = $this->manager->handleRatings($request,$user, $recette,$ratingForm, $rating);
            if ($response){
                return $this->redirectToRoute('app_accueil');
            }

        return $this->render('recettes/detail.html.twig', [
            'recette' => $recetteData,
            'ratingForm' => $ratingForm->createView(),
            'existingRating'=>$existingRating
        ]);
    }

    #[Route('/nouvelleRecette', name: 'app_nouvelleRecette')]
    public function nouvelleRecette(Request $request): \Symfony\Component\HttpFoundation\RedirectResponse|Response
    {
        $user = $this->getUser();
        $recette = new Recette();
        $recetteForm = $this->createForm(RecetteType::class, $recette);

        if ($this->manager->handleRecipeForm($request,$recetteForm,$recette, $user))
        {
            return $this->redirectToRoute('app_ajoutIngredients', ['id'=>$recette->getId()]);
        }

        return  $this->render('recettes/nouvelleRecette.html.twig',
            ['recetteForm'=> $recetteForm]);
    }

    #[Route('/ajoutIngredient/{id}', name: 'app_ajoutIngredients')]
    public function ajoutIngredients(Recette $id, Request $request,): \Symfony\Component\HttpFoundation\RedirectResponse|Response
    {
        $recetteIngredient = new RecetteIngredient();
        $ingredientForm = $this->createForm(RecetteIngredientType::class, $recetteIngredient);
        $data = $this->manager->handleIngredientsForm($request, $ingredientForm, $id);

        if ($data){

            return $this->redirectToRoute('app_accueil');
        }

        return $this->render('recettes/ajoutIngredients.html.twig',
        ['ingredientForm'=>$ingredientForm->createView()]);
    }

    #[Route ('/recherche', name: 'app_recherche')]
    public function recherche(Request $request): Response
    {
        $searchDTO = new SearchDTO();
        $searchForm = $this->createForm(SearchFormType::class, $searchDTO);

        $recettes = $this->manager->handleSearchForm($request,$searchForm,$searchDTO);
        if ($recettes){
            $searchDTO = new SearchDTO();
            $searchForm = $this->createForm(SearchFormType::class, $searchDTO);
        }
        return $this->render('recettes/recherche.html.twig',
            ['searchForm'=>$searchForm->createView(),
                'recettes'=>$recettes
        ]);
    }

    #[Route ('/modifRecette/{id}', name: 'app_modifRecette')]
    public function modifRecette(Recette $recette, Request $request, EntityManagerInterface $entityManager): \Symfony\Component\HttpFoundation\RedirectResponse|Response
    {
        $modifForm = $this->createForm(ModifRecetteType::class, $recette);

        $response = $this->manager->handleModifRecette($request,$modifForm,$recette);
        if ($response){

            return $this->redirectToRoute('app_accueil');
        }

        return $this->render('recettes/modifRecette.html.twig',[
            'modifForm'=>$modifForm->createView(),
            'recette'=>$recette
        ]);
    }

    #[Route('/modifIngredients/{id}', name: 'app_modifIngredients',methods: ['GET','POST'])]
    public function modifIngredients(Recette $recette, Request $request,EntityManagerInterface $entityManager): Response
    {
        $ingredientForms = [];
        foreach ($recette->getIngredients() as $ingredient){
            $ingredientForm = $this->createForm(RecetteIngredientType::class, $ingredient);
            $ingredientForms[] = $ingredientForm->createView();
        }
        $response = $this->manager->handleModifIngredients($request, $recette);
        if ($response){

            return $this->redirectToRoute('app_accueil');
        }


        return $this->render('recettes/modifyIngredients.html.twig',[
            'ingredientForms'=>$ingredientForms
        ]);
    }

    #[Route('/modifIngredients/getIngredientUnit/{id}', name: 'app_getingredientunit', methods: ['GET'])]
    public function getIngredientUnit(Ingredient $ingredient): JsonResponse
    {
        return new JsonResponse(['unit'=>$ingredient->getUnite()]);
    }
}
