<?php

namespace App\DTO;

class SearchDTO
{
    private $searchTerm;
    private $ingredientFilter;

    /**
     * @return mixed
     */
    public function getSearchTerm(): ?string
    {
        return $this->searchTerm;
    }

    /**
     * @param mixed $searchTerm
     */
    public function setSearchTerm($searchTerm): self
    {
        $this->searchTerm = $searchTerm;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIngredientFilter()
    {
        return $this->ingredientFilter;
    }

    /**
     * @param mixed $ingredientFilter
     */
    public function setIngredientFilter($ingredientFilter): void
    {
        $this->ingredientFilter = $ingredientFilter;
    }


}