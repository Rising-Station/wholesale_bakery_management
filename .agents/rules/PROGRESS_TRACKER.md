# Mominul Bekari - Wholesale Distribution System
## Implementation Progress Tracker

> **Core Supply Chain:**
> $$\mathbf{Supplier} \longrightarrow \mathbf{Mominul\ Bekari} \longrightarrow \mathbf{Retailers\ (Organized\ by\ Road)}$$
>
> **Instructions for Developers & AI Agents:**
> 1. Check this file at the start of every session to identify the current active phase and pending tasks.
> 2. Complete tasks phase-by-phase without leaving partial or broken UI/APIs.
> 3. Mark completed tasks with `[x]` and update the **Current Status Summary** table.
> 4. Run verification tests before proceeding to the subsequent phase.

---

## Current Status Summary

| Overall Progress | Current Phase | Last Updated Date | Status |
| :--- | :--- | :--- | :--- |
| **100%** (Completed) | All 10 Phases Fully Implemented & Verified | 2026-10-25 | 🟢 Complete |

---

## Phase Checklist & Milestones

### [x] Phase 1: Foundation, UI Shell & Core Helpers
- [x] Initialize Laravel project with Inertia.js + Vue.js 3 (`<script setup>`).
- [x] Configure PrimeVue v4 with styled theme (Aura) & PrimeIcons.
- [x] Create base layout: Sidebar, Header/Topbar, User dropdown, Flash toast container.
- [x] Create reusable PHP Enums:
  - `app/Enums/MemoStatus.php`
  - `app/Enums/PurchaseStatus.php`
  - `app/Enums/DeliveryStatus.php`
  - `app/Enums/PackagingType.php`
  - `app/Enums/DamageType.php`
  - `app/Enums/ReturnType.php`
  - `app/Enums/StockMovementType.php`
  - `app/Enums/StockLocationType.php`
  - `app/Enums/PaymentMethod.php`
  - `app/Enums/ExpenseCategoryType.php`
  - `app/Enums/TripStatus.php`
- [x] Implement reusable Traits:
  - `app/Traits/ApiResponseTrait.php`
  - `app/Traits/FileUploadTrait.php`
  - `app/Traits/HasAuditTrail.php`
  - `app/Traits/CurrencyFormatterTrait.php`
- [x] Build reusable PrimeVue UI wrapper components:
  - AppDataTable (pagination, search, export)
  - ConfirmModal
  - CurrencyInput
  - StatusBadge
- **Phase 1 Verification:**
  - [x] App loads cleanly at `/` or `/dashboard` without console errors.
  - [x] Layout responsive on mobile, tablet, and desktop.

---

### [x] Phase 2: User, Role & Permission (RBAC) System & Authentication
- [x] Install and configure permission system (`spatie/laravel-permission`).
- [x] Seed default roles: `SuperAdmin`, `BranchManager`, `Accountant`, `SalesDriver`, `InventoryStorekeeper`.
- [x] Implement Role & Permission Management Screen (Permission matrix, role CRUD).
- [x] Implement User Management Screen (User CRUD, assign role, active/inactive toggle).
- [x] Implement Dashboard Login Functionality:
  - `AuthenticatedSessionController` (login, logout, session regeneration).
  - `LoginRequest` supporting login with Email or Phone number + Password.
  - Rate limiting (6 attempts / minute) with lockout protection.
  - `EnsureUserIsActive` middleware redirecting deactivated staff to login.
  - Modern, responsive Vue 3 Login Page (`resources/js/Pages/Auth/Login.vue`) with 1-click Quick Demo role fills.
- [x] Setup route authorization middleware (`auth`, `active.user`) protecting `/dashboard` and administrative routes.
- **Phase 2 Verification:**
  - [x] Staff can sign in using either email or phone number.
  - [x] Deactivated staff accounts are prevented from logging in.
  - [x] Logout invalidates session and regenerates CSRF token.
  - [x] Unauthenticated guests attempting to visit `/dashboard` are redirected to `/login`.
  - [x] Automated feature tests passing for authentication and user/role flows (14 tests, 47 assertions).

---

### [x] Phase 3: Road Management & Retailer Directory (Tier 3 Setup)
- [x] Create `roads` migration, Model, `StoreRoadRequest`, `RoadService`, and `RoadController`.
- [x] Road UI: List, Add, Edit, Delete (Road A, B, C; assigned vehicle & driver contact).
- [x] Create `retailer_shops` migration, Model, `StoreRetailerRequest`, `RetailerService`, and `RetailerController`.
- [x] Retailer UI:
  - DataTable with road filter (filter by Road A, Road B, Road C).
  - Search by shop name, owner name, phone number.
  - Create & Edit shop modal (mandatory road dropdown selection, opening due amount, credit limit).
