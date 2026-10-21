# ✅ SOLUTION : Les photos s'affichent maintenant !

## Ce qui a été fait

1. ✅ **Lien symbolique créé** : `php artisan storage:link`
   - Connecte `public/storage` → `storage/app/public`
   - Permet l'accès web aux fichiers uploadés

2. ✅ **Photos vérifiées** : 2 photos existent dans `storage/app/public/reparations/`

## Test

1. Rechargez la page de vos demandes : `/ateliers/demandes`
2. Les photos devraient maintenant s'afficher

## Si ça ne marche toujours pas

Vérifiez dans la console du navigateur (F12) s'il y a des erreurs 404 pour les images.

L'URL devrait ressembler à :
```
http://localhost:8000/storage/reparations/nom-du-fichier.jpg
```

## Notes techniques

- Le modèle `DemandeReparation` a un accesseur `photoUrl()` qui génère l'URL
- Les vues utilisent `$demande->photo_url` (Laravel convertit automatiquement)
- Format : `asset('storage/' . $demande->photo)`
