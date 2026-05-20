# Defect: DEF-003 — Google OAuth Token Doit Ne JAMAIS Expirer

**Statut**: 🔴 Open
**Priorité**: 🔴 Critique
**Story Points**: 2
**Créé**: 2026-05-20
**Mis à jour**: 2026-05-20
**Sprint**: Backlog
**Rapporté par**: Martin Fournier

---

## Énoncé du Problème

L'accès Google OAuth expire régulièrement (2-7 jours), forçant une
ré-approbation manuelle. L'application demande déjà `access_type=offline`
mais inclut `prompt=consent` qui force la ré-approbation à chaque session.

**Objectif :** Le token NE DOIT JAMAIS expirer. Une seule approbation
suffit pour toujours.

## Root Cause

Dans `app/Http/Controllers/Auth/GoogleOAuthController.php` :

```php
->with([
    'access_type' => 'offline',   // ✅ Refresh token demandé
    'prompt' => 'consent',         // ❌ FORCE ré-approbation à chaque fois
])
```

`prompt=consent` force Google à montrer l'écran de consentement à chaque
autorisation, ce qui invalide l'ancien refresh token.

## Fix

Supprimer `prompt => 'consent'` :

```php
->with([
    'access_type' => 'offline',
])
```

## Test

- [ ] Après le fix : une seule approbation Google suffit pour toujours
- [ ] Le refresh token est stocké en DB (colonne `google_refresh_token`)
- [ ] Le bot fonctionne 30+ jours sans ré-approbation

## Fichiers Affectés

- `app/Http/Controllers/Auth/GoogleOAuthController.php` — ✅ Déjà fixé
