# 🧠 Feature: Hermes Webhook — Catégorisation & Priorisation temps réel des Google Tasks

> **Product Manager:** John 📋
> **Date:** 2026-07-24
> **État:** Backlog — Prêt pour Sprint Planning

---

## 1. Vision

Hermes devient le cerveau entre l'app Laravel/Vue (task-manager) et Google Tasks.
Quand Martin crée ou modifie une tâche, un webhook Hermes catégorise automatiquement
(projet, contexte) et priorise (P1-P4). En batch nocturne, Hermes nettoie et rééquilibre.

**Zéro changement UI Laravel/Vue** — le webhook est un call HTTP sortant *depuis* l'app
vers Hermes. Martin continue d'utiliser l'app et Google Tasks mobile normalement.

---

## 2. Architecture

```
┌──────────────────┐     Webhook HTTP      ┌─────────────────────────────────┐
│  Laravel/Vue App │  ──────────────────►  │         Hermes (Agent)          │
│  (task-manager)  │  POST /hook/tasks     │                                 │
│                  │  {task, action, user}  │  Categorize → Prioritize →     │
│  storeTask() ───►│                       │  Respond {priority, category,  │
│  updateTask() ──►│                       │            notes, context}      │
│                   │                       │                                 │
│  Google Tasks API │                       │  Joplin RAG ─► Joplin PARA     │
│  (source of truth)│                       │  DeepSeek    ┤                 │
└──────────────────┘                       └─────────────────────────────────┘
```

### Flux temps réel

1. Martin crée une task dans l'app Laravel/Vue (ou via Google Tasks mobile directement)
2. L'app envoie un POST au webhook Hermes avec `{title, notes, list_id, action: 'created'}`
3. Hermes analyse le titre + notes via DeepSeek (modèle déjà configuré)
4. Hermes retourne un JSON : `{priority, category, project, context, joplin_note_hint}`
5. L'app met à jour la tâche dans Google Tasks avec le titre encodé `[P2] ...`
6. Si Joplin note liée trouvée, elle est ajoutée aux notes de la tâche

### Flux batch nocturne

1. Cron Hermes (`cron/`) toutes les nuits à 02:00
2. Liste les tâches Google Tasks ouvertes
3. Pour chaque tâche :
   a. Archive si inactive >30 jours (complète automatiquement)
   b. Rétrograde P1 abusifs (>7 jours sans update) → P2
   c. Marque les tâches stagnantes (>14 jours) en P4 avec note "À revoir"
4. Génère un rapport Airtable / note Joplin de ce qui a été fait

---

## 3. User Stories (Martin POV, valeur business)

### MVP — Sprint 1 & 2

| ID | Story | Valeur | Effort |
|----|-------|--------|--------|
| **HW-01** | En tant que Martin, quand je crée une tâche dans l'app, Hermes la catégorise automatiquement (projet, type) — sans que je doive faire de choix d'UI | **🔥 Critique** — Le bordel actuel, c'est l'absence de structure. L'app ne me demande pas de catégoriser, donc tout s'accumule dans la Default List. | **S** |
| **HW-02** | En tant que Martin, quand je crée une tâche, Hermes lui assigne une priorité P1-P4 cohérente — détectée du contexte, pas d'un menu déroulant | **🔥 Critique** — Sans priorisation, tout est P3 par défaut (codec existant). Je veux que Hermes sente si c'est urgent. | **S** |
| **HW-03** | En tant que Martin, je peux modifier une tâche existante et Hermes re-catégorise/re-priorise automatiquement | **🔥 Critique** — La vie change. Un P2 devient P1. | **S** |
| **HW-04** | En tant que Martin, le webhook Hermes est **opt-in** et ne casse jamais mon flux actuel — si Hermes est down, l'app continue sans webhook | **🔥 Critique** — L'app DOIT rester stable. Pas de regression. | **XS** |

### MVP — Sprint 3 (Batch Nocturne)

| ID | Story | Valeur | Effort |
|----|-------|--------|--------|
| **HW-05** | En tant que Martin, quand je me lève, les tâches inactives >30 jours sont archivées automatiquement — mon Daily ne montre plus de vieilleries | **🔥 Critique** — Le plus gros problème: tâches accumulées qu'il faut trier manuellement. | **M** |
| **HW-06** | En tant que Martin, je peux désactiver le batch nocturne dans Hermes si je ne veux pas de modifications automatiques | **🔴 High** — Control avant tout. | **XS** |
| **HW-07** | En tant que Martin, les P1 qui stagnent >7 jours sont rétrogradés en P2 — le système reste crédible | **🟠 High** — Sans ça, tout est P1 et rien ne l'est. | **S** |

### V2 — Sprint 4 et au-delà

