<?php

namespace App\Controller;

use App\Entity\Recette;
use App\Form\RecetteType;
use App\services\RecetteManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
    public function entrees(): Response
    {
        $entrees = $this->manager->getEntrees();

        return $this->render('recettes/entrees.html.twig',[
            'entrees' => $entrees,
        ]);
    }

     #[Route ('/plats', name: 'app_plats')]
    public function plats(): Response
    {
        $plats = $this->manager->getPlats();

        return $this->render('recettes/plats.html.twig',[
            'plats' => $plats,
        ]);
    }

    #[Route ('/desserts', name: 'app_desserts')]
    public function desserts(): Response
    {
        $desserts = $this->manager->getDesserts();

        return $this->render('recettes/desserts.html.twig',[
            'desserts' => $desserts,
        ]);
    }

    #[Route ('/detail/{id}', name: 'app_detail')]
    public function detail(int $id): Response
    {
        $recette = $this->manager->getRecipeByName($id);
        if (!$recette)
        {
            throw $this->createNotFoundException('Recette introuvable');
        }

        return $this->render('recettes/detail.html.twig', [
            'recette' => $recette,
        ]);
    }

    #[Route('/nouvelleRecette', name: 'app_nouvelleRecette')]
    public function nouvelleRecette(Request $request): \Symfony\Component\HttpFoundation\RedirectResponse|Response
    {
        $user = $this->getUser();
        $recette = new Recette();
        $recetteForm = $this->manager->createRecipeForm($user, $recette);

        if ($this->manager->handleRecipeForm($request,$recetteForm,$recette))
        {
            $this->addFlash('success', 'Recette ajoutée');
            return $this->redirectToRoute('app_accueil');
        }

        return  $this->render('recettes/nouvelleRecette.html.twig',
            ['recetteForm'=> $recetteForm]);
    }

}
