@if ($etape->outils_necessaires)<p class="tcu-pre"><strong>Outils :</strong> {{ $etape->outils_necessaires }}</p>@endif
@if ($etape->duree_minutes)<p><strong>Durée estimée :</strong> {{ $etape->duree_minutes }} min</p>@endif
@if ($etape->conseils_securite)<p class="tcu-pre"><strong>Sécurité :</strong> {{ $etape->conseils_securite }}</p>@endif
