# BMAD Sprint — Grocery Intelligence (Features 1-7)

> Sprint Grocery — 2026-05-22
> Martin Fournier — Product Owner
> C-3PO — Dev autonome

---

## 🎯 Objectif

Implémenter les 7 features de l'écosystème circulaires/spéciaux/menu de la semaine dans le health-dashboard (Laravel).

## 📋 Sprint Backlog

| # | Feature | Priorité | Statut |
|---|---------|----------|--------|
| 1 | 🚨 **Alertes Stock Up** — Notif Telegram quand prix atteint plancher historique | Haute | 🔴 |
| 2 | 📊 **Heatmap de prix** — Visualisation couleur des tendances par item/semaine | Haute | 🔴 |
| 3 | 🥘 **Recettes ← Circulaires** — Meal plan priorise les items en spécial | Haute | 🔴 |
| 4 | 🛍️ **Shopping List auto** — Liste groupée par section à partir du meal plan | Haute | 🔴 |
| 5 | 📈 **Prédiction des prix** — "Le lait monte en juin" basé sur historique 1 an | Moyenne | 🔴 |
| 6 | 🇫🇷 **Vue Flipp Québec** — Tous les magasins, un produit, le meilleur prix | Moyenne | 🔴 |
| 7 | 🔄 **Sync PC Optimum** — Points bonus et offres personnalisées | Faible | 🔴 |

---

## 📊 Architecture

```
                    health-dashboard/Laravel
┌─────────────────────────────────────────────────────────┐
│  Feature 1: StockUpAlert (Notification + Telegram)      │
│  Feature 2: PriceHeatmap (Chart Controller + Vue)       │
│  Feature 3: RecipeMatcher (Service + Model)             │
│  Feature 4: ShoppingListService (Generate from MealPlan)│
│  Feature 5: PricePredictor (Analyse PriceHistory)       │
│  Feature 6: FlippView (UnifiedPriceController)          │
│  Feature 7: LoyaltyPoints (PC Optimum Model + Sync)     │
└─────────────────────────────────────────────────────────┘
                          │
           ┌──────────────┴──────────────┐
           ▼                              ▼
    PriceHistory                    Telegram/Discord
    PriceStat                       Notification channel
    GroceryDeal
    GroceryStore
```

---

## 🗺️ Roadmap d'exécution

```
Phase 1 (ce soir):  Models + Services backend (Features 1-4)
Phase 2 (ce soir):  Views + UI (Features 1-4)
Phase 3 (demain):   Features 5-7
Phase 4 (demain):   Tests + polish
```

---

## 📦 Livrables

- Controllers, Models, Views pour chaque feature
- Routes web protégées (auth)
- Tests unitaires pour les services
- Notifications Telegram pour les alertes
