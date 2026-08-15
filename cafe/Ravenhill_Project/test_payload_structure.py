import urllib.request
import json

base = "https://mehedihasan.au/kent/cpro306/g1/api"
endpoints = [
    ("/inventory/inventory.php", "inventory"),
    ("/orders/orders.php", "orders"),
    ("/reservations/reservations.php", "reservations"),
    ("/feedback/feedback.php", "feedback"),
    ("/employees/employees.php", "staff"),
]

for ep, name in endpoints:
    url = base + ep
    try:
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
        res = urllib.request.urlopen(req, timeout=5)
        raw = res.read().decode('utf-8')
        data = json.loads(raw)
        d = data.get('data')
        print(f"\n--- {name} ({ep}) ---")
        if isinstance(d, dict):
            print("Dict keys:", list(d.keys()))
            for k, v in d.items():
                if isinstance(v, list):
                    print(f"  {k}: list of {len(v)} items (sample 1: {v[0] if v else 'empty'})")
                else:
                    print(f"  {k}: {type(v)} = {v}")
        elif isinstance(d, list):
            print(f"List of {len(d)} items (sample 1: {d[0] if d else 'empty'})")
        else:
            print("Data is:", type(d), d)
    except Exception as e:
        print(f"FAIL: {name} -> {e}")