- [x] Retailer Ledger view: Shop profile, opening balance, transactions history, and current due balance.
- **Phase 3 Verification:**
  - [x] Able to create roads (Road A, Road B, Road C).
  - [x] Able to register shops under specific roads and verify road filter in UI.
  - [x] Automated feature tests passing for road and retailer flows (9 tests, 21 assertions).

---

### [x] Phase 4: Product Packaging, Catalog & Central Warehouse Stock (Tier 2 Setup)
- [x] Create `product_categories` and `products` migrations and models.
- [x] Support packaging types:
  - Cartoon type (multi-item carton packaging)
  - 5kg packet type (Chanachur, Toast, Biscuits bulk packs)
  - 12 pcs packet type (Cakes, snack packs)
  - Standard pieces/kg
- [x] Product CRUD UI:
  - Category selection, packaging type dropdown, wholesale price, cost price, min stock alert.
- [x] **Mominul Bekari Central Warehouse Stock Engine**:
  - Create `stock_movements` (immutable audit ledger for all movements).
  - Create `stock_adjustments` & `stock_adjustment_items` (physical count vs system count audits).
  - Create `damage_scrap_inventory` (quarantine store for damaged goods).
  - Implement `StockService` (validation, balance updates, stock ledger logging).
  - UI: Stock Ledger view (filter by product, date, movement type).
  - UI: Low-Stock Alert dashboard widget (items below threshold).
- **Phase 4 Verification:**
  - [x] Products with different packaging types created and prices correctly formatted.
  - [x] Stock movements properly recorded in immutable audit log.
  - [x] Low-stock alert badge activates when inventory falls below threshold.
  - [x] Automated feature tests passing for product catalog and stock engine (11 tests, 33 assertions).

---

### [x] Phase 5: Supplier Procurement & Purchase Stock-In Engine (Tier 1 Inflow)
- [x] Create `suppliers`, `purchases`, `purchase_items`, and `supplier_payments` migrations and models.
- [x] Implement `PurchaseService`, `SupplierPaymentService`, and FormRequests.
- [x] Supplier Directory UI:
  - Supplier list with company name, contact, opening balance, and live due amount.
  - Add/Edit supplier modal.
- [x] **Purchase Invoice (Stock-In to Mominul Bekari) UI**:
  - Select Supplier -> invoice number & date.
  - Add purchased products (packaging type, quantity, unit cost price, line total).
  - Real-time totals: Subtotal, discount, grand total, paid amount, and due amount.
  - **Automated Stock-In**: Receiving purchase automatically increments `products.warehouse_stock` in Mominul Bekari's central warehouse and logs `PURCHASE_IN` in `stock_movements`.
  - Atomically updates supplier current due balance.
- [x] Supplier Payment modal (record cash/bank/bKash payment against supplier due balance).
- [x] Supplier Ledger view: Full history of purchases, payments, and outstanding dues.
- **Phase 5 Verification:**
  - [x] Creating a purchase bill immediately increases Mominul Bekari's warehouse stock and logs `PURCHASE_IN`.
  - [x] Supplier due increases with new purchases and decreases upon payment.
  - [x] Automated feature tests passing for supplier procurement flow (6 tests, 17 assertions).

---

### [x] Phase 6: Vehicle Mobile Stock Loading & Trip Management (Mominul Bekari -> Road)
- [x] Create `vehicle_trips` and `vehicle_trip_items` migrations and models.
- [x] Implement `TripService`, `StoreTripRequest`, and `VehicleTripController`.
- [x] Trip Loading Sheet UI:
  - Select Road (Road A, B, or C) -> auto-populate vehicle & driver info.
  - Add products and loading quantities.
  - **Upstream Purchase Prerequisite & 0-Stock Prevention**: Products with 0 central warehouse stock cannot be loaded onto vehicles. Stock must first be ingested through manufacturing supplier purchases (`PURCHASE_IN`).
  - **Stock Transfer**: Deduct loaded quantities from `warehouse_stock`, assign to `vehicle_trip_items`, log `LOAD_TO_VEHICLE` movement.
