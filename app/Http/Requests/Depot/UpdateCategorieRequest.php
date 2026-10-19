<?php

namespace App\Http\Requests\Depot;

use Illuminate\Validation\Rules\Unique;

class UpdateCategorieRequest extends StoreCategorieRequest
{
    protected function nomUnique(): Unique
    {
        return parent::nomUnique()->ignore($this->route('categorie'));
    }
}
