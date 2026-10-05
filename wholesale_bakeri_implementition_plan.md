# Mominul Bekari - Wholesale Distribution Management System
## Technical Implementation Plan & Architecture Specification

---

### 1. Executive Summary & Core Distribution Chain

A specialized, enterprise-grade ERP & Sales Distribution system built exclusively for **Mominul Bekari**. 

**Mominul Bekari** operates as the central wholesale distribution hub in a strict 3-tier supply chain:

$$\mathbf{Supplier} \longrightarrow \mathbf{Mominul\ Bekari} \longrightarrow \mathbf{Retailers\ (Organized\ by\ Road)}$$

#### The 3-Tier Distribution Architecture:
1. **Tier 1: Suppliers (Bakery Manufacturers):**
   - Supply finished packaged goods (Chanachur, Toast, Biscuits, Cakes) in bulk packaging (Cartons, 5kg bulk packets, 12-piece single-serve packets).
   - Mominul Bekari purchases stock on cash/credit, tracks supplier invoices, dues, and payments.
2. **Tier 2: Mominul Bekari (Central Wholesale Hub & Depot):**
   - Central Warehouse stores saleable inventory.
   - Operates dispatch loading sheets for 3 dedicated delivery roads (Road A, Road B, Road C).
   - Manages overall accounts, road-wise settlement, damage isolation, and operational expenses.
3. **Tier 3: Retailers (Strictly Segmented by Road):**
   - Retailer shops belong to a specific road (Road A: ~100 shops, Road B: ~50 shops, Road C: ~75 shops).
   - Mominul Bekari dispatches **1 vehicle per road at a time**.
   - Sales Memos are generated per retailer shop, tracking delivered products, damage/expiry deductions, cash paid, and remaining due.

#### Technology Stack:
##### Project directory - /Users/alamin/Herd/wholesale_bakery_system
- **Backend Framework:** Laravel (v11.x / v12.x) with PHP 8.3+
- **Frontend Stack:** Inertia.js (v2.x) + Vue.js 3 (`<script setup>`, Composition API)
- **UI Component Library:** PrimeVue (v4.x) with Tailwind / Styled Mode & PrimeIcons
- **Database:** MySQL 8.0+
- **Architecture Patterns:**
  - **Service Pattern:** Business logic isolated into dedicated Service classes (`PurchaseService`, `StockService`, `MemoService`, `TripService`, etc.).
  - **Form Requests:** Strict validation rules decoupled into individual Form Request classes.
  - **PHP Enums:** Type-safe enums for statuses, packaging types, stock movement types, damage types, expense categories, and payment methods.
  - **Reusable Traits:** Standardized response formats, file upload handlers, audit logs, and currency/number formatting.
  - **Database Transactions:** ACID compliance for all multi-table accounting and inventory adjustments.

---



### 2. Architectural Blueprint & Design Patterns