- [x] Dispatch Vehicle: Mark trip as `DISPATCHED`.
- [x] Printable Vehicle Loading Manifest / Gate Pass for the driver.
- **Phase 6 Verification:**
  - [x] Trip created for a road, products loaded, and central warehouse stock decreased accordingly.
  - [x] System prevents loading products with 0 or insufficient warehouse stock with clear guidance to purchase from supplier first.
  - [x] Printable trip sheet displays correct product counts and driver info.
  - [x] Automated feature tests passing for vehicle trip loading & dispatch (6 tests, 21 assertions).

---

### [x] Phase 7: Sales Memo, Billing & Credit (Due) Engine (Road -> Retailer Delivery)
- [x] Create `sales_memos`, `sales_memo_items` migrations and models.
- [x] Implement `MemoService`, `StoreMemoRequest`, and `MemoController`.
- [x] Sales Memo Creation UI:
  - Filter Retailers strictly by the assigned Road.
  - Channel selector: Active road vehicle trip or direct central depot sale.
  - Display selected retailer's existing due balance in real time.
  - Product selection with real-time available stock badges (vehicle stock on road trips, warehouse stock on direct sales).
  - **Zero-Stock Selling Prevention**: 0 stock cannot be sold. Rejects issuing memos for unstocked items or quantities exceeding available mobile trip/depot stock.
  - Live calculations: Subtotal - Damage Deduction - Exchange Deduction - Custom Deduction + Previous Due - Paid Amount = Net Due.
- [x] Automated status assignment: `PAID`, `PARTIAL`, `DUE`.
- [x] Update Retailer current due balance atomically in DB transaction.
- [x] Deduct sold quantities from vehicle trip stock (or central warehouse for direct sales).
- [x] Support embedded Return rows (Damage returns & Exchange returns) directly in Memo creation flow.
- [x] Printable Sales Memo layouts (Thermal 80mm & standard A4 formats) displaying return/exchange lines.
- [x] Direct Retailer Due Payment Collection modal.
- **Phase 7 Verification:**
  - [x] Memo creation correctly calculates subtotal, applies damage/exchange deductions, deducts vehicle stock, and updates retailer due.
  - [x] System strictly prevents selling 0 stock or products not loaded on the vehicle trip.
  - [x] Thermal and A4 print views format properly without UI distortion.
  - [x] Automated feature tests passing for sales memos, stock validation, and payments (11 tests, 39 assertions).

---

### [x] Phase 8: Returns & Exchanges (Damage Returns, Product Exchanges, Custom Deductions & Scrap / Supplier Claims)
- [x] Create `damage_returns` (or `product_returns`) and `supplier_return_claims` migrations and models.
- [x] Implement `DamageReturnService` and FormRequests supporting both return workflows:
  - **Scenario 1: Damage / Expiry Return (Defective / Unsellable Goods):**
    - Retailer returns damaged, broken, or expired products.
    - Financial impact: Deducted from new memo total (`damage_deduction`), reducing payable amount.
    - Stock impact: Does **NOT** enter sellable stock. Automatically routed to `damage_scrap_inventory` (Quarantine).
    - Can subsequently be claimed back to manufacturing supplier (`supplier_return_claims`) or written off (`SCRAP_DISPOSAL`).
  - **Scenario 2: Product Exchange (Good Condition / Resellable Stock Return):**
    - Retailer exchanges good condition/intact products (e.g. slow-moving stock for fresh/alternate items).
    - Financial impact: Deducted from new memo total (`exchange_deduction`), offsetting new items taken.
    - Stock impact: **Increases sellable stock** (`affects_sellable_stock = true`).
      - In vehicle trip: adds back into vehicle's active trip stock (`exchanged_quantity`), allowing immediate resale or return to depot.
      - In central warehouse/direct memos: logs `StockMovementType::EXCHANGE_RETURN`, directly increasing central warehouse stock.
- [x] Return UI in Memo Screen:
  - Select Return Type: `Damage Return` (Scenario 1) or `Product Exchange` (Scenario 2).
  - Add returned product rows with quantity and deduction rate (auto-populated from wholesale price).
  - Custom amount deduction toggle and input field (supervisor override/custom discount).
