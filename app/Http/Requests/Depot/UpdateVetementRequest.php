<?php

namespace App\Http\Requests\Depot;

class UpdateVetementRequest extends StoreVetementRequest
{
    /**
     * Le back office peut tout modifier ; en front, seul le déposant peut modifier son vêtement.
     */
    public function authorize(): bool
    {
        return $this->isBackOffice()
            || $this->user()->can('update', $this->route('vetement'));
    }
}
