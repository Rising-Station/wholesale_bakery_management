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
| **5%** (In Progress) | Phase 1: Foundation & UI Shell | 2026-10-05 | 🔵 In Progress |

---

## Phase Checklist & Milestones

### [ ] Phase 1: Foundation, UI Shell & Core Helpers
- [ ] Initialize Laravel project with Inertia.js + Vue.js 3 (`<script setup>`).
- [ ] Configure PrimeVue v4 with styled theme (Aura/Lara) & PrimeIcons.
- [ ] Create base layout: Sidebar, Header/Topbar, User dropdown, Flash toast container.
- [ ] Create reusable PHP Enums:
  - `app/Enums/MemoStatus.php`
  - `app/Enums/PurchaseStatus.php`
  - `app/Enums/DeliveryStatus.php`
  - `app/Enums/PackagingType.php`
  - `app/Enums/DamageType.php`
  - `app/Enums/StockMovementType.php`
  - `app/Enums/StockLocationType.php`
  - `app/Enums/PaymentMethod.php`
  - `app/Enums/ExpenseCategoryType.php`
  - `app/Enums/TripStatus.php`
- [ ] Implement reusable Traits:
  - `app/Traits/ApiResponseTrait.php`
  - `app/Traits/FileUploadTrait.php`
  - `app/Traits/HasAuditTrail.php`
  - `app/Traits/CurrencyFormatterTrait.php`
- [ ] Build reusable PrimeVue UI wrapper components:
  - AppDataTable (pagination, search, export)
  - ConfirmModal
  - CurrencyInput
- **Phase 1 Verification:**
  - [ ] App loads cleanly at `/` or `/dashboard` without console errors.
  - [ ] Layout responsive on mobile, tablet, and desktop.

---

### [ ] Phase 2: User, Role & Permission (RBAC) System
- [ ] Install and configure permission system (`spatie/laravel-permission`).
- [ ] Seed default roles: `SuperAdmin`, `BranchManager`, `Accountant`, `SalesDriver`, `InventoryStorekeeper`.
- [ ] Implement Role & Permission Management Screen (Permission matrix, role CRUD).
- [ ] Implement User Management Screen (User CRUD, assign role, active/inactive toggle).
- [ ] Setup route authorization middleware & role-based menu rendering.
- **Phase 2 Verification:**
  - [ ] Role creation, updating, and assigning to users works without error.
  - [ ] Unauthorized users are properly restricted from protected routes.

---

### [ ] Phase 3: Road Management & Retailer Directory (Tier 3 Setup)
- [ ] Create `roads` migration, Model, `StoreRoadRequest`, `RoadService`, and `RoadController`.
- [ ] Road UI: List, Add, Edit, Delete (Road A, B, C; assigned vehicle & driver contact).
- [ ] Create `retailer_shops` migration, Model, `StoreRetailerRequest`, `RetailerService`, and `RetailerController`.
- [ ] Retailer UI:
  - DataTable with road filter (filter by Road A, Road B, Road C).
  - Search by shop name, owner name, phone number.
  - Create & Edit shop modal (mandatory road dropdown selection, opening due amount, credit limit).
- [ ] Retailer Ledger view: Shop profile, opening balance, transactions history, and current due balance.
- **Phase 3 Verification:**
  - [ ] Able to create roads (Road A, Road B, Road C).
  - [ ] Able to register shops under specific roads and verify road filter in UI.

---

### [ ] Phase 4: Product Packaging, Catalog & Central Warehouse Stock (Tier 2 Setup)
- [ ] Create `product_categories` and `products` migrations and models.
- [ ] Support packaging types:
  - Cartoon type (multi-item carton packaging)
  - 5kg packet type (Chanachur, Toast, Biscuits bulk packs)
  - 12 pcs packet type (Cakes, snack packs)
  - Standard pieces/kg
- [ ] Product CRUD UI:
  - Category selection, packaging type dropdown, wholesale price, cost price, min stock alert.
- [ ] **Mominul Bekari Central Warehouse Stock Engine**:
  - Create `stock_movements` (immutable audit ledger for all movements).
  - Create `stock_adjustments` & `stock_adjustment_items` (physical count vs system count audits).
  - Create `damage_scrap_inventory` (quarantine store for damaged goods).
  - Implement `StockService` (validation, balance updates, stock ledger logging).
  - UI: Stock Ledger view (filter by product, date, movement type).
  - UI: Low-Stock Alert dashboard widget (items below threshold).
