<?php

namespace App\Controller;

use App\services\RecetteManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
}
