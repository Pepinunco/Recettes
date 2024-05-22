<?php

namespace App\Repository;

use App\Entity\Recette;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use http\Env\Request;

/**
 * @extends ServiceEntityRepository<Recette>
 *
 * @method Recette|null find($id, $lockMode = null, $lockVersion = null)
 * @method Recette|null findOneBy(array $criteria, array $orderBy = null)
 * @method Recette[]    findAll()
 * @method Recette[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecetteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recette::class);
    }
    /**
     * Trouve les recettes les plus récentes.
     *
     * @return Recette[] Les recettes les plus récentes
     */
    public function findNewestRecipes(): array
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->addOrderBy('r.dateCreated','DESC');
        $query = $queryBuilder->getQuery();
        $query->setMaxResults(4);
        return $query->getResult();
    }
    /**
     * Trouve les recettes les mieux notées.
     *
     * @return Recette[] Les recettes les mieux notées
     */
    public function findBestRecipes(): array
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->addOrderBy('r.Note','DESC');
        $query = $queryBuilder->getQuery();
        $query->setMaxResults(4);
        return $query->getResult();
    }
    /**
     * Trouve les entrées avec pagination.
     *
     * @param int $page Le numéro de page
     * @param int $limit Le nombre limite de résultats par page
     * @return Paginator Les entrées avec pagination
     */
    public function findEntrees(int $page,
                                int $limit): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->andWhere('r.Categorie = 1');
        $queryBuilder->addOrderBy('r.Note', 'DESC');
        $query = $queryBuilder->getQuery();
        $query->setFirstResult(($page-1) * $limit);
        $query->setMaxResults($limit);
        return new Paginator($query, true);
    }
    /**
     * Trouve les plats avec pagination.
     *
     * @param int $page Le numéro de page
     * @param int $limit Le nombre limite de résultats par page
     * @return Paginator Les entrées avec pagination
     */
    public function findPlats(int $page,
                              int $limit): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->andWhere('r.Categorie = 2');
        $queryBuilder->addOrderBy('r.Note', 'DESC');
        $query = $queryBuilder->getQuery();
        $query->setFirstResult(($page-1) * $limit);
        $query->setMaxResults($limit);
        return new Paginator($query, true);
    }
    /**
     * Trouve les desserts avec pagination.
     *
     * @param int $page Le numéro de page
     * @param int $limit Le nombre limite de résultats par page
     * @return Paginator Les entrées avec pagination
     */
    public function findDesserts(int $page,
                                 int $limit): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->andWhere('r.Categorie = 3');
        $queryBuilder->addOrderBy('r.Note', 'DESC');
        $query = $queryBuilder->getQuery();
        $query->setFirstResult(($page-1) * $limit);
        $query->setMaxResults($limit);
        return new Paginator($query, true);
    }
    /**
     * Trouve une recette par son ID.
     *
     * @param int $id L'ID de la recette
     * @return mixed La recette
     */
    public function findRecipeById(int $id): mixed
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->select('r', 'ri', 'c', 'a', 'ing');
        $queryBuilder->leftJoin('r.ingredients', 'ri');
        $queryBuilder->leftJoin('ri.Ingredient','ing');
        $queryBuilder->leftJoin('r.Commentaires', 'c');
        $queryBuilder->leftJoin('r.Auteur', 'a');
        $queryBuilder->where('r.id = :id');
        $queryBuilder->setParameter('id',$id);
        $query = $queryBuilder->getQuery();
        return $query->getOneOrNullResult();
    }
    /**
     * Trouve les recettes par terme de recherche et/ou filtre d'ingrédient.
     *
     * @param string|null $searchTerm Le terme de recherche
     * @param int|null $ingredientFilter L'ID du filtre d'ingrédient
     * @return mixed Les recettes correspondant aux critères de recherche
     */
    public function findRecipesBySearch(?string $searchTerm,
                                        ?int $ingredientFilter): mixed
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->leftJoin('r.ingredients', 'ri');
        $queryBuilder->leftJoin('ri.Ingredient', 'i');
        if ($searchTerm){
            $queryBuilder->andWhere('r.Nom LIKE :searchTerm');
            $queryBuilder->setParameter('searchTerm','%'.$searchTerm. '%');
        }
        if ($ingredientFilter){
            $queryBuilder->andWhere('i.id = :ingredientId');
            $queryBuilder->setParameter('ingredientId', $ingredientFilter);
        }
        return $queryBuilder->getQuery()->getResult();
    }
}