- **Phase 4 Verification:**
  - [ ] Products with different packaging types created and prices correctly formatted.
  - [ ] Stock movements properly recorded in immutable audit log.
  - [ ] Low-stock alert badge activates when inventory falls below threshold.

---

### [ ] Phase 5: Supplier Procurement & Purchase Stock-In Engine (Tier 1 Inflow)
- [ ] Create `suppliers`, `purchases`, `purchase_items`, and `supplier_payments` migrations and models.
- [ ] Implement `PurchaseService`, `SupplierPaymentService`, and FormRequests.
- [ ] Supplier Directory UI:
  - Supplier list with company name, contact, opening balance, and live due amount.
  - Add/Edit supplier modal.
- [ ] **Purchase Invoice (Stock-In to Mominul Bekari) UI**:
  - Select Supplier -> invoice number & date.
  - Add purchased products (packaging type, quantity, unit cost price, line total).
  - Real-time totals: Subtotal, discount, grand total, paid amount, and due amount.
  - **Automated Stock-In**: Receiving purchase automatically increments `products.warehouse_stock` in Mominul Bekari's central warehouse and logs `PURCHASE_IN` in `stock_movements`.
  - Atomically updates supplier current due balance.
- [ ] Supplier Payment modal (record cash/bank/bKash payment against supplier due balance).
- [ ] Supplier Ledger view: Full history of purchases, payments, and outstanding dues.
- **Phase 5 Verification:**
  - [ ] Creating a purchase bill immediately increases Mominul Bekari's warehouse stock and logs `PURCHASE_IN`.
  - [ ] Supplier due increases with new purchases and decreases upon payment.

---

### [ ] Phase 6: Vehicle Mobile Stock Loading & Trip Management (Mominul Bekari -> Road)
- [ ] Create `vehicle_trips` and `vehicle_trip_items` migrations and models.
- [ ] Implement `TripService`, `StoreTripRequest`, and `VehicleTripController`.
- [ ] Trip Loading Sheet UI:
  - Select Road (Road A, B, or C) -> auto-populate vehicle & driver info.
  - Add products and loading quantities.
  - **Stock Validation**: Prevent loading more than available central warehouse stock at Mominul Bekari.
  - **Stock Transfer**: Deduct loaded quantities from `warehouse_stock`, assign to `vehicle_trip_items`, log `LOAD_TO_VEHICLE` movement.
- [ ] Dispatch Vehicle: Mark trip as `DISPATCHED`.
- [ ] Printable Vehicle Loading Manifest / Gate Pass for the driver.
- **Phase 6 Verification:**
  - [ ] Trip created for a road, products loaded, and central warehouse stock decreased accordingly.
  - [ ] System prevents loading products with insufficient warehouse stock.
  - [ ] Printable trip sheet displays correct product counts and driver info.

---

### [ ] Phase 7: Sales Memo, Billing & Credit (Due) Engine (Road -> Retailer Delivery)
- [ ] Create `sales_memos`, `sales_memo_items` migrations and models.
- [ ] Implement `MemoService`, `StoreMemoRequest`, and `MemoController`.
- [ ] Sales Memo Creation UI:
  - Filter Retailers strictly by the assigned Road.
  - Display selected retailer's existing due balance in real time.
  - Product selection with packaging type, unit price, quantity, and auto-line total.
  - Real-time stock validation against vehicle loaded stock.
  - Live calculations: Subtotal + Previous Due - Paid Amount = Net Due.
- [ ] Automated status assignment: `PAID`, `PARTIAL`, `DUE`.
- [ ] Update Retailer current due balance atomically in DB transaction.
- [ ] Deduct sold quantities from vehicle trip stock.
- [ ] Printable Sales Memo layouts (Thermal 80mm & standard A4 formats).
- [ ] Direct Retailer Due Payment Collection modal.
- **Phase 7 Verification:**
  - [ ] Memo creation correctly calculates subtotal, deducts vehicle stock, and updates retailer due.
  - [ ] Thermal and A4 print views format properly without UI distortion.

---

