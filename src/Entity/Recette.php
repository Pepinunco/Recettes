<?php

namespace App\Entity;

use App\Repository\RecetteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecetteRepository::class)]
class Recette
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $Nom = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $TempsPreparation = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $TempsCuisson = null;

    #[ORM\Column]
    private ?int $Portions = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $Instructions = null;

    #[ORM\Column(nullable: true)]
    private ?float $Note = null;

    #[ORM\ManyToOne(inversedBy: 'recettes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Categorie $Categorie = null;

    #[ORM\ManyToOne(inversedBy: 'recettes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $Auteur = null;

    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'recette',orphanRemoval: true, cascade: ["remove"])]
    private Collection $Commentaires;

    /**
     * @var Collection<int, RecetteIngredient>
     */
    #[ORM\OneToMany(targetEntity: RecetteIngredient::class, mappedBy: 'recette', orphanRemoval: true, cascade: ["remove"])]
    private Collection $ingredients;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreated = null;

    /**
     * @var Collection<int, Ratings>
     */
    #[ORM\OneToMany(targetEntity: Ratings::class, mappedBy: 'Recette')]
    private Collection $ratings;

    public function __construct()
    {
        $this->Commentaires = new ArrayCollection();
        $this->ingredients = new ArrayCollection();
        $this->ratings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->Nom;
    }

    public function setNom(string $Nom): static
    {
        $this->Nom = $Nom;

        return $this;
    }

    public function getTempsPreparation(): ?\DateTimeInterface
    {
        return $this->TempsPreparation;
    }

    public function setTempsPreparation(\DateTimeInterface $TempsPreparation): static
    {
        $this->TempsPreparation = $TempsPreparation;

        return $this;
    }

    public function getTempsCuisson(): ?\DateTimeInterface
    {
        return $this->TempsCuisson;
    }

    public function setTempsCuisson(\DateTimeInterface $TempsCuisson): static
    {
        $this->TempsCuisson = $TempsCuisson;

        return $this;
    }

    public function getPortions(): ?int
    {
        return $this->Portions;
    }

    public function setPortions(int $Portions): static
    {
        $this->Portions = $Portions;

        return $this;
    }

    public function getInstructions(): ?string
    {
        return $this->Instructions;
    }

    public function setInstructions(string $Instructions): static
    {
        $this->Instructions = $Instructions;

        return $this;
    }

    public function getNote(): ?float
    {
        return $this->Note;
    }

    public function setNote(float $Note): static
    {
        $this->Note = $Note;

        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->Categorie;
    }

    public function setCategorie(?Categorie $Categorie): static
    {
        $this->Categorie = $Categorie;

        return $this;
    }

    public function getAuteur(): ?Utilisateur
    {
        return $this->Auteur;
    }

    public function setAuteur(?Utilisateur $Auteur): static
    {
        $this->Auteur = $Auteur;

        return $this;
    }

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        return $this->Commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): static
    {
        if (!$this->Commentaires->contains($commentaire)) {
            $this->Commentaires->add($commentaire);
            $commentaire->setRecette($this);
        }

        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->Commentaires->removeElement($commentaire)) {
            // set the owning side to null (unless already changed)
            if ($commentaire->getRecette() === $this) {
                $commentaire->setRecette(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, RecetteIngredient>
     */
    public function getIngredients(): Collection
    {
        return $this->ingredients;
    }

    public function addIngredient(RecetteIngredient $ingredient): static
    {
        if (!$this->ingredients->contains($ingredient)) {
            $this->ingredients->add($ingredient);
            $ingredient->setRecette($this);
        }

        return $this;
    }

    public function removeIngredient(RecetteIngredient $ingredient): static
    {
        if ($this->ingredients->removeElement($ingredient)) {
            // set the owning side to null (unless already changed)
            if ($ingredient->getRecette() === $this) {
                $ingredient->setRecette(null);
            }
        }

        return $this;
    }

    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTimeInterface $dateCreated): static
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * @return Collection<int, Ratings>
     */
    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    public function addRating(Ratings $rating): static
    {
        if (!$this->ratings->contains($rating)) {
            $this->ratings->add($rating);
            $rating->setRecette($this);
        }

        return $this;
    }

    public function removeRating(Ratings $rating): static
    {
        if ($this->ratings->removeElement($rating)) {
            // set the owning side to null (unless already changed)
            if ($rating->getRecette() === $this) {
                $rating->setRecette(null);
            }
        }

        return $this;
    }

    public function updateNote(): static
    {
        $totalRatings = count($this->ratings);
        if ($totalRatings > 0){
            $sumRatings = array_reduce($this->ratings->toArray(), function ($sum, $rating){
                return $sum + $rating->getValue();
            }, 0);
            $this->Note = $sumRatings/$totalRatings;
        } else{
            $this->Note = null;
        }
        return $this;
    }
}