| ID | Story | Valeur | Effort |
|----|-------|--------|--------|
| **HW-08** | En tant que Martin, je reçois un rapport matinal de ce qui a été fait par le batch nocturne — lien Joplin pour les détails | **🟡 Medium** | **M** |
| **HW-09** | En tant que Martin, Hermes détecte quand une tâche est liée à une note Joplin existante et ajoute le lien dans les notes de la tâche | **🟡 Medium** — Utile mais pas bloquant. Le contexte arrive par d'autres canaux. | **L** |
| **HW-10** | En tant que Martin, je peux demander "nettoie ma semaine" à Hermes pour re-prioriser tout mon backlog en une passe | **🟢 Low** — Nice-to-have. | **M** |
| **HW-11** | En tant que Martin, le webhook écrit un log dans la note Joplin "🔄 Hermes Activity" avec l'historique des changements | **🟢 Low** — Auditabilité en mode slow. | **S** |

---

## 4. Coding Stories (tâches techniques)

### Sprint 1 — Foundation (webhook, auth, endpoint)

| ID | Tâche | Fichiers | Effort |
|----|-------|----------|--------|
| **CS-01** | Ajouter une config `hermes_webhook_url` et `hermes_webhook_token` dans Laravel (google-tasks config) — env vars avec fallback null | `config/google-tasks.php`, `.env.example` | **XS** |
| **CS-02** | Créer un `HermesWebhookService` — envoie POST avec HMAC ou Bearer token, timeout 5s, gestion d'erreur silencieuse | `app/Services/Hermes/HermesWebhookService.php` | **S** |
| **CS-03** | Créer un trait `FiresHermesWebhook` sur `TasksController` — intercepte `storeTask()` et `updateTask()`, envoie payload si configuré | `app/Http/Controllers/TasksController.php` | **S** |
| **CS-04** | Payload webhook: `{action, task_id, title, notes, list_id, user_id, priority?, due?}` + signature HMAC | `HermesWebhookService.php` | **XS** |
| **CS-05** | Côté Hermes: créer le skill/déclencheur Hermes qui reçoit le webhook — endpoint HTTP simple dans la gateway | Skill Hermes `task-categorizer` | **M** |

### Sprint 2 — Catégorisation & Priorisation temps réel

| ID | Tâche | Effort |
|----|-------|--------|
| **CS-06** | Prompt DeepSeek structuré: analyser titre + notes → détecter projet (PARA), type (dev/admin/finance/santé/maison), priorité P1-P4 | **S** |
| **CS-07** | Webhook Hermes → parsing de la réponse → retour structuré à l'app : `{priority, category, project, context}` | **S** |
| **CS-08** | Dans `storeTask()`, après réception webhook, appeler `TaskPriorityCodec::encodeTitle()` avec la priorité Hermes | **XS** |
| **CS-09** | Timeout + fallback: si Hermes ne répond pas <4s, continuer sans webhook — log warning, pas d'erreur bloquante | **XS** |
| **CS-10** | Rate limiting: pas plus de 5 webhooks/minute (Google Tasks API rate limit déjà serré) | **XS** |

### Sprint 3 — Batch Nocturne

| ID | Tâche | Effort |
|----|-------|--------|
| **CS-11** | Cron Hermes nocturne: lister toutes les tâches ouvertes via Google Tasks API (script Hermes) | **M** |
| **CS-12** | Logique d'archivage: tâche inactive >30 jours sans completion → complete automatiquement (sauf si due date future) | **M** |
| **CS-13** | Rétrogradation P1: tâche P1 sans mise à jour >7 jours → `patchTask()` avec `[P2]` dans le titre | **S** |
| **CS-14** | Stagnation P4: tâche sans mise à jour >14 jours → `[P4]` + note "📌 Stale — review needed" | **S** |
| **CS-15** | Toggle pour désactiver le batch: config Hermes + env var `HERMES_BATCH_NOCTURNE_ENABLED=false` | **XS** |

### Sprint 4 — Rapports & Joplin liaison (V2)

| ID | Tâche | Effort |
|----|-------|--------|
| **CS-16** | Rapport quotidien: créer note Joplin avec résumé de ce que le batch a fait | **M** |
| **CS-17** | RAG Joplin: chercher notes connexes par titre de tâche, retourner `joplin_note_id` si match >0.7 | **L** |

---

## 5. Effort total

| Phase | Points | Jours-homme | Description |
|-------|--------|-------------|-------------|
| **Sprint 1** (Foundation) | CS-01 à CS-05 = 1+2+2+1+3 = **9 pts** | ~1.5 jours | Webhook Laravel + endpoint Hermes |
| **Sprint 2** (Catégorisation temps réel) | CS-06 à CS-10 = 2+2+1+1+1 = **7 pts** | ~1 jour | Prompt + intégration + fallback |
| **Sprint 3** (Batch nocturne) | CS-11 à CS-15 = 3+3+2+2+1 = **11 pts** | ~1.5 jours | Archivage + rétrogradation + toggle |
| **Sprint 4** (Rapports + Joplin) | CS-16 + CS-17 = 3+5 = **8 pts** | ~1.5 jours | Rapport note Joplin + RAG |
| **Total** | **35 pts** | ~5.5 jours | |

