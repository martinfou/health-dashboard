#!/usr/bin/env python3
"""
💹 Price Intelligence — Analyse les prix des spéciaux et dit si un deal est vraiment bon.

Usage:
  python3 scripts/price-intel.py                                # Analyse deals actuels
  python3 scripts/price-intel.py --product "porc"               # Historique du porc
  python3 scripts/price-intel.py --store super-c --best          # Meilleurs deals chez Super C
  python3 scripts/price-intel.py --update                        # Scrape + update historique
  python3 scripts/price-intel.py --question "Le poulet est-il en spécial intéressant cette semaine?"
"""

import json
import os
import sys
from datetime import datetime, timedelta
from collections import defaultdict

STORAGE_DIR = os.path.join(os.path.dirname(__file__), "..", "storage", "app", "grocery")
HISTORY_DIR = os.path.join(os.path.dirname(__file__), "..", "storage", "app", "price-history")

# Seuils pour qualifier un deal
THRESHOLDS = {
    'EXCELLENT': 0.30,   # 30%+ sous la moyenne = deal de fou
    'GOOD': 0.15,         # 15-30% sous = bon deal
    'AVERAGE': 0.05,      # 5-15% sous = correct
    'WEAK': -0.05,        # -5% à +5% = bof
    'BAD': -999,          # Plus cher que la moyenne = pas un deal
}

