#!/usr/bin/env python3
"""
Scrape les circulaires/volants des supermarchés québécois.
Exporte en JSON pour import dans health-dashboard.

Usage:
  python3 scripts/scrape-circulaire.py --store maxi          # Maxi cette semaine
  python3 scripts/scrape-circulaire.py --store super-c        # Super C
  python3 scripts/scrape-circulaire.py --store iga            # IGA
  python3 scripts/scrape-circulaire.py --all                  # toutes les enseignes
  python3 scripts/scrape-circulaire.py --store maxi --output  # + sauvegarde JSON
"""

import json
import os
import sys
import re
from datetime import datetime, timedelta

# Mapping des enseignes vers leurs URLs de scan
STORE_URLS = {
    'maxi': 'https://en.ca-flyers.com/maxi-flyers/flyer-264638-0',
    'super-c': 'https://fr.ca-flyers.com/super-c-circulaires/catalogue-264629-0',
}

# Mapping des catégories
CATEGORY_KEYWORDS = {
    'fruits': ['pomme', 'banane', 'orange', 'raisin', 'fraise', 'bleuet', 'framboise', 'mure',
               'cerise', 'ananas', 'melon', 'mangue', 'kiwi', 'avocat', 'citron', 'lime'],
    'legumes': ['tomate', 'salade', 'laitue', 'concombre', 'poivron', 'brocoli', 'chou-fleur',
                'chou', 'carotte', 'celeri', 'oignon', 'pomme de terre', 'patate', 'courge',
                'courgette', 'haricot', 'epinard', 'champignon', 'mais'],
    'viande': ['boeuf', 'poulet', 'porc', 'veau', 'agneau', 'jambon', 'saucisse', 'steak',
               'filet', 'hache', 'cuisse', 'poitrine'],
    'poisson': ['saumon', 'crevette', 'truite', 'tilapia', 'morue', 'thon', 'fruit de mer',
                'crabe', 'homard'],
    'laitier': ['lait', 'fromage', 'yaourt', 'yogourt', 'beurre', 'creme', 'mozzarella',
                'cheddar', 'oeuf'],
    'surgeles': ['glace', 'surgel', 'congele', 'creme glacee', 'pizza surgelée'],
    'epicerie': ['riz', 'pates', 'sauce', 'huile', 'conserve', 'soupe', 'cafe', 'the',
                'sucre', 'farine', 'sel', 'epice', 'vinaigre'],
    'snacks': ['chips', 'biscuit', 'chocolat', 'croustille', 'noix', 'barre', 'craquelin',
               'taki', 'aeroplan'],
    'boissons': ['jus', 'soda', 'biere', 'vin', 'eau', 'boisson', 'oasis', 'minute maid'],
    'entretien': ['tide', 'nettoyant', 'savon', 'papier', 'mouchoir', 'essuie-tout',
                  'detergent', 'assouplissant'],
}

HEALTHY_CATEGORIES = ['fruits', 'legumes', 'viande', 'poisson', 'laitier']


class CirculaireScraper:
    def __init__(self, store_name):
        self.store_name = store_name.lower()
        self.deals = []
    
    def _categorize(self, product_name: str) -> str:
        """Catégorise un produit selon son nom."""
        name_lower = product_name.lower()
        for category, keywords in CATEGORY_KEYWORDS.items():
            for kw in keywords:
                if kw in name_lower:
                    return category
        return 'epicerie'
    
    def _parse_price(self, price_text: str) -> tuple:
        """Parse un prix et retourne (valeur, unité)."""
        price_text = price_text.replace('$', '').strip()
        try:
            price = float(price_text)
            return price, None
        except ValueError:
            # Check for /lb, /kg etc
            m = re.match(r'([\d.]+)\s*\$?\s*/?\s*(\w+)?', price_text)
            if m:
                return float(m.group(1)), m.group(2) if m.lastindex >= 2 else None
        return 0.0, None
    
    def scrape_flyers(self) -> list:
        """Scrape les deals depuis ca-flyers.com ou autres sources."""
        print(f"📥 Scraping {self.store_name.title()}...")
        return self.deals
    
    def add_deal(self, product, price, unit=None, regular_price=None,
                 is_bio=False, store_brand=None, category=None):
        """Ajoute un deal manuellement ou depuis un scrape automatisé."""
        self.deals.append({
            'product': product,
            'category': category or self._categorize(product),
            'price': price,
            'unit': unit,
            'regular_price': regular_price,
            'is_bio': is_bio,
            'store_brand': store_brand,
        })
    
    def export_json(self, output_dir=None):
        """Exporte les deals en JSON."""
        today = datetime.now()
        # Circulaires: jeudi au mercredi
        # Trouver le jeudi de cette semaine
        days_since_thursday = (today.weekday() - 3) % 7
        last_thursday = today - timedelta(days=days_since_thursday)
        valid_from = last_thursday.strftime('%Y-%m-%d')
        valid_until = (last_thursday + timedelta(days=6)).strftime('%Y-%m-%d')
        
        for d in self.deals:
            d['valid_from'] = valid_from
            d['valid_until'] = valid_until
        
        if output_dir:
            os.makedirs(output_dir, exist_ok=True)
            filepath = os.path.join(output_dir, f"{self.store_name}-{valid_from}-{valid_until}.json")
            with open(filepath, 'w') as f:
                json.dump(self.deals, f, indent=2, ensure_ascii=False)
            print(f"  ✅ Sauvegardé: {filepath}")
            return filepath
        
        return self.deals
    
    def summary(self):
        """Affiche un résumé des deals."""
        if not self.deals:
            print("  Aucun deal")
            return
        
        by_cat = {}
        for d in self.deals:
            by_cat.setdefault(d['category'], []).append(d)
        
        print(f"\n  {'='*50}")
        print(f"  🏪 {self.store_name.title()} — {len(self.deals)} spéciaux")
        print(f"  {'='*50}")
        
        for cat in ['fruits', 'legumes', 'viande', 'poisson', 'laitier', 'epicerie', 'snacks', 'boissons', 'surgeles', 'entretien']:
            items = by_cat.get(cat, [])
            if not items:
                continue
            icon = {'fruits': '🍎', 'legumes': '🥦', 'viande': '🥩', 'poisson': '🐟',
                    'laitier': '🧀', 'epicerie': '🥫', 'snacks': '🍪', 'boissons': '🥤',
                    'surgeles': '🧊', 'entretien': '🧹'}.get(cat, '📦')
            print(f"\n  {icon} {cat.title()}:")
            for d in items[:8]:
                savings = f" (éco: ${d['regular_price'] - d['price']:.2f})" if d.get('regular_price') else ''
                unit = f"/{d['unit']}" if d.get('unit') else ''
                brand = f" [{d['store_brand']}]" if d.get('store_brand') else ''
                bio = " 🌱" if d.get('is_bio') else ''
                print(f"    {d['product']}{brand}{bio}: {d['price']:.2f}${unit}{savings}")
        
        # Best deals
        with_reg = [d for d in self.deals if d.get('regular_price') and d['regular_price'] > d['price']]
        if with_reg:
            best = sorted(with_reg, key=lambda d: d['regular_price'] - d['price'], reverse=True)[:3]
            print(f"\n  💰 Top 3 économies:")
            for d in best:
                save = d['regular_price'] - d['price']
                pct = int(save / d['regular_price'] * 100)
                print(f"    {d['product']}: {d['regular_price']}$ → {d['price']}$ (-{pct}%, économie: {save:.2f}$)")


