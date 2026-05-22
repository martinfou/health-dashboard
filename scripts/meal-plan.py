#!/usr/bin/env python3
"""
🍽️ Meal Plan Generator — optimise les repas de la semaine basés sur les spéciaux.
Utilise les données scrappées des circulaires pour créer un plan repas optimisé.

Usage:
  python3 scripts/meal-plan.py                    # Plan repas cette semaine
  python3 scripts/meal-plan.py --save             # Sauvegarde dans health-dashboard
  python3 scripts/meal-plan.py --telegram         # Envoie sur Telegram
"""

import json
import os
import sys
from datetime import datetime, timedelta

STORAGE_DIR = os.path.join(os.path.dirname(__file__), "..", "storage", "app", "grocery")
OUTPUT_DIR = os.path.join(os.path.dirname(__file__), "..", "storage", "app", "mealplans")

PROTEIN_CATEGORIES = ['viande', 'poisson', 'laitier']
PRODUCE_CATEGORIES = ['fruits', 'legumes']

# Recettes simples basées sur les ingrédients en spécial
RECIPES = {
    "poulet_legumes": {
        "name": "Poulet rôti aux légumes",
        "ingredients": {"viande": ["poulet"], "legumes": ["champignon", "salade", "tomate"]},
        "proteines": 30, "kcal": 450,
    },
    "saumon_legumes": {
        "name": "Filet de saumon et légumes grillés",
        "ingredients": {"poisson": ["saumon"], "legumes": ["champignon", "salade", "tomate"]},
        "proteines": 35, "kcal": 500,
    },
    "porc_pommes": {
        "name": "Filet de porc aux pommes",
        "ingredients": {"viande": ["porc"], "fruits": ["pomme"]},
        "proteines": 28, "kcal": 420,
    },
    "salade_proteinee": {
        "name": "Salade protéinée poulet/légumes",
        "ingredients": {"viande": ["poulet"], "legumes": ["salade", "tomate", "concombre"]},
        "proteines": 25, "kcal": 350,
    },
    "pates_viande": {
        "name": "Pâtes à la viande",
        "ingredients": {"viande": ["viande hachee", "boeuf"], "epicerie": ["pates", "sauce"]},
        "proteines": 22, "kcal": 550,
    },
    "omelette_champignons": {
        "name": "Omelette aux champignons",
        "ingredients": {"laitier": ["oeuf", "fromage"], "legumes": ["champignon"]},
        "proteines": 20, "kcal": 300,
    },
    "tacos_poulet": {
        "name": "Tacos au poulet",
        "ingredients": {"viande": ["poulet"], "legumes": ["salade", "tomate", "avocat"]},
        "proteines": 30, "kcal": 500,
    },
    "saute_crevettes": {
        "name": "Sauté de crevettes et légumes",
        "ingredients": {"poisson": ["crevette"], "legumes": ["brocoli", "poivron", "champignon"]},
        "proteines": 25, "kcal": 380,
    },
    "salade_fruits": {
        "name": "Salade de fruits frais avec yogourt",
        "ingredients": {"fruits": [], "laitier": ["yaourt"]},
        "proteines": 10, "kcal": 200,
    },
    "fromage_crudites": {
        "name": "Plateau fromages et crudités",
        "ingredients": {"laitier": ["fromage"], "legumes": ["tomate", "concombre", "celeri"]},
        "proteines": 15, "kcal": 350,
    },
}


def load_deals(store_slug):
    """Load deals from a store's JSON file."""
    deals = []
    today = datetime.now()
    days_since_thursday = (today.weekday() - 3) % 7
    last_thursday = today - timedelta(days=days_since_thursday)
    date_str = last_thursday.strftime("%Y-%m-%d")
    end_str = (last_thursday + timedelta(days=6)).strftime("%Y-%m-%d")
    
    path = os.path.join(STORAGE_DIR, f"{store_slug}-{date_str}-{end_str}.json")
    if os.path.exists(path):
        with open(path) as f:
            store_deals = json.load(f)
            for d in store_deals:
                d['store'] = store_slug
                deals.append(d)
    return deals


