@extends('layouts.front')

@section('title', 'Créneaux de collecte — Espace association')

@section('content')
<div class="tc-page">
  <x-ui.hero surtitre="Espace association" titre="Créneaux de collecte"
    sous-titre="Ouvrez des créneaux de collecte à domicile : les donateurs choisissent l'un d'eux dans le formulaire de don."
    :fil="['Dons' => route('dons.index'), 'Espace association' => route('dons.espace.index'), 'Créneaux' => null]">
    <a href="{{ route('dons.espace.index') }}" class="tc-btn">Dons reçus</a>
  </x-ui.hero>
</div>

<section class="py-5">
  <div class="container">
    @include('dons.partials.flash')

    <div class="row g-5">
      <div class="col-lg-4">
        <div class="card border-0 bg-light">
          <div class="card-body">
            <h2 class="h5 text-uppercase mb-3">Nouveau créneau</h2>
            {{-- Un administrateur peut ouvrir l'espace sans être responsable d'une association : rien à proposer. --}}
            @if ($associations->isEmpty())
              <p class="mb-0">Vous n'êtes responsable d'aucune association : les créneaux se créent depuis le compte du responsable.</p>
            @else
            <form method="POST" action="{{ route('dons.espace.creneaux.store') }}" novalidate>
              @csrf
              @if ($associations->count() > 1)
                <div class="mb-3">
                  <label for="association_id" class="form-label">Association</label>
                  <select id="association_id" name="association_id" @class(['form-select', 'is-invalid' => $errors->has('association_id')])>
                    @foreach ($associations as $association)
                      <option value="{{ $association->id }}" @selected((string) old('association_id') === (string) $association->id)>{{ $association->nom }}</option>
                    @endforeach
                  </select>
                  @error('association_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
              @else
                <input type="hidden" name="association_id" value="{{ $associations->first()->id }}">
              @endif
              <div class="mb-3">
                <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                <input type="date" id="date" name="date" min="{{ today()->toDateString() }}" value="{{ old('date') }}" @class(['form-control', 'is-invalid' => $errors->has('date')]) required>
                @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="row g-2 mb-3">
                <div class="col-6">
                  <label for="heure_debut" class="form-label">De <span class="text-danger">*</span></label>
                  <input type="time" id="heure_debut" name="heure_debut" value="{{ old('heure_debut', '09:00') }}" @class(['form-control', 'is-invalid' => $errors->has('heure_debut')]) required>
                  @error('heure_debut') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6">
                  <label for="heure_fin" class="form-label">À <span class="text-danger">*</span></label>
                  <input type="time" id="heure_fin" name="heure_fin" value="{{ old('heure_fin', '12:00') }}" @class(['form-control', 'is-invalid' => $errors->has('heure_fin')]) required>
                  @error('heure_fin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
              </div>
              <div class="mb-3">
                <label for="capacite_max" class="form-label">Nombre de collectes possibles <span class="text-danger">*</span></label>
                <input type="number" id="capacite_max" name="capacite_max" min="1" max="100" value="{{ old('capacite_max', 4) }}" @class(['form-control', 'is-invalid' => $errors->has('capacite_max')]) required>
                @error('capacite_max') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <button type="submit" class="btn btn-dark rounded-pill px-4">Ouvrir le créneau</button>
            </form>
            @endif
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <h2 class="h5 text-uppercase mb-3">Créneaux à venir</h2>
        @if ($creneaux->isEmpty())
          <p class="text-muted">Aucun créneau à venir. Sans créneau ouvert, les donateurs ne peuvent choisir que le dépôt sur place.</p>
        @else
          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>Créneau</th>
                  @if ($associations->count() > 1) <th>Association</th> @endif
                  <th class="text-center">Réservés</th>
                  <th class="text-end"></th>
                </tr>
              </thead>
              <tbody>
                @foreach ($creneaux as $creneau)
                  <tr>
                    <td>{{ $creneau->libelle() }}</td>
                    @if ($associations->count() > 1) <td>{{ $creneau->association->nom }}</td> @endif
                    <td class="text-center">
                      <span @class(['badge rounded-pill', 'text-bg-danger' => $creneau->placesRestantes() === 0, 'text-bg-light border' => $creneau->placesRestantes() > 0])>
                        {{ $creneau->nombreReservations() }} / {{ $creneau->capacite_max }}
                      </span>
                    </td>
                    <td class="text-end">
                      @can('delete', $creneau)
                        <form method="POST" action="{{ route('dons.espace.creneaux.destroy', $creneau) }}" onsubmit="return confirm('Supprimer ce créneau ?')">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-outline-danger btn-sm">Supprimer</button>
                        </form>
                      @else
                        <span class="small text-muted">Réservé</span>
                      @endcan
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          {{ $creneaux->links('dons.partials.pagination') }}
        @endif
      </div>
    </div>
  </div>
</section>
@endsection