class PriceIntelligence:
    def __init__(self):
        self.history = {}  # (store, product, unit) -> [prices]
        os.makedirs(HISTORY_DIR, exist_ok=True)
        self._load_history()
    
    def _load_history(self):
        """Load all historical price data."""
        for fname in os.listdir(HISTORY_DIR):
            if fname.endswith('.json'):
                path = os.path.join(HISTORY_DIR, fname)
                with open(path) as f:
                    batch = json.load(f)
                for record in batch:
                    key = (record['store'], record['product'], record.get('unit', ''))
                    self.history.setdefault(key, []).append({
                        'price': record['sale_price'],
                        'regular': record.get('regular_price'),
                        'date': record['valid_from'],
                        'store': record['store'],
                    })
    
    def save_batch(self, records):
        """Save a batch of price records to history."""
        today = datetime.now().strftime('%Y-%m-%d')
        path = os.path.join(HISTORY_DIR, f"prices-{today}.json")
        
        # Load existing from today if any
        existing = []
        if os.path.exists(path):
            with open(path) as f:
                existing = json.load(f)
        
        existing.extend(records)
        
        with open(path, 'w') as f:
            json.dump(existing, f, indent=2, ensure_ascii=False)
        
        # Also update in-memory
        for r in records:
            key = (r['store'], r['product'], r.get('unit', ''))
            self.history.setdefault(key, []).append(r)
        
        print(f"  ✅ {len(records)} records sauvegardés dans l'historique")
    
    def get_stats(self, product_search, store_filter=None):
        """Get price statistics for a product."""
        results = []
        
        for (store, product, unit), prices in self.history.items():
            if product_search.lower() not in product.lower():
                continue
            if store_filter and store_filter.lower() not in store.lower():
                continue
            
            sale_prices = [p['price'] for p in prices]
            regular_prices = [p['regular'] for p in prices if p.get('regular')]
            
            avg_sale = sum(sale_prices) / len(sale_prices) if sale_prices else 0
            min_sale = min(sale_prices) if sale_prices else 0
            max_sale = max(sale_prices) if sale_prices else 0
            samples = len(sale_prices)
            
            avg_regular = sum(regular_prices) / len(regular_prices) if regular_prices else None
            
            # Dernière observation
            latest = prices[-1] if prices else None
            
            results.append({
                'store': store.title(),
                'product': product,
                'unit': unit,
                'avg_sale': round(avg_sale, 2),
                'min_sale': min_sale,
                'max_sale': max_sale,
                'samples': samples,
                'avg_regular': round(avg_regular, 2) if avg_regular else None,
                'latest_price': latest['price'] if latest else None,
                'latest_date': latest['date'] if latest else None,
            })
        
        return sorted(results, key=lambda r: -r['samples'])
    
    def rate_deal(self, product, sale_price, store=None):
        """Rate a deal: is it REALLY a good deal?"""
        stats = self.get_stats(product, store)
        if not stats:
            return {'rating': 'NO_DATA', 'message': f"⚠️ Aucun historique pour '{product}' — scrape la semaine prochaine pour comparer!"}
        
        # Use the best matching product
        s = stats[0]
        avg = s['avg_sale']
        
        if avg == 0:
            return {'rating': 'NO_DATA', 'message': f"⚠️ Pas assez de données pour {product}"}
        
        diff_pct = (avg - sale_price) / avg
        
        if diff_pct >= THRESHOLDS['EXCELLENT']:
            rating = 'EXCELLENT'
            msg = f"🔥 **EXCELLENT DEAL!** {sale_price}$ vs moyenne {avg}$ ({(diff_pct*100):.0f}% moins cher)"
        elif diff_pct >= THRESHOLDS['GOOD']:
            rating = 'GOOD'
            msg = f"👍 **Bon deal.** {sale_price}$ vs moyenne {avg}$ ({(diff_pct*100):.0f}% d'économie)"
        elif diff_pct >= THRESHOLDS['AVERAGE']:
            rating = 'AVERAGE'
            msg = f"✅ **Correct.** {sale_price}$ vs moyenne {avg}$ ({(diff_pct*100):.0f}% d'économie)"
        elif diff_pct >= THRESHOLDS['WEAK']:
            rating = 'WEAK'
            msg = f"😐 **Moyen.** Seulement {(diff_pct*100):.0f}% sous la moyenne de {avg}$ — pas fou"
        else:
            rating = 'BAD'
            msg = f"❌ **Pas un deal.** {sale_price}$ est PLUS CHER que la moyenne de {avg}$"
        
        # Ajouter contexte
        extra = []
        if s['min_sale'] > 0:
            extra.append(f"Meilleur prix ever: {s['min_sale']}$")
        if s['samples'] > 1:
            extra.append(f"Basé sur {s['samples']} semaines d'historique")
        if s['avg_regular']:
            reg_save = ((s['avg_regular'] - sale_price) / s['avg_regular'] * 100) if s['avg_regular'] > 0 else 0
            extra.append(f"Économie vs prix régulier: {reg_save:.0f}%")
        
        return {
            'rating': rating,
            'message': msg,
            'detail': ' | '.join(extra),
            'savings_vs_avg': f"{(diff_pct*100):.0f}%",
            'avg_price': avg,
        }
    
    def analyze_current_deals(self):
        """Analyze all current deals and rate them."""
        maxi_deals = self._load_deals('maxi')
        superc_deals = self._load_deals('super-c')
        all_deals = maxi_deals + superc_deals
        
        print("\n" + "=" * 65)
        print("  💹 PRICE INTELLIGENCE — Analyse des spéciaux de la semaine")
        print("=" * 65)
        
        for store in ['maxi', 'super-c']:
            deals = self._load_deals(store)
            if not deals:
                continue
            
            print(f"\n  🏪 {store.title()}")
            print(f"  {'─' * 60}")
            
            rated = []
            for d in deals:
                rating = self.rate_deal(d['product'], d['price'], store)
                rated.append((d, rating))
            
            # Sort by rating
            rating_order = {'EXCELLENT': 0, 'GOOD': 1, 'AVERAGE': 2, 'WEAK': 3, 'BAD': 4, 'NO_DATA': 5}
            rated.sort(key=lambda x: rating_order.get(x[1]['rating'], 99))
            
            for d, r in rated:
                icon = {'EXCELLENT': '🔥', 'GOOD': '👍', 'AVERAGE': '✅', 'WEAK': '😐', 'BAD': '❌', 'NO_DATA': '📝'}.get(r['rating'], '❓')
                print(f"  {icon} {d['product']:40s} {d['price']:>5.2f}$")
                if r['rating'] != 'NO_DATA':
                    print(f"     {r['message']}")
                    if r.get('detail'):
                        print(f"     {r['detail']}")
        
        # Summary
        print(f"\n  {'─' * 60}")
        all_rated = []
        for store in ['maxi', 'super-c']:
            for d in self._load_deals(store):
                all_rated.append((d, self.rate_deal(d['product'], d['price'], store)))
        
        excellent = sum(1 for _, r in all_rated if r['rating'] == 'EXCELLENT')
        good = sum(1 for _, r in all_rated if r['rating'] == 'GOOD')
        weak = sum(1 for _, r in all_rated if r['rating'] == 'WEAK')
        bad = sum(1 for _, r in all_rated if r['rating'] == 'BAD')
        nodata = sum(1 for _, r in all_rated if r['rating'] == 'NO_DATA')
        
        print(f"  🔥 {excellent} excellents | 👍 {good} bons | 😐 {weak} moyens | ❌ {bad} à éviter | 📝 {nodata} sans historique")
    
    def _load_deals(self, store_slug):
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
    
    def update_history_from_current(self):
        """Save current deals to price history."""
        all_records = []
        for store in ['maxi', 'super-c']:
            deals = self._load_deals(store)
            for d in deals:
                record = {
                    'store': store,
                    'product': d['product'],
                    'category': d.get('category', 'epicerie'),
                    'sale_price': d['price'],
                    'regular_price': d.get('regular_price'),
                    'unit': d.get('unit', ''),
                    'store_brand': d.get('store_brand', ''),
                    'is_bio': d.get('is_bio', False),
                    'valid_from': d.get('valid_from', last_thursday),
                    'valid_until': d.get('valid_until', end_str),
                }
                all_records.append(record)
        
        if all_records:
            self.save_batch(all_records)
        return all_records