---

## 6. ⭐ MVP Minimal (<3 lignes)

> Un webhook POST depuis `storeTask()` qui demande à Hermes (DeepSeek) de
> prioriser [P1..P4] et catégoriser (projet, type) chaque nouvelle tâche.
> Résultat intégré dans le titre via le codec `[P2] titre` existant.
> Batch nocturne opt-in qui archive les tasks >30j et rétrograde les P1 abusifs.

### Le MVP tient en **Sprint 1 + Sprint 2 + CS-11/CS-13 du Sprint 3** = 19 pts, ~3 jours

---

## 7. Ce qui peut attendre V2

1. **Rapport quotidien Joplin** (HW-08, CS-16) — cool mais Martin n'a pas besoin d'un
   rapport pour savoir que ses tâches sont rangées. La preuve est dans Google Tasks.

2. **Lien automatique Joplin** (HW-09, CS-17) — chercher des notes connexes via RAG
   est le plus gros effort (L) et le bénéfice est incertain tant que le flux n'est pas
   rodé. Attend V2.

3. **Webhook log dans Joplin** (HW-11) — auditabilité intéressante mais pas nécessaire
   pour sentir la différence au quotidien. Peut être fait en 1h quand le besoin se présente.

---

## 8. Contraintes & Décisions

### Design Decisions

| Décision | Choix | Raison |
|----------|-------|--------|
| Webhook synchrone ou asynchrone ? | **Synchrone (POST, timeout 5s)** | Async = queue, complexité superflue. Le webhook Hermes est rapide (<2s). Si timeout, fallback silencieux. |
| Priorité champ dédié ou encodée dans titre ? | **Titre encodé `[P2]`** (codec existant) | Zéro changement schema Google Tasks. Compatible mobile. |
| Batch lancé par Hermes ou par Laravel cron ? | **Hermes cron** | « Hermes est le cerveau ». Laravel n'a pas de cron activé (shared hosting). |
| Stockage des décisions Hermes ? | **Aucun (stateless)** | Hermes n'a pas besoin de stocker — Google Tasks est la source of truth. Le webhook reçoit le titre + notes et répond. |
| Auth du webhook ? | **Bearer token** (simple) + HMAC body (optionnel) | Pas de OAuth complexe. Secret partagé entre les deux. |

### Risques

| Risque | Mitigation |
|--------|-----------|
| Hermes down → app bloquée | Timeout + try/catch. L'app continue sans webhook. |
| DeepSeek donne une priorité incohérente (ex: tout P1) | Prompt engineering: demander distribution forçée. Fallback: codec default P3. |
| Batch nocturne trop agressif | Toggle OFF par défaut? Non — ON mais avec log et undo window implicite (Google Tasks trash 30 jours). |
| Rate limit Google Tasks API (batch) | Une seule requête par batch (`listTasks` + `patchTask` individuels). Max 50 patches/nuit. |
| Joplin MCP unreachable (RAG) | Pas un problème pour le MVP — le RAG est Sprint 4. |

---

## 9. Dépendances

### Existe déjà ✅

- `TaskPriorityCodec` — encode/décode `[P1]..[P4]` dans le titre
- `GoogleTasksClient` — toutes les opérations CRUD + move
- `config/google-tasks.php` — extensible
- OAuth Google Tasks fonctionnel
- Hermes configuré avec DeepSeek (provider `deepseek`)
- Hermes gateway HTTP (peut exposer des endpoints)
- Joplin MCP connecté (183 notebooks, 881 notes indexées RAG)

### À construire 🏗️

- Endpoint HTTP Hermes pour recevoir webhook (skill/déclencheur)
- `HermesWebhookService` côté Laravel
- Prompt DeepSeek structuré pour catégorisation/priorisation
- Cron Hermes pour batch nocturne
- Optionnel: script Python Hermes pour `gtd-nightly-cleanup`

---

## 10. Critères de succès du MVP

- [ ] Créer une tâche dans l'app → le titre devient `[P2] Faire X` (détecté intelligentment)
- [ ] Créer sans webhook configuré → comportement inchangé (pas de webhook call)
- [ ] Timeout webhook → tâche créée normalement, priorité par défaut (P3), log warning
- [ ] Modifier le titre d'une tâche → re-catégorisation si Hermes répond
- [ ] Batch nocturne désactivable via `HERMES_BATCH_NOCTURNE_ENABLED=false`
- [ ] Tâche inactive >30j → automatiquement complétée par le batch
- [ ] P1 stagnant >7j → rétrogradé en P2
- [ ] L'app n'a AUCUNE modification UI (zéro risque de regression frontend)