```
app/
├── Enums/
│   ├── MemoStatus.php              # PAID, PARTIAL, DUE, CANCELLED
│   ├── PurchaseStatus.php          # ORDERED, RECEIVED, PARTIAL, CANCELLED
│   ├── DeliveryStatus.php          # PENDING, LOADED, DELIVERED, RETURNED
│   ├── PackagingType.php           # CARTOON, PACKET_5KG, PACKET_12PCS, CUSTOM
│   ├── DamageType.php              # EXPIRED, DAMAGED, TRANSIT_LOSS
│   ├── StockMovementType.php       # PURCHASE_IN, PURCHASE_RETURN_SUPPLIER, LOAD_TO_VEHICLE, RETURN_FROM_VEHICLE, DIRECT_SALE, DAMAGE_RETURN, SCRAP_DISPOSAL, AUDIT_ADJUSTMENT
│   ├── StockLocationType.php       # CENTRAL_WAREHOUSE, VEHICLE_TRANSIT, DAMAGE_QUARANTINE
│   ├── PaymentMethod.php           # CASH, BKASH, NAGAD, BANK_TRANSFER, CHEQUE
│   ├── ExpenseCategoryType.php     # SALARY, SHOP_RENT, ELECTRICITY, GAS, WATER, OTHER
│   └── TripStatus.php              # DRAFT, LOADED, DISPATCHED, COMPLETED
├── Http/
│   ├── Controllers/                # Thin controllers delegating directly to Services
│   │   ├── Admin/
│   │   ├── SupplierController.php            # Tier 1: Supplier directory & balance ledger
│   │   ├── PurchaseController.php            # Supplier -> Mominul Bekari procurement & stock-in
│   │   ├── StockController.php               # Mominul Bekari central warehouse inventory & alerts
│   │   ├── StockAdjustmentController.php     # Physical count audits & variance corrections
│   │   ├── RoadController.php                # Road A, B, C configuration
│   │   ├── RetailerController.php            # Tier 3: Retail shops directory (mapped to roads)
│   │   ├── ProductController.php             # Product catalog & packaging types
│   │   ├── VehicleTripController.php         # Mominul Bekari -> Road vehicle dispatch
│   │   ├── MemoController.php                # Retailer sales memos & billing
│   │   ├── DamageReturnController.php        # Damage credits & scrap tracking
│   │   ├── ExpenseController.php             # Operational overhead expenses
│   │   └── SettingController.php             # System configuration
│   └── Requests/                   # Dedicated request validation classes
│       ├── Purchase/
│       │   ├── StorePurchaseRequest.php
│       │   └── StoreSupplierPaymentRequest.php
│       ├── Stock/
│       │   └── StoreStockAdjustmentRequest.php
│       ├── Memo/
│       │   ├── StoreMemoRequest.php
│       │   └── StoreRetailerPaymentRequest.php
│       ├── Retailer/
│       └── ...
├── Services/                       # Core business logic & database transactions
│   ├── PurchaseService.php         # Supplier procurement, stock-in increment, supplier due ledger
│   ├── StockService.php            # Warehouse stock validation, movement ledger, low-stock alerts
│   ├── MemoService.php             # Computes memo totals, damage deductions, retailer due ledger
│   ├── TripService.php             # Vehicle load deduction, gate pass, daily road trip closing
│   ├── TripReconciliationService.php # Road-wise vs grand total reconciliation & unsold stock return
│   ├── ExpenseService.php          # Overhead expenses & analytics
│   └── BackupService.php           # Database dump & file management
└── Traits/
    ├── HasAuditTrail.php           # Created_by / updated_by tracking
    ├── FileUploadTrait.php         # Logo, receipts, voucher image upload
    └── CurrencyFormatterTrait.php  # Decimal precision & localized currency formatting
```

---

### 3. Database Schema Design (Entity-Relationship Overview)

#### 3.1 Tier 1: Suppliers & Procurement (Inflow to Mominul Bekari)
- **`suppliers`**:
  - `id` (PK), `name`, `company_name`, `phone`, `alt_phone`, `address`, `opening_due` (decimal, default 0), `current_due` (decimal, default 0), `is_active` (bool), timestamps.
- **`purchases`**:
  - `id` (PK), `supplier_id` (FK -> `suppliers`), `invoice_no` (string, supplier bill number), `purchase_date` (date), `subtotal` (decimal), `discount` (decimal, default 0), `grand_total` (decimal), `paid_amount` (decimal, default 0), `due_amount` (decimal, default 0), `payment_status` (Enum: `PAID`, `PARTIAL`, `DUE`), `status` (Enum: `RECEIVED`, `ORDERED`), `notes` (text), `created_by` (FK -> `users`), timestamps.
- **`purchase_items`**:
  - `id` (PK), `purchase_id` (FK -> `purchases`), `product_id` (FK -> `products`), `packaging_type` (Enum: `CARTOON`, `PACKET_5KG`, `PACKET_12PCS`, etc.), `quantity` (decimal), `unit_cost` (decimal), `line_total` (decimal), timestamps.
- **`supplier_payments`**:
  - `id` (PK), `supplier_id` (FK -> `suppliers`), `purchase_id` (FK, nullable), `amount` (decimal), `payment_method` (Enum), `payment_date` (date), `transaction_ref` (string, nullable), `notes` (text), `created_by` (FK -> `users`), timestamps.
- **`supplier_return_claims`**:
  - `id` (PK), `supplier_id` (FK -> `suppliers`), `product_id` (FK -> `products`), `quantity` (decimal), `unit_cost` (decimal), `claim_amount` (decimal), `reason` (Enum: `EXPIRED`, `DAMAGED_FROM_FACTORY`), `status` (Enum: `PENDING`, `ACCEPTED`, `REPLACED_WITH_STOCK`), `notes` (text), timestamps.