### [ ] Phase 8: Damage Returns, Custom Deductions & Scrap / Supplier Claims
- [ ] Create `damage_returns` and `supplier_return_claims` migrations and models.
- [ ] Implement `DamageReturnService` and FormRequests.
- [ ] Damage Return UI in Memo Screen:
  - Add damaged/expired product rows (e.g., 10 cakes damaged, 2 kg chanachur expired).
  - Auto-calculate standard deduction value based on wholesale price.
  - Custom amount deduction toggle and input field (supervisor override/custom discount).
- [ ] **Scrap Quarantine & Supplier Return Routing**:
  - Automatically route damaged/expired quantities into `damage_scrap_inventory`.
  - Ensure damaged items never return into sellable central warehouse stock.
  - Optional: Generate Supplier Return Claim to send defective/expired goods back to the manufacturing supplier for credit.
- [ ] Memo Financial Update: Deduct damage + custom deductions from memo total before computing net payable.
- [ ] Damage & Expiry audit report with filters by road, shop, date, and product.
- **Phase 8 Verification:**
  - [ ] Adding damage items correctly deducts the amount from the memo total.
  - [ ] Custom deduction override works and is tracked in damage audit logs.
  - [ ] Damaged items are logged in quarantine scrap inventory.

---

### [ ] Phase 9: Road-Wise Sales, Collections & Stock Reconciliation
- [ ] Implement `TripReconciliationService` and `RoadReportController`.
- [ ] Road-Wise Settlement Dashboard:
  - Road A summary tab (Loaded vs Sold vs Damaged vs Returned).
  - Road B summary tab.
  - Road C summary tab.
  - Grand Total consolidated summary tab for Mominul Bekari.
- [ ] **Daily Trip Closing & Unsold Stock Return**:
  - Enter unsold quantities returning from the vehicle.
  - Reconcile invariant: $\text{Loaded Quantity} = \text{Sold Quantity} + \text{Damaged Quantity} + \text{Returned Quantity}$.
  - Automatically restore unsold items from vehicle back into Mominul Bekari central warehouse stock (`RETURN_FROM_VEHICLE` ledger entry).
  - Complete trip (`COMPLETED` status).
- **Phase 9 Verification:**
  - [ ] Road totals match individual memo sums.
  - [ ] Grand total correctly aggregates all roads.
  - [ ] Unsold goods accurately restored to Mominul Bekari central warehouse inventory upon closing trip.
  - [ ] Discrepancy warning shown if loaded stock doesn't match sold + damaged + returned.

---

### [ ] Phase 10: Operational Expenses, General Settings, DB Backup, Frontend Landing Page & Final QA
- [ ] Operational Expense Module:
  - Pre-seeded categories: Salary, Shop Rent, Electric Bill, Gas Bill, Water Bill, Other Expenses.
  - Expense entry with receipt/voucher image upload, payment method, date, and description.
  - Monthly expense analytics chart.
- [ ] General Settings Module:
  - Site Name ("Mominul Bekari Wholesale"), Logo, Favicon, Copyright text, Currency symbol (`৳` / BDT), SMS Notifications On/Off toggle, Maintenance Mode switch.
- [ ] Database Backup Module:
  - One-click manual backup trigger (`mysqldump` / gzip).
  - Download backup file, delete old backups, auto-backup schedule command (`php artisan backup:run`).
- [ ] Public Landing Page (Homepage):
  - Responsive, modern landing page showcasing Mominul Bekari, wholesale products (Chanachur, Biscuits, Cakes, etc.), dealership inquiry form, and contact info.
- [ ] Final End-to-End QA:
  - Comprehensive walkthrough of the complete chain: **Supplier -> Mominul Bekari -> Road Vehicle -> Retailer Shop -> Daily Settlement -> DB Backup**.
- **Phase 10 Verification:**
  - [ ] Public site loads seamlessly.
  - [ ] Settings persist across the application.
  - [ ] Database backup creates valid, downloadable SQL dump.

---

## Working Guidelines for Continuous Execution

1. **Atomic Commits & Verification:** Never move to the next phase until the verification checklist for the current phase passes completely.
2. **Database Integrity:** Always wrap multi-model updates (e.g. Memo creation + Stock deduction + Retailer Due update) inside `DB::transaction()`.
3. **No Dead Ends / Missing Components:** When adding an Inertia view, ensure all child PrimeVue components, routes, and FormRequests exist to prevent runtime white screens.
