import urllib.request
import json

base = "https://mehedihasan.au/kent/cpro306/g1/api"
endpoints = [
    ("/menu/categories.php", "categories"),
    ("/menu/items.php", "menuItems"),
    ("/inventory/inventory.php", "inventory"),
    ("/tables/tables.php", "tables"),
    ("/orders/orders.php", "orders"),
    ("/reservations/reservations.php", "reservations"),
    ("/customers/customers.php", "customers"),
    ("/payments/payments.php", "transactions"),
    ("/discounts/discounts.php", "discounts"),
    ("/feedback/feedback.php", "feedback"),
    ("/employees/employees.php", "staff"),
    ("/reports/audit.php", "audit"),
    ("/orders/orders.php?action=next_num", "nextOrderNum")
]

for ep, name in endpoints:
    url = base + ep
    try:
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
        res = urllib.request.urlopen(req, timeout=5)
        raw = res.read().decode('utf-8')
        try:
            data = json.loads(raw)
            print(f"OK [200 JSON]: {name} ({ep}) -> success={data.get('success')}, count={len(data.get('data', [])) if isinstance(data.get('data'), list) else 'obj'}")
        except Exception as e:
            print(f"WARN [Non-JSON]: {name} ({ep}) -> {raw[:150]}")
    except Exception as e:
        print(f"FAIL [Error]: {name} ({ep}) -> {e}")