#### 3.2 Tier 2: Mominul Bekari Central Warehouse & Product Catalog
- **`product_categories`**:
  - `id` (PK), `name`, `slug`, `is_active` (bool).
- **`products`**:
  - `id` (PK), `category_id` (FK), `name`, `code` (SKU), `packaging_type` (Enum: `CARTOON`, `PACKET_5KG`, `PACKET_12PCS`, etc.), `units_per_cartoon` (int, default 1), `weight_per_unit_kg` (decimal, nullable), `pieces_per_packet` (int, nullable), `cost_price` (decimal, purchase cost from supplier), `wholesale_price` (decimal, selling price to retailer), `warehouse_stock` (decimal, default 0), `min_alert_quantity` (int, default 10), `is_active` (bool), timestamps.
- **`stock_movements`** (Immutable Audit Ledger):
  - `id` (PK), `product_id` (FK -> `products`), `movement_type` (Enum: `PURCHASE_IN`, `PURCHASE_RETURN_SUPPLIER`, `LOAD_TO_VEHICLE`, `RETURN_FROM_VEHICLE`, `DIRECT_SALE`, `DAMAGE_RETURN`, `SCRAP_DISPOSAL`, `AUDIT_ADJUSTMENT`), `source_location` (Enum: `CENTRAL_WAREHOUSE`, `VEHICLE_TRANSIT`, `DAMAGE_QUARANTINE`), `destination_location` (Enum), `reference_type` (Purchase, Trip, Memo, Adjustment, Claim), `reference_id` (unsigned bigint), `quantity` (decimal), `balance_before` (decimal), `balance_after` (decimal), `remarks` (string), `created_by` (FK -> `users`), timestamps.
- **`stock_adjustments`**:
  - `id` (PK), `adjustment_no` (string, unique), `adjustment_date` (date), `reason` (string: Physical count variance, shrinkage, miscount), `status` (Enum: `PENDING`, `APPROVED`), `approved_by` (FK -> `users`), `created_by` (FK -> `users`), timestamps.
- **`stock_adjustment_items`**:
  - `id` (PK), `stock_adjustment_id` (FK -> `stock_adjustments`), `product_id` (FK -> `products`), `system_stock` (decimal), `physical_stock` (decimal), `variance_quantity` (decimal), `adjustment_type` (Enum: `ADD`, `REDUCE`), timestamps.
- **`damage_scrap_inventory`**:
  - `id` (PK), `product_id` (FK -> `products`), `retailer_id` (FK, nullable), `road_id` (FK, nullable), `damage_type` (Enum: `DAMAGED`, `EXPIRED`), `quantity` (decimal), `status` (Enum: `QUARANTINED`, `RETURNED_TO_SUPPLIER`, `DESTROYED_SCRAP`), `disposal_date` (date, nullable), `disposal_notes` (text), timestamps.

#### 3.3 Roads & Route Vehicle Dispatch (Mominul Bekari -> Road)
- **`roads`**:
  - `id` (PK), `name` (e.g., Road A, Road B, Road C), `code` (e.g., RD-A), `vehicle_number`, `driver_name`, `driver_phone`, `is_active` (bool), timestamps.
- **`vehicle_trips`**:
  - `id` (PK), `road_id` (FK -> `roads`), `trip_date` (date), `vehicle_number`, `driver_id` (FK -> `users`), `status` (Enum: `DRAFT`, `LOADED`, `DISPATCHED`, `COMPLETED`), `notes` (text), timestamps.
- **`vehicle_trip_items`**:
  - `id` (PK), `vehicle_trip_id` (FK -> `vehicle_trips`), `product_id` (FK -> `products`), `loaded_quantity` (decimal), `sold_quantity` (decimal, default 0), `damaged_quantity` (decimal, default 0), `returned_quantity` (decimal, default 0), timestamps.

#### 3.4 Tier 3: Retailers (Road-Based Shops & Memos)
- **`retailer_shops`**:
  - `id` (PK), `road_id` (FK -> `roads`), `shop_name`, `owner_name`, `phone`, `alt_phone`, `address`, `credit_limit` (decimal), `opening_due` (decimal), `current_due` (decimal), `is_active` (bool), timestamps.