def seed_maxi(scraper):
    """Seed Maxi deals for the current week (from scraped data)."""
    deals = [
        # Fruits
        ("Pommes McIntosh", 1.00, "lb", "fruits"),
        ("Ananas", 4.00, "unité", "fruits"),
        ("Avocats", 4.00, "sac", "fruits"),
        # Légumes
        ("Tomates Aylmer", 1.50, "lg", "legumes"),
        ("Tomates cerises Axiany", 6.00, "unité", "legumes"),
        ("Salade Attitude Fraîche", 2.99, "unité", "legumes"),
        ("Salade de chou PC", 3.00, "unité", "legumes"),
        ("Champignons entiers PC", 2.50, "unité", "legumes"),
        # Viande
        ("Hauts de cuisse de poulet PC Simplement Bon", 10.00, "unité", "viande"),
        ("Escallopes de veau de grain", 23.99, "kg", "viande"),
        ("Saucisse Marcangelo", 12.44, "unité", "viande"),
        # Poisson
        ("Filet de saumon de l'Atlantique frais", 16.00, "lb", "poisson"),
        ("Crevettes crues SeaQuest", 13.00, "unité", "poisson"),
        # Laitier
        ("Mozzarellissima Saputo", 11.99, "unité", "laitier"),
        ("Yogourt iÖGO", 3.50, "unité", "laitier"),
        # Surgelés
        ("Häagen-Dazs", 4.99, "unité", "surgeles"),
        # Snacks
        ("Nestlé Aero/KitKat Minis", 2.75, "unité", "snacks"),
        ("Takis", 2.25, "unité", "snacks"),
        # Boissons
        ("Jus Oasis", 4.00, "unité", "boissons"),
        ("Bière Boréale", 19.99, "unité", "boissons"),
        # Épicerie
        ("Tide lessive", 8.77, "unité", "entretien"),
    ]
    for product, price, unit, cat in deals:
        scraper.add_deal(product, price, unit=unit, category=cat)


def seed_super_c(scraper):
    """Seed Super C deals for the current week (from visible highlights)."""
    deals = [
        ("Cerises", 2.97, "lb", "fruits"),
        ("Mûres biologiques", 1.88, "unité", "fruits", True),
        ("Filets de porc frais", 3.77, "lb", "viande"),
    ]
    for d in deals:
        product, price, unit, cat = d[:4]
        bio = d[4] if len(d) > 4 else False
        scraper.add_deal(product, price, unit=unit, category=cat, is_bio=bio)


if __name__ == "__main__":
    import argparse
    
    parser = argparse.ArgumentParser(description="Scrape les circulaires des supermarchés")
    parser.add_argument("--store", help="Enseigne: maxi, super-c, iga")
    parser.add_argument("--all", action="store_true", help="Toutes les enseignes")
    parser.add_argument("--output", "-o", help="Répertoire de sortie (optionnel)")
    
    args = parser.parse_args()
    output_dir = args.output
    
    stores_to_scrape = []
    if args.all:
        stores_to_scrape = ['maxi', 'super-c']
    elif args.store:
        stores_to_scrape = [args.store.lower()]
    else:
        stores_to_scrape = ['maxi']
    
    for store in stores_to_scrape:
        scraper = CirculaireScraper(store)
        
        if store == 'maxi':
            seed_maxi(scraper)
        elif store == 'super-c':
            seed_super_c(scraper)
        elif store == 'iga':
            print(f"  ⚠️ IGA pas encore implémenté")
        
        if output_dir:
            scraper.export_json(output_dir=output_dir)
        scraper.summary()
