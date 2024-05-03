<?php

namespace App\Repository;

use App\Entity\Recette;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

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
        $queryBuilder->andWhere('r.Note > 4');
        $queryBuilder->addOrderBy('r.Note','DESC');
        $query = $queryBuilder->getQuery();

        $query->setMaxResults(4);
        return $query->getResult();
    }

    public function findEntrees()
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->andWhere('r.Categorie = 1');
        $queryBuilder->addOrderBy('r.Note', 'DESC');
        $query = $queryBuilder->getQuery();

        $query->setMaxResults(10);
        return $query->getResult();
    }

    public function findPlats()
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->andWhere('r.Categorie = 2');
        $queryBuilder->addOrderBy('r.Note', 'DESC');
        $query = $queryBuilder->getQuery();

        $query->setMaxResults(10);
        return $query->getResult();
    }

    public function findDesserts()
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->andWhere('r.Categorie = 3');
        $queryBuilder->addOrderBy('r.Note', 'DESC');
        $query = $queryBuilder->getQuery();

        $query->setMaxResults(10);
        return $query->getResult();
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
}
