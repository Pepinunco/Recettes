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

    public function findNewestRecipes()
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->addOrderBy('r.dateCreated','DESC');
        $query = $queryBuilder->getQuery();

        $query->setMaxResults(4);
        return $query->getResult();
    }

    public function findBestRecipes()
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->addOrderBy('r.Note','DESC');
        $query = $queryBuilder->getQuery();

        $query->setMaxResults(4);
        return $query->getResult();
    }

    public function findEntrees(int $page, int $limit): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->andWhere('r.Categorie = 1');
        $queryBuilder->addOrderBy('r.Note', 'DESC');
        $query = $queryBuilder->getQuery();

        $query->setFirstResult(($page-1) * $limit);
        $query->setMaxResults($limit);
        return new Paginator($query, true);
    }

    public function findPlats(int $page, int $limit)
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->andWhere('r.Categorie = 2');
        $queryBuilder->addOrderBy('r.Note', 'DESC');
        $query = $queryBuilder->getQuery();

        $query->setFirstResult(($page-1) * $limit);
        $query->setMaxResults($limit);
        return new Paginator($query, true);
    }

    public function findDesserts(int $page, int $limit)
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->andWhere('r.Categorie = 3');
        $queryBuilder->addOrderBy('r.Note', 'DESC');
        $query = $queryBuilder->getQuery();

        $query->setFirstResult(($page-1) * $limit);
        $query->setMaxResults($limit);
        return new Paginator($query, true);
    }

    public function findRecipeById(int $id)
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

    public function findRecipesBySearch($searchTerm, $ingredientFilter)
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
