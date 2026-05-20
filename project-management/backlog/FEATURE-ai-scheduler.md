# 🧠 Feature: AI Priority Scheduler

> Plan d'implémentation Bmad — Task Manager
> 2026-05-20

---

## 🎯 Vision

Un bouton "Optimiser ma journée" qui analyse toutes les tâches Google Tasks
ouvertes et les réordonne intelligemment selon :
- **Priorité** (P1 → P4 déjà codée)
- **Échéance** (due date / overdue)
- **Énergie estimée** (tâche facile = matin, complexe = après-midi)
- **Contexte** (tag/projet)

---

## 📋 User Story

**En tant que** Martin (power user)
**Je veux** cliquer "Optimiser ma journée"
**Pour que** mes tâches soient réordonnées par priorité + timing intelligent
**Et que** je perde moins de temps à décider quoi faire.

---

## 🏗️ Architecture

```
┌──────────────────────────────────────────────────────────────┐
│                    Frontend (Vue/Inertia)                     │
│                                                              │
│  [Optimiser ma journée 🤖] ← bouton dans la toolbar          │
│       │                                                     │
│       ▼                                                     │
│  Modal "Suggestions de la journée"                          │
│  ┌─────────────────────────────────────────────────┐        │
│  │  ☀️ 09:00 → Répondre email Fournisseur (P2)     │        │
│  │  🔥 10:00 → Finaliser rapport Q2 (P1) ⚡focus   │        │
│  │  📞 11:00 → Appel client ABC (P1)              │        │
│  │  🥪 12:00 → Lunch                              │        │
│  │  ⚡ 13:00 → Tâches admin (P3) ☕ facile         │        │
│  │                                               │        │
│  │  [Appliquer]  [Réorganiser]  [Annuler]        │        │
│  └─────────────────────────────────────────────────┘        │
│                                                              │
└──────────────────────┬───────────────────────────────────────┘
                       │ POST /api/tasks/schedule
                       ▼
┌──────────────────────────────────────────────────────────────┐
│                   Backend (Laravel)                          │
│                                                              │
│  ScheduleController.php                                      │
│  1. Récupère toutes les tâches ouvertes via Google Tasks API │
│  2. Les envoie à DeepSeek avec un prompt structuré           │
│  3. DeepSeek retourne un ordre + timing suggéré              │
│  4. Met à jour les `due` dates dans Google Tasks             │
│  5. Retourne le planning au frontend                         │
│                                                              │
└──────────────────────┬───────────────────────────────────────┘
                       │ POST /tasks/task/{id}
                       ▼
┌──────────────────────────────────────────────────────────────┐
│                    Google Tasks API                          │
│                                                              │
│  Met à jour la date d'échéance pour refléter le nouveau      │
│  ordre suggéré par l'IA                                      │
└──────────────────────────────────────────────────────────────┘
```

---

## 🗂️ Fichiers à Modifier

| Fichier | Changement |
|---|---|
| `app/Http/Controllers/TasksController.php` | + `schedule()` method |
| `app/Http/Controllers/ScheduleController.php` | **Nouveau** — orchestre l'appel DeepSeek |
| `app/Services/AiSchedulerService.php` | **Nouveau** — appelle DeepSeek avec le prompt |
| `routes/web.php` | + POST `/tasks/schedule` |
| `resources/js/Pages/Tasks/Index.vue` | + Bouton + Modal suggestions |
| `resources/js/Components/AiScheduleModal.vue` | **Nouveau** — composant modal |

---

## 🤖 Prompt DeepSeek

```
Tu es un assistant productivité. Voici les tâches non complétées
de Martin pour aujourd'hui. Réordonne-les par priorité et
suggère un timing optimal.

Règles :
- P1 = urgent, à faire le matin
- P2 = important, milieu de journée
- P3/P4 = quand tu peux, après-midi
- Alterne tâches faciles (email) et difficiles (focus)
- Regroupe les tâches par contexte (appels, écriture, admin)

Tâches :
1. [P1] Finaliser rapport Q2 — due aujourd'hui
2. [P2] Répondre email Fournisseur — due demain
3. [P2] Appel client ABC — 14:00 réservé
4. [P3] Mettre à jour le CRM — pas d'échéance
5. [P4] Nettoyer boîte email — pas d'échéance
6. [P1] Correction bug production — URGENT

Réponds UNIQUEMENT en JSON :
{
  "schedule": [
    {"taskId": "6", "time": "09:00", "reason": "Urgent production bug"},
    {"taskId": "3", "time": "10:00", "reason": "Appel client (horaire fixe)"},
    {"taskId": "1", "time": "11:00", "reason": "Focus intense avant lunch"},
    {"taskId": "2", "time": "13:00", "reason": "Email simple post-lunch"},
    {"taskId": "4", "time": "14:30", "reason": "Admin calme"},
    {"taskId": "5", "time": "16:00", "reason": "Fin de journée léger"}
  ],
  "focusBlocks": ["11:00-12:00"],
  "energyTip": "Matin chargé, prévoir pause café 10:30"
}
```

---

## 📐 Wireframe du bouton

```
┌──────────────────────────────────────────────────────────────┐
│  🔍 [Search tasks...]    [📅 Today] [📋 All] [♾️ Kanban]    │
│                                                              │
│  🤖 [Optimiser ma journée]  ← NOUVEAU bouton                │
│  ─────────────────────────────────────────────────────────    │
│  ☐ [P1] 🔥 Finaliser rapport Q2                    Aujourd'hui│
│  ☐ [P1] 🐛 Correction bug production                URGENT    │
│  ☐ [P2] 📞 Appel client ABC                        14:00     │
│  ☐ [P2] ✉️ Répondre email Fournisseur               Demain    │
│  ☐ [P3] 🔧 Mettre à jour le CRM                      —       │
│  ─────────────────────────────────────────────────────────    │
│  + Add task                                         3 ouvertes│
└──────────────────────────────────────────────────────────────┘
```

---

## 📋 Stories & Effort

| Story | Pts | Description |
|---|---|---|
| **AI-01** | 3 | `AiSchedulerService` — Appel DeepSeek avec prompt structuré, parsing JSON |
| **AI-02** | 2 | `ScheduleController` — Endpoint POST, orchestration, update Google Tasks |
| **AI-03** | 2 | `AiScheduleModal.vue` — Modal avec suggestions, boutons Appliquer/Réorganiser |
| **AI-04** | 1 | Bouton dans la toolbar Tasks Index + intégration Inertia |

**Total : 8 pts, ~1 journée de dev.**

---

## ✅ Critères de Succès

- [ ] Le bouton "Optimiser ma journée" apparaît dans la toolbar
- [ ] Le modal affiche 6+ tâches ordonnées avec timing et raison
- [ ] "Appliquer" met à jour les dates d'échéance dans Google Tasks
- [ ] "Réorganiser" relance l'IA sans appliquer
- [ ] Le prompt DeepSeek renvoie du JSON valide à chaque fois
- [ ] Les tâches sans échéance sont placées intelligemment
- [ ] Fallback si DeepSeek est down : ordre P1→P4 basique