- **`sales_memos`**:
  - `id` (PK), `memo_no` (string, unique, e.g. MEMO-2026-0001), `retailer_id` (FK -> `retailer_shops`), `road_id` (FK -> `roads`), `vehicle_trip_id` (FK, nullable), `memo_date` (date), `subtotal` (decimal), `damage_deduction` (decimal, default 0), `custom_deduction` (decimal, default 0), `grand_total` (decimal), `paid_amount` (decimal, default 0), `due_amount` (decimal, default 0), `previous_due` (decimal, default 0), `total_net_payable` (decimal), `memo_status` (Enum: `PAID`, `PARTIAL`, `DUE`, `CANCELLED`), `delivery_status` (Enum: `PENDING`, `DELIVERED`, `RETURNED`), `notes`, `created_by` (FK -> `users`), timestamps.
- **`sales_memo_items`**:
  - `id` (PK), `sales_memo_id` (FK -> `sales_memos`), `product_id` (FK -> `products`), `packaging_type` (Enum), `quantity` (decimal), `unit_price` (decimal), `line_total` (decimal), timestamps.
- **`damage_returns`**:
  - `id` (PK), `sales_memo_id` (FK, nullable), `retailer_id` (FK -> `retailer_shops`), `product_id` (FK -> `products`), `damage_type` (Enum: `DAMAGED`, `EXPIRED`), `quantity` (decimal), `unit_deduction_rate` (decimal), `total_deduction_amount` (decimal), `is_custom_amount` (bool, default false), `notes`, `approved_by` (FK -> `users`), timestamps.
- **`retailer_payments`**:
  - `id` (PK), `retailer_id` (FK -> `retailer_shops`), `sales_memo_id` (FK, nullable), `road_id` (FK -> `roads`), `payment_method` (Enum), `amount` (decimal), `payment_date` (date), `transaction_ref`, `notes`, `collected_by` (FK -> `users`), timestamps.

#### 3.5 Overhead Expenses & System Configuration
- **`expense_categories`**:
  - `id` (PK), `name` (Salary, Shop Rent, Electric Bill, Gas Bill, Water Bill, Other Expenses), `type` (Enum: `EXPENSE`), `is_active` (bool).
- **`expenses`**:
  - `id` (PK), `category_id` (FK -> `expense_categories`), `title` (string), `amount` (decimal), `expense_date` (date), `payment_method` (Enum), `voucher_image` (string, nullable), `notes` (text), `created_by` (FK -> `users`), timestamps.
- **`settings`**:
  - `id` (PK), `key` (unique), `value` (text, nullable), `group` (string, default 'general').
- **`database_backups`**:
  - `id` (PK), `filename`, `disk`, `size_bytes`, `created_at`.

---

### 4. Step-by-Step Implementation Roadmap (10 Phases)

To prevent broken UI or functionality across partial sessions, work is organized into 10 autonomous, sequential phases:

#### Phase 1: Foundation, UI Shell & Core Helpers
- [ ] Initialize Laravel + Inertia.js + Vue 3 + PrimeVue v4 with Tailwind / PrimeVue Theme (Aura/Lara).
- [ ] Setup global layout: Admin Sidebar, Topbar with User Menu, Flash Toasts, Responsive Drawer.
- [ ] Build reusable PHP Enums (`MemoStatus`, `PurchaseStatus`, `PackagingType`, `DamageType`, `StockMovementType`, `StockLocationType`, `PaymentMethod`, `ExpenseCategoryType`, `TripStatus`).
- [ ] Implement foundational Traits (`ApiResponseTrait`, `FileUploadTrait`, `HasAuditTrail`, `CurrencyFormatterTrait`).
- [ ] Implement base PrimeVue utility wrapper components (AppDataTable, ConfirmModal, CurrencyInput).

#### Phase 2: User, Role & Permission (RBAC) System
- [ ] Install & configure RBAC (`spatie/laravel-permission`).
- [ ] Seed standard roles: `SuperAdmin`, `BranchManager`, `Accountant`, `SalesDriver`, `InventoryStorekeeper`.
- [ ] Setup Role & Permission Management UI (Permission matrix checkboxes, Role CRUD).
- [ ] Create User CRUD with Role assignment, password hashing, and active status toggle.

