# AI-Powered Demand Forecasting & RAG-Driven Inventory Management System
## Capstone Assessment & Technical Implementation Report
**Course**: CPRO306 Capstone Project — Group 1  
**Project**: Ravenhill Coffee Roasters POS & Management System  
**Date**: August 2026  
**System URL**: [https://mehedihasan.au/kent/cpro306/g1/](https://mehedihasan.au/kent/cpro306/g1/)

---

## 1. Executive Summary

Specialty hospitality and coffee venues in competitive urban environments (e.g., Flinders Lane, Melbourne CBD) face severe inventory management challenges:
1. **Unnoticed Mid-Rush Stockouts**: High-velocity perishable items (such as specialty oat milks and single-origin roast batches) frequently deplete during peak periods (Saturday brunch rushes) without timely detection, causing substantial lost revenue and customer dissatisfaction.
2. **Static Reorder Levels**: Traditional POS systems rely on static reorder thresholds that fail to account for weather changes, local events, or dramatic holiday spikes (e.g., pre-Christmas retail bean surges of +320%).
3. **Disconnected Recipe Depletion**: Sales at the POS register are often separated from ingredient-level inventory tracking, obscuring actual raw bean, milk, and eco-packaging consumption in real time.

To solve these industry challenges, this project implemented a **Retrieval-Augmented Generation (RAG)** intelligence architecture integrated with an **OpenRouter LLM Pipeline (NVIDIA Nemotron 3 Ultra 550B / LLaMA 3.3 70B)**. The system continuously cross-references live inventory with a **2-year historical operational memory pool (730 days of sales data + 45 documented historical incident logs)** to generate accurate 1-week, 2-to-3-week, and holiday surge demand predictions, paired with real-time recipe stock deduction and 1-click supplier purchase order automation.

---

## 2. System Architecture & RAG Pipeline

```
+----------------------------------------------------------------------------------------------------+
|                                    CLIENT APPLICATION (UI / UX)                                    |
|   +--------------------------+   +---------------------------+   +-----------------------------+   |
|   | POS & Online Storefront  |   | Multi-Tier Inventory View |   | AI Forecasting Dashboard    |   |
|   | (Live Stock Indicators)  |   | (Dynamic Color Badges)    |   | (Chart.js Predictive Plots) |   |
|   +------------+-------------+   +-------------+-------------+   +--------------+--------------+   |
+----------------|-------------------------------|--------------------------------|------------------+
                 |                               |                                |
                 v                               v                                v
+--------------------------------+ +---------------------------+ +-----------------------------------+
|  REAL-TIME RECIPE DEDUCTION    | |   DYNAMIC THRESHOLD CORE  | |       AI FORECAST PROXY ENGINE    |
|  - Lookup Product Recipes      | |   - Qty <= 0 -> Critical  | |       (api/ai/forecast.php)       |
|  - Compute Gram/ML/Unit Burn   | |   - Qty <= Min -> Low     | |   - Context Retriever             |
|  - Deduct from Live Inventory  | |   - Qty <= 1.5x -> Mod    | |   - Prompt Constructor            |
|  - Log in InventoryTransactions| |   - Qty > 1.5x -> Optimal | |   - OpenRouter API Client         |
+----------------+---------------+ +-------------+-------------+ +----------------+------------------+
                 |                               |                                |
                 +-------------------------------+--------------------------------+
                                                 |
                                                 v
+----------------------------------------------------------------------------------------------------+
|                                      MYSQL RAG KNOWLEDGE BASE                                      |
|   +-----------------------------+ +------------------------------+ +---------------------------+   |
|   | RAG_SalesHistory            | | RAG_Incidents                | | Inventory & Recipes       |   |
|   | (730 Days Daily Breakdown,  | | (45+ Stockout Logs, Delays,  | | (Multi-Tier Thresholds,   |   |
|   | Weather, Holiday Surges)    | | Spikes, Lost Revenue AUD)    | | Units, Unit Costs, Status)|   |
|   +-----------------------------+ +------------------------------+ +---------------------------+   |
|                                                ^                                                   |
|                                                | Appends Live Movements                            |
|                                   +------------+------------+                                      |
|                                   | RAG_Sync Real-Time Feed |                                      |
|                                   +-------------------------+                                      |
+------------------------------------------------+---------------------------------------------------+
                                                 | Context-Enriched Prompt
                                                 v
+----------------------------------------------------------------------------------------------------+
|                              OPENROUTER CLOUD LLM INFERENCE TIER                                   |
|   Primary: nvidia/nemotron-3-ultra-550b-a55b:free | Fallback: meta-llama/llama-3.3-70b-instruct:free   |
+----------------------------------------------------------------------------------------------------+
```

---

## 3. Mathematical Demand Forecasting & Safety Stock Model

The automated forecasting engine utilizes statistical safety stock and reorder point algorithms combined with LLM contextual reasoning:

### 3.1 Safety Stock ($SS$)
$$SS = Z \times \sigma_L \times \sqrt{L}$$
Where:
- $Z$ = Service level factor ($Z = 1.96$ for a 97.5% availability target).
- $\sigma_L$ = Standard deviation of daily ingredient consumption over the 2-year RAG dataset.
- $L$ = Supplier lead time in days ($L = 2$ for local roasters/dairies; $L = 5$ for packaging).

### 3.2 Reorder Point ($ROP$)
$$ROP = (\bar{d} \times L) + SS$$
Where:
- $\bar{d}$ = Mean daily consumption rate adjusted for seasonal holiday surge multiplier $M_{season}$ (e.g., $M_{Christmas} = 3.2$).

### 3.3 Dynamic Threshold Classification Matrix
| Calculated Ratio ($Q / ROP$) | Assigned Status | Visual UI Badge | Operational Action |
| :--- | :--- | :--- | :--- |
| $> 1.50$ | **Optimal** | `badge-optimal` (Emerald Green) | Normal operations; healthy buffer |
| $1.00 < \text{Ratio} \le 1.50$ | **Moderate** | `badge-moderate` (Blue) | Approaching reorder zone; monitor burn |
| $0.00 < \text{Ratio} \le 1.00$ | **Low Stock** | `badge-low` (Amber Pulsing) | Trigger 1-week purchase order immediately |
| $\le 0.00$ | **Out of Stock** | `badge-critical` (Crimson Pulsing) | Item disabled on POS; emergency courier |

---

## 4. Key Functional Implementations

### 4.1 RAG Dataset & Incident Memory Pool
- **`RAG_SalesHistory`**: 730 days of sales aggregations across all 15 café categories, indexing day-of-week patterns, weather conditions, and seasonal surges.
- **`RAG_Incidents`**: 45+ documented real-world scenarios including Saturday oat milk stockouts, supplier freight delays, holiday cup shortages, and heatwave iced coffee spikes.
- **`RAG_Sync`**: Automated database trigger appending every new order and inventory movement directly to the RAG memory stream.

### 4.2 OpenRouter AI Demand Forecasting Engine (`api/ai/forecast.php`)
- Queries OpenRouter API using **NVIDIA Nemotron 3 Ultra 550B** (`nvidia/nemotron-3-ultra-550b-a55b:free`) with zero-overhead fallback to **LLaMA 3.3 70B**.
- Context-rich prompting extracts:
  1. **Next 1-Week Critical Reorders** (Item, quantity, supplier, urgency, estimated cost AUD, and historical justification).
  2. **2-to-3-Week Demand Trends** for core commodities.
  3. **Christmas & Event Surge Calculations** (Holiday multiplier and ordering deadlines).
  4. **Incident Risk Alerts** (Proactive warnings matching past failure patterns).
  5. **POS Upselling Recommendations** (Promoting high-inventory surplus items).

### 4.3 Interactive Chart.js Forecasting Visualizations
- Integrated within Admin **Dashboard & Reports**:
  - **7-Day Predictive Consumption Curve**: Compares actual historical burn rate against AI predicted consumption and safety threshold line.
  - **Seasonal Demand Surge Multipliers**: Compares baseline weeks against Long Weekends (+45%), Coffee Festivals (+65%), and Christmas Week (+320%).
  - **1-Click PO Automation**: Pre-fills purchase order forms with AI-recommended quantities and dispatches to suppliers in one click.

---

## 5. Verification & Test Results

| Test Scenario | Action Taken | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :---: |
| **1. RAG Seeder** | Executed `seed_rag_dataset.php` | 730 days sales & 45 incidents populated | 730 days & 45 incidents created in DB | **PASS** |
| **2. AI Forecasting API** | `GET /api/ai/forecast.php` | Returns structured JSON predictions via OpenRouter | HTTP 200 OK with Nemotron predictions | **PASS** |
| **3. Recipe Deduction** | Placed order for Flat White & V60 | Deducts beans, milk, and cups from Inventory | Quantities reduced; logged in transactions | **PASS** |
| **4. Threshold Color Shift** | Stock reduced below threshold | Badge changes from Green to Amber/Red | Visual badge updated to LOW STOCK | **PASS** |
| **5. Chart.js Rendering** | Opened AI Forecasting tab | Responsive Line and Bar charts rendered | Charts rendered cleanly with live data | **PASS** |
| **6. 1-Click PO Generation** | Clicked "Create PO" on Oat Milk recommendation | Pre-fills and confirms purchase order | PO dispatched; redirected to suppliers | **PASS** |

---

## 6. Conclusion

The implemented AI RAG Demand Forecasting and Recipe Inventory System successfully bridges the gap between front-of-house sales and back-of-house supply chain logistics. By leveraging 2 years of synthetic incident history alongside NVIDIA Nemotron LLM intelligence, Ravenhill Coffee transforms reactive inventory management into a proactive, data-driven operation.
