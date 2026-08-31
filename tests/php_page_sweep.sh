#!/bin/bash
# Quick regression sweep: logs in as admin & customer, fetches key pages, checks HTTP status + PHP notices/warnings.
BASE="https://ice-store-hub.preview.emergentagent.com"
AJAR=/tmp/admin_cookies.txt; CJAR=/tmp/cust_cookies.txt
rm -f $AJAR $CJAR

login() { # jar url field token_page
  local jar=$1 url=$2 data=$3
  local tok=$(curl -s -c $jar "$url" | grep -o 'name="csrf" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
  curl -s -b $jar -c $jar -o /dev/null -w "login[$url] %{http_code}\n" -X POST "$url" --data "csrf=$tok&$data"
}

login $AJAR "$BASE/admin/login.php" "email=admin@chipi.id&password=admin123"
login $CJAR "$BASE/customer/login.php" "login=budi@mail.com&password=customer123"

check() { # jar path
  local jar=$1 path=$2
  local out=$(curl -s -b $jar -w "\nHTTP:%{http_code}" "$BASE$path")
  local code=$(echo "$out" | tail -1 | cut -d: -f2)
  local bad=$(echo "$out" | grep -oiE "(Fatal error|Warning</b>|Warning:|Notice:|Deprecated:|Uncaught)" | sort -u | tr '\n' ',')
  echo "$path -> $code ${bad:+PHP_ISSUE[$bad]}"
}

echo "--- public ---"
for p in / /customer/products.php /customer/product.php?id=1; do check $CJAR "$p"; done
echo "--- customer ---"
for p in /customer/dashboard.php /customer/cart.php /customer/orders.php /customer/order-detail.php?id=4 /customer/receipts.php /customer/address.php /customer/profile.php; do check $CJAR "$p"; done
echo "--- admin ---"
for p in /admin/index.php /admin/orders.php /admin/order-detail.php?id=4 /admin/products.php /admin/product-form.php /admin/categories.php /admin/brands.php /admin/customers.php /admin/promos.php /admin/reports.php?range=today /admin/settings.php /admin/import.php /admin/export.php /admin/profile.php /admin/notif.php; do check $AJAR "$p"; done