#### Phase 3: Road Management & Retailer Directory (Tier 3 Setup)
- [ ] Create `roads` migration, Model, `StoreRoadRequest`, `RoadService`, and `RoadController`.
- [ ] Road UI: List, Add, Edit, Delete (Road A, B, C; assigned vehicle & driver contact).
- [ ] Create `retailer_shops` migration, Model, `StoreRetailerRequest`, `RetailerService`, and `RetailerController`.
- [ ] Retailer UI:
  - DataTable with road filter (Road A, Road B, Road C).
  - Search by shop name, owner name, phone number.
  - Create & Edit shop modal (mandatory road dropdown selection, opening due amount, credit limit).
- [ ] Retailer Ledger view: Shop profile, opening balance, memo transactions history, and current due balance.

#### Phase 4: Product Packaging, Catalog & Central Warehouse Stock (Tier 2 Setup)
- [ ] Create `product_categories` and `products` migrations & models.
- [ ] Support packaging types:
  - Cartoon type (multi-item carton packaging)
  - 5kg packet type (Chanachur, Toast, Biscuits bulk packs)
  - 12 pcs packet type (Cakes, snack packs)
  - Standard pieces/kg
- [ ] Product CRUD UI:
  - Category selection, packaging type dropdown, wholesale price, cost price, min stock alert.
- [ ] **Mominul Bekari Warehouse Stock Engine**:
  - `stock_movements`: Complete immutable stock ledger logging every balance change.
  - `stock_adjustments`: Physical audit vs system count reconciliation with manager approval.
  - `damage_scrap_inventory`: Quarantine store for damaged/expired products.
  - Low-stock visual alerts & badge notifications (when stock $\le$ `min_alert_quantity`).

#### Phase 5: Supplier Procurement & Purchase Stock-In Engine (Tier 1 Inflow)
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

#### Phase 6: Vehicle Mobile Stock Loading & Trip Management (Mominul Bekari -> Road)
- [ ] Create `vehicle_trips` and `vehicle_trip_items` migrations and models.
- [ ] Implement `TripService`, `StoreTripRequest`, and `VehicleTripController`.
- [ ] Trip Loading Sheet UI:
  - Select Road (Road A, B, or C) -> auto-populate vehicle & driver info.
  - Add products and loading quantities.
  - **Stock Validation**: Prevent loading more than available central warehouse stock at Mominul Bekari.
  - **Stock Transfer**: Deduct loaded quantities from `warehouse_stock`, assign to `vehicle_trip_items`, log `LOAD_TO_VEHICLE` movement.
- [ ] Dispatch Vehicle: Mark trip as `DISPATCHED`.
- [ ] Printable Vehicle Loading Manifest / Gate Pass for the driver.

#### Phase 7: Sales Memo, Billing & Credit (Due) Engine (Road -> Retailer Delivery)
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

#### Phase 8: Damage Returns, Custom Deductions & Scrap / Supplier Claims
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

#### Phase 9: Road-Wise Sales, Collections & Stock Reconciliation
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

#### Phase 10: Operational Expenses, General Settings, DB Backup, Frontend Landing Page & Final QA
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

---

### 5. The Complete Supply Chain & Stock Flow Diagram