- [x] Memo Financial Update: Deduct both damage and exchange deductions along with custom deductions before computing net payable.
- [x] Damage & Exchange audit report with filters by road, shop, date, product, and return type (`Admin/Returns/Index.vue`).
- [x] Supplier return claim filing modal and backend recording.
- **Phase 8 Verification:**
  - [x] Scenario 1: Damaged items correctly deduct from memo total and route to quarantine scrap without increasing sellable stock.
  - [x] Scenario 2: Exchanged good items correctly deduct from memo total and increase sellable stock in vehicle trip/central warehouse.
  - [x] Custom deduction override works and is tracked in audit logs.
  - [x] Automated feature tests passing for both return workflows (verified in `SalesMemoAndReturnsTest.php`).

---

### [x] Phase 9: Road-Wise Sales, Collections & Stock Reconciliation
- [x] Implement `TripService` reconciliation methods, `ReconcileTripRequest`, `TripReconciliationController`, `RoadReportService`, and `RoadReportController`.
- [x] Road-Wise Settlement Dashboard:
  - Road A summary tab (Loaded vs Sold vs Damaged vs Returned).
  - Road B summary tab.
  - Road C summary tab.
  - Grand Total consolidated summary tab for Mominul Bekari.
- [x] **Daily Trip Closing & Unsold Stock Return**:
  - Enter unsold quantities returning from the vehicle (`Admin/Trips/Reconcile.vue`).
  - Reconcile invariant: $\text{Loaded Quantity} + \text{Exchanged Quantity} = \text{Sold Quantity} + \text{Returned Quantity}$.
  - Damaged quantities collected are routed to `damage_scrap_inventory` (Quarantine).
  - Automatically restore unsold items from vehicle back into Mominul Bekari central warehouse stock (`RETURN_FROM_VEHICLE` ledger entry).
  - Complete trip (`COMPLETED` status).
- **Phase 9 Verification:**
  - [x] Road totals match individual memo sums.
  - [x] Grand total correctly aggregates all roads.
  - [x] Unsold goods accurately restored to Mominul Bekari central warehouse inventory upon closing trip.
  - [x] Discrepancy warning shown if loaded stock + exchanged stock doesn't match sold + returned.
  - [x] Automated feature tests passing for trip reconciliation and road settlement (4 tests, 11 assertions).

---

### [x] Phase 10: Operational Expenses, General Settings, DB Backup, Frontend Landing Page & Final QA
- [x] Operational Expense Module:
  - Pre-seeded categories: Vehicle Fuel, Vehicle Repair, Depot Rent, Staff Salary, Utilities, Packaging, Entertainment, Miscellaneous.
  - Expense entry with receipt/voucher image upload, payment method, date, vehicle number, road, and description (`Admin/Expenses/Index.vue`).
  - Total overhead calculation and ledger table with filters.
- [x] General Settings Module:
  - Site Name ("Mominul Bekari Wholesale Distribution"), Hotline, Address, Currency symbol (`৳`), Receipt headers/footers, Low stock alert thresholds, SMS toggle, Credit warning toggle (`Admin/Settings/Index.vue`).
- [x] Database Backup Module:
  - One-click manual backup trigger (`Admin/Backup/Index.vue`).
  - Safe snapshot copy to storage, downloadable backup file, file size calculation, and deletion.
- [x] Public Landing Page (Homepage):
  - Responsive, modern landing page (`Welcome.vue`) showcasing Mominul Bekari, 3-Tier Supply Chain architecture, wholesale products (Toast, Cakes, Buns, Bread, Cookies), dealership inquiry form, and contact info.
- [x] Final End-to-End QA:
  - Comprehensive walkthrough of the complete chain: **Supplier (Tier 1) -> Mominul Bekari (Tier 2) -> Road Vehicle -> Retailer Shop (Tier 3) -> Daily Settlement -> DB Backup**.
- **Phase 10 Verification:**
  - [x] Public site loads seamlessly with interactive dealership inquiry.
  - [x] Settings persist across the application.
  - [x] Database backup creates valid, downloadable snapshot.
  - [x] Automated feature tests passing for expenses, settings, backup, and landing page (6 tests, 26 assertions).
  - [x] Entire test suite green: 59 tests, 177 assertions passing across all 10 phases.

---

## Working Guidelines for Continuous Execution

1. **Atomic Commits & Verification:** Never move to the next phase until the verification checklist for the current phase passes completely.
2. **Database Integrity:** Always wrap multi-model updates (e.g. Memo creation + Stock deduction + Retailer Due update) inside `DB::transaction()`.
3. **No Dead Ends / Missing Components:** When adding an Inertia view, ensure all child PrimeVue components, routes, and FormRequests exist to prevent runtime white screens.
