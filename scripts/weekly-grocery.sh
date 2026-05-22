#!/bin/bash
# =============================================================================
# weekly-grocery.sh — Pipeline complet des circulaires (jeudi 06:00)
# =============================================================================
# 1. Scrape les circulaires Maxi + Super C
# 2. Importe dans health-dashboard
# 3. Génère le plan repas
# 4. Envoie notification Telegram
# =============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
LOG_DIR="$PROJECT_DIR/storage/logs"
mkdir -p "$LOG_DIR" "$PROJECT_DIR/storage/app/grocery" "$PROJECT_DIR/storage/app/mealplans"

LOG_FILE="$LOG_DIR/weekly-grocery-$(date +%Y-%m-%d).log"
exec > >(tee -a "$LOG_FILE") 2>&1

echo "=========================================="
echo "  🛒 Weekly Grocery Pipeline"
echo "  $(date '+%Y-%m-%d %H:%M')"
echo "=========================================="

# Step 1: Scrape Maxi
echo ""
echo "[1/4] Scraping Maxi..."
python3 "$SCRIPT_DIR/scrape-circulaire.py" --store maxi --output "$PROJECT_DIR/storage/app/grocery/"

# Step 2: Scrape Super C
echo ""
echo "[2/4] Scraping Super C..."
python3 "$SCRIPT_DIR/scrape-circulaire.py" --store super-c --output "$PROJECT_DIR/storage/app/grocery/"

# Step 3: Import into health-dashboard
echo ""
echo "[3/4] Importing into health-dashboard..."
cd "$PROJECT_DIR"
php artisan grocery:import 2>&1 || echo "  ⚠️ Import SQL via php artisan, fallback to direct..."

# Direct SQL import fallback
python3 -c "
import json, sqlite3, os

db = 'database/database.sqlite'
if not os.path.exists(db):
    print('  DB not found')
    exit()

conn = sqlite3.connect(db)
cur = conn.cursor()

stores = {'maxi': 1, 'super-c': 2}
import glob
for slug, store_id in stores.items():
    for f in sorted(glob.glob(f'storage/app/grocery/{slug}-*.json')):
        with open(f) as fh:
            deals = json.load(fh)
        inserted = 0
        for d in deals:
            try:
                cur.execute('''INSERT OR IGNORE INTO grocery_deals 
                    (grocery_store_id, product, category, price, unit, valid_from, valid_until, is_bio, store_brand, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime(\"now\"), datetime(\"now\"))''',
                    (store_id, d['product'], d['category'], d['price'], d.get('unit',''),
                     d.get('valid_from','2026-05-21'), d.get('valid_until','2026-05-27'),
                     1 if d.get('is_bio') else 0, d.get('store_brand', '')))
                inserted += 1
            except Exception as e:
                pass
        print(f'  ✅ {slug}: {inserted} deals imported')
        break  # Only latest file

conn.commit()
cur.execute('SELECT COUNT(*) FROM grocery_deals WHERE valid_until >= date(\"now\")')
print(f'  📊 Total deals actifs: {cur.fetchone()[0]}')
conn.close()
"

# Step 4: Generate meal plan
echo ""
echo "[4/5] Updating price history..."
python3 "$SCRIPT_DIR/price-intel.py" --update 2>&1 || echo "  ⚠️ Price history update skipped"

echo ""
echo "[5/5] Generating meal plan..."
python3 "$SCRIPT_DIR/meal-plan.py" --save --telegram

echo ""
echo "✅ Weekly grocery pipeline complete!"
echo "📄 Log: $LOG_FILE"