```
    ┌─────────────────────────────────────────────────────────────┐
    │              TIER 1: MANUFACTURING SUPPLIERS                │
    │     (Supplying Bulk Chanachur, Toast, Biscuits, Cakes)      │
    └──────────────────────────────┬──────────────────────────────┘
                                   │ (Purchase Invoice: PURCHASE_IN)
                                   ▼
    ┌─────────────────────────────────────────────────────────────┐
    │           TIER 2: MOMINUL BEKARI (CENTRAL WHOLESALE)        │
    │               - Central Warehouse Saleable Stock            │
    │               - Supplier Payment & Due Ledger               │
    │               - Road Allocation & Vehicle Loading Sheets    │
    └──────────────────────────────┬──────────────────────────────┘
                                   │
         ┌─────────────────────────┼─────────────────────────┐
         │ (LOAD_TO_VEHICLE)       │ (LOAD_TO_VEHICLE)       │ (LOAD_TO_VEHICLE)
         ▼                         ▼                         ▼
 ┌───────────────┐         ┌───────────────┐         ┌───────────────┐
 │ Road A Vehicle│         │ Road B Vehicle│         │ Road C Vehicle│
 │ (1 Vehicle)   │         │ (1 Vehicle)   │         │ (1 Vehicle)   │
 └───────┬───────┘         └───────┬───────┘         └───────┬───────┘
         │                         │                         │
         ▼                         ▼                         ▼
 ┌───────────────┐         ┌───────────────┐         ┌───────────────┐
 │ TIER 3: ROAD A│         │ TIER 3: ROAD B│         │ TIER 3: ROAD C│
 │ 100 Retailers │         │  50 Retailers │         │  75 Retailers │
 └───────┬───────┘         └───────┬───────┘         └───────┬───────┘
         │                         │                         │
         ├─ Sales Memos & Cash     ├─ Sales Memos & Cash     ├─ Sales Memos & Cash
         │  Collection             │  Collection             │  Collection
         │                         │                         │
         ├─ Damage/Expiry Return   ├─ Damage/Expiry Return   ├─ Damage/Expiry Return
         │  Deductions             │  Deductions             │  Deductions
         │                         │                         │
         ▼                         ▼                         ▼
┌─────────────────────────────────────────────────────────────────────────┐
│        DAILY ROAD RECONCILIATION & RETURN TO MOMINUL BEKARI             │
└────────────────┬───────────────────────────────────────┬────────────────┘
                 │                                       │
(RETURN_FROM_VEHICLE Unsold Goods)              (Damaged & Expired Goods)
                 │                                       │
                 ▼                                       ▼
  ┌─────────────────────────────┐         ┌─────────────────────────────┐
  │ MOMINUL BEKARI WAREHOUSE    │         │  SCRAP QUARANTINE STORE     │
  │ (Restored to Saleable Stock)│         │  (Claimed Back to Supplier  │
  └─────────────────────────────┘         │   or Disposed as Scrap)     │
                                          └─────────────────────────────┘
```

#### Dual-Ledger Financial Integrity:
1. **Upstream (Supplier ⟷ Mominul Bekari):**
   - Purchase Invoice $\implies$ Mominul Bekari Payable increases.
   - Payment to Supplier $\implies$ Mominul Bekari Payable decreases.
2. **Downstream (Mominul Bekari ⟷ Retailers based on Road):**
   - Sales Memo $\implies$ Retailer Receivable increases.
   - Cash / MFS Collection $\implies$ Retailer Receivable decreases.
   - Damage / Expiry Credit $\implies$ Retailer Receivable decreases.

#### Key Stock Invariant Equation:
For every product $P$ on any vehicle trip $T$:
$$\text{Loaded}(P) = \text{Sold}(P) + \text{Damaged}(P) + \text{Returned}(P)$$
If $\text{Loaded} \ne \text{Sold} + \text{Damaged} + \text{Returned}$, a **Trip Reconciliation Discrepancy** alert is raised requiring driver/manager sign-off before closing the trip.

---

### 6. Memo Calculation Formula Specifications

For every sales memo issued to a retailer on a road:

$$\text{Subtotal} = \sum (\text{Item Quantity} \times \text{Unit Wholesale Price})$$

$$\text{Net Bill} = \text{Subtotal} - \text{Damage Deduction} - \text{Custom Deduction}$$

$$\text{Total Outstanding} = \text{Previous Due} + \text{Net Bill}$$

$$\text{Current Due} = \text{Total Outstanding} - \text{Paid Amount}$$

#### Status Auto-assignment:
- If $\text{Current Due} \le 0 \implies \text{PAID}$
- If $\text{Paid Amount} > 0 \land \text{Current Due} > 0 \implies \text{PARTIAL}$
- If $\text{Paid Amount} == 0 \land \text{Current Due} > 0 \implies \text{DUE}$

---

### 7. Step-by-Step Tracker File Reference

A dedicated tracking file `PROGRESS_TRACKER.md` is maintained in the root directory.
Before beginning any phase or after completing any task, check and update `PROGRESS_TRACKER.md` to prevent duplicated work or token-limit context loss.
