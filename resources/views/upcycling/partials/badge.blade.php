{{-- Badge Bootstrap. Paramètres : enum (Difficulte ou StatutProjet) --}}
<span class="badge rounded-0 text-bg-{{ $enum->color() }} fw-normal">{{ $enum->label() }}</span>