def answer_question(pi, question):
    """Répond à une question sur les prix."""
    question = question.lower()
    
    if "porc" in question or "pork" in question:
        stats = pi.get_stats("porc")
        if stats:
            s = stats[0]
            print(f"\n  🐷 **{s['product']}**")
            print(f"  Prix moyen en spécial: {s['avg_sale']}$")
            print(f"  Meilleur prix ever: {s['min_sale']}$")
            print(f"  Prix actuel: {s['latest_price']}$")
            print(f"  Basé sur {s['samples']} semaines d'historique")
        else:
            print("  Pas encore d'historique pour le porc")
    
    elif "poulet" in question or "chicken" in question:
        stats = pi.get_stats("poulet")
        # Similar...
    
    else:
        print("  Je peux chercher: porc, poulet, saumon, boeuf, ou n'importe quel produit!")


if __name__ == "__main__":
    import argparse
    parser = argparse.ArgumentParser(description="Price Intelligence - Analyse les deals")
    parser.add_argument("--update", action="store_true", help="Ajouter les deals actuels à l'historique")
    parser.add_argument("--product", help="Analyse l'historique d'un produit")
    parser.add_argument("--store", help="Filtrer par enseigne")
    parser.add_argument("--best", action="store_true", help="Meilleurs deals")
    parser.add_argument("--question", help="Poser une question")
    
    args = parser.parse_args()
    pi = PriceIntelligence()
    
    if args.update:
        pi.update_history_from_current()
    
    if args.product:
        stats = pi.get_stats(args.product, args.store)
        if stats:
            print(f"\n  📊 Historique pour '{args.product}':\n")
            for s in stats:
                print(f"  {s['store']:10s} | {s['product']:40s} | avg: {s['avg_sale']:>5.2f}$ | min: {s['min_sale']:>5.2f}$ | max: {s['max_sale']:>5.2f}$ | {s['samples']} semaines")
        else:
            print(f"\n  Aucun historique pour '{args.product}'")
    
    elif args.question:
        answer_question(pi, args.question)
    
    elif args.best:
        pi.analyze_current_deals()
    
    elif not args.update:
        pi.analyze_current_deals()