def generate_meal_plan():
    """Génère un plan de repas optimisé pour la semaine."""
    maxi = load_deals('maxi')
    superc = load_deals('super-c')
    all_deals = maxi + superc
    
    # Catégoriser
    proteins = [d for d in all_deals if d.get('category') in PROTEIN_CATEGORIES]
    produces = [d for d in all_deals if d.get('category') in PRODUCE_CATEGORIES]
    
    # Meilleurs deals protéines (moins chers)
    sorted_proteins = sorted(proteins, key=lambda d: d['price'])
    sorted_produces = sorted(produces, key=lambda d: d['price'])
    
    # Générer 7 dîners
    dinners = []
    used_recipes = set()
    
    days = ["Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi", "Dimanche"]
    
    for i, day in enumerate(days):
        best_recipe = None
        best_recipe_key = None
        best_score = -1
        
        for recipe_key, recipe in RECIPES.items():
            if recipe_key in used_recipes:
                continue
            score = 0
            
            # Score basé sur les ingrédients en spécial
            for cat, ingredients in recipe['ingredients'].items():
                for ing in ingredients:
                    for d in all_deals:
                        if ing.lower() in d['product'].lower():
                            score += 10 - d['price']
                            break
            
            if score > best_score:
                best_score = score
                best_recipe = recipe
                best_recipe_key = recipe_key
        
        if best_recipe:
            used_recipes.add(best_recipe_key)
            
            # Trouver les ingrédients en spécial pour cette recette
            recipe_deals = []
            for d in all_deals:
                for cat, ings in best_recipe['ingredients'].items():
                    for ing in ings:
                        if ing.lower() in d['product'].lower() and d not in recipe_deals:
                            recipe_deals.append(d)
            
            dinners.append({
                'day': day,
                'recipe': best_recipe['name'],
                'proteines': best_recipe['proteines'],
                'kcal': best_recipe['kcal'],
                'deals': recipe_deals[:5],
                'total_cost': sum(d['price'] for d in recipe_deals[:5]),
                'store_breakdown': list(set(d['store'] for d in recipe_deals[:5])),
            })
        else:
            dinners.append({
                'day': day,
                'recipe': 'Repas libre',
                'proteines': 0,
                'kcal': 0,
                'deals': [],
                'total_cost': 0,
                'store_breakdown': [],
            })
    
    # Calculer le total
    total_cost = sum(d['total_cost'] for d in dinners if d['total_cost'])
    
    return {
        'generated_at': datetime.now().isoformat(),
        'week': f"{days[0]} {datetime.now().strftime('%d %B')}",
        'top_proteins': [{'product': d['product'], 'price': d['price'], 'store': d['store']} 
                        for d in sorted_proteins[:5]],
        'top_produces': [{'product': d['product'], 'price': d['price'], 'store': d['store']} 
                        for d in sorted_produces[:5]],
        'dinners': dinners,
        'total_cost': round(total_cost, 2),
        'total_proteins': sum(d['proteines'] for d in dinners),
    }


def format_telegram(plan):
    """Format meal plan for Telegram."""
    lines = []
    lines.append("🍽️ **Plan repas de la semaine**")
    lines.append(f"📅 Semaine du {plan['week']}")
    lines.append("")
    
    # Top deals
    lines.append("**🔥 Meilleurs spéciaux protéines:**")
    for p in plan['top_proteins'][:3]:
        store_icon = "🏪" if p['store'] == 'maxi' else "🏪"
        lines.append(f"  • {p['product']}: **{p['price']}$** ({p['store'].title()})")
    
    lines.append("")
    lines.append("**🍎 Top fruits/légumes:**")
    for p in plan['top_produces'][:3]:
        lines.append(f"  • {p['product']}: **{p['price']}$** ({p['store'].title()})")
    
    # Meal plan
    lines.append("")
    lines.append("**📋 Dîners de la semaine:**")
    for d in plan['dinners']:
        cost_str = f" ({d['total_cost']:.1f}$)" if d['total_cost'] > 0 else ""
        stores_str = f" {' '.join(s.title() for s in d['store_breakdown'])}" if d['store_breakdown'] else ""
        lines.append(f"  {d['day']}: {d['recipe']}{cost_str}{stores_str}")
    
    # Stats
    lines.append("")
    lines.append(f"💰 **Total estimé:** {plan['total_cost']}$ pour la semaine")
    lines.append(f"💪 **Protéines totales:** {plan['total_proteins']}g")
    lines.append("")
    lines.append("👨‍🍳 _Basé sur les spéciaux Maxi + Super C cette semaine_")
    
    return "\n".join(lines)


def save_plan(plan):
    """Save meal plan to JSON."""
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    today = datetime.now().strftime("%Y-%m-%d")
    path = os.path.join(OUTPUT_DIR, f"mealplan-{today}.json")
    with open(path, 'w') as f:
        json.dump(plan, f, indent=2, ensure_ascii=False)
    print(f"✅ Plan repas sauvegardé: {path}")
    return path


def send_telegram(message):
    """Send via Telegram (uses OpenClaw gateway)."""
    # Write to a file that the health-dashboard can pick up
    today = datetime.now().strftime("%Y-%m-%d")
    path = os.path.join(OUTPUT_DIR, f"telegram-msg-{today}.txt")
    with open(path, 'w') as f:
        f.write(message)
    print(f"📤 Message Telegram prêt: {path}")
    print()
    print(message)
    return path


if __name__ == "__main__":
    import argparse
    parser = argparse.ArgumentParser(description="Générateur de plan repas")
    parser.add_argument("--save", action="store_true", help="Sauvegarder le plan")
    parser.add_argument("--telegram", action="store_true", help="Préparer message Telegram")
    args = parser.parse_args()
    
    plan = generate_meal_plan()
    
    if args.save:
        save_plan(plan)
    
    if args.telegram or not (args.save):
        msg = format_telegram(plan)
        send_telegram(msg)
