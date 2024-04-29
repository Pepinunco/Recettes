<?php

namespace App\services;

use App\Repository\RecetteRepository;

class RecetteManager
{

    private RecetteRepository $recetteRepository;

    public function __construct(RecetteRepository $recetteRepository)
    {
        $this->recetteRepository = $recetteRepository;
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
}