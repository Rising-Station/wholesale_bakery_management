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
│   ├── DamageType.php              # EXPIRED, DAMAGED, TRANSIT_LOSS
│   ├── ReturnType.php              # DAMAGE (Scenario 1), EXCHANGE (Scenario 2)
│   ├── StockMovementType.php       # PURCHASE_IN, PURCHASE_RETURN_SUPPLIER, LOAD_TO_VEHICLE, RETURN_FROM_VEHICLE, DIRECT_SALE, DAMAGE_RETURN, EXCHANGE_RETURN, SCRAP_DISPOSAL, AUDIT_ADJUSTMENT
│   ├── StockLocationType.php       # CENTRAL_WAREHOUSE, VEHICLE_TRANSIT, DAMAGE_QUARANTINE
│   ├── PaymentMethod.php           # CASH, BKASH, NAGAD, BANK_TRANSFER, CHEQUE
│   ├── ExpenseCategoryType.php     # SALARY, SHOP_RENT, ELECTRICITY, GAS, WATER, OTHER
│   └── TripStatus.php              # DRAFT, LOADED, DISPATCHED, COMPLETED
├── Http/
│   ├── Controllers/                # Thin controllers delegating directly to Services
│   │   ├── Auth/
│   │   │   ├── AuthenticatedSessionController.php # Login, Logout, Session lifecycle
│   │   │   └── PasswordResetController.php       # Password reset & recovery
│   │   ├── Admin/
│   │   │   ├── UserController.php                # Staff accounts & role assignments
│   │   │   ├── RoleController.php                # RBAC roles & permission matrix
│   │   │   ├── SupplierController.php            # Tier 1: Supplier directory & balance ledger
│   │   │   ├── PurchaseController.php            # Supplier -> Mominul Bekari procurement & stock-in
│   │   │   ├── StockController.php               # Mominul Bekari central warehouse inventory & alerts
│   │   │   ├── StockAdjustmentController.php     # Physical count audits & variance corrections
│   │   │   ├── RoadController.php                # Road A, B, C configuration
│   │   │   ├── RetailerController.php            # Tier 3: Retail shops directory (mapped to roads)
│   │   │   ├── ProductController.php             # Product catalog & packaging types
│   │   │   ├── VehicleTripController.php         # Mominul Bekari -> Road vehicle dispatch
│   │   │   ├── MemoController.php                # Retailer sales memos & billing
│   │   │   ├── DamageReturnController.php        # Damage credits & scrap tracking
│   │   │   ├── ExpenseController.php             # Operational overhead expenses
│   │   │   └── SettingController.php             # System configuration
│   ├── Middleware/
│   │   ├── EnsureUserIsActive.php                # Blocks deactivated staff (is_active = false)
│   │   └── HandleInertiaRequests.php             # Shares auth.user, roles, permissions globally
│   └── Requests/                   # Dedicated request validation classes
│       ├── Auth/
│       │   └── LoginRequest.php                  # Validates credentials & applies rate limiting
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

#### 3.0 Authentication & User Management (RBAC) System
- **`users`**:
  - `id` (PK), `name` (string), `email` (string, unique), `phone` (string, nullable, unique), `password` (hashed string), `is_active` (bool, default true), `email_verified_at` (timestamp, nullable), `remember_token` (string, nullable), timestamps.
  - Linked to audit trails across all transactions (`created_by`, `approved_by`, `collected_by`).
- **`roles`** (`spatie/laravel-permission`):
  - `id` (PK), `name` (string, e.g. `SuperAdmin`, `BranchManager`, `Accountant`, `SalesDriver`, `InventoryStorekeeper`), `guard_name` (string, default `web`), timestamps.
- **`permissions`** (`spatie/laravel-permission`):
  - `id` (PK), `name` (string, dot-notated, e.g. `memos.create`, `trips.reconcile`, `stock.adjust`), `guard_name` (string, default `web`), timestamps.
- **`model_has_roles`** & **`role_has_permissions`**:
  - Standard polymorphic pivot tables establishing strict many-to-many associations between users, roles, and granular abilities.

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
  - `id` (PK), `product_id` (FK -> `products`), `movement_type` (Enum: `PURCHASE_IN`, `PURCHASE_RETURN_SUPPLIER`, `LOAD_TO_VEHICLE`, `RETURN_FROM_VEHICLE`, `DIRECT_SALE`, `DAMAGE_RETURN`, `EXCHANGE_RETURN`, `SCRAP_DISPOSAL`, `AUDIT_ADJUSTMENT`), `source_location` (Enum: `CENTRAL_WAREHOUSE`, `VEHICLE_TRANSIT`, `DAMAGE_QUARANTINE`), `destination_location` (Enum), `reference_type` (Purchase, Trip, Memo, Adjustment, Claim), `reference_id` (unsigned bigint), `quantity` (decimal), `balance_before` (decimal), `balance_after` (decimal), `remarks` (string), `created_by` (FK -> `users`), timestamps.
- **`stock_adjustments`**:
  - `id` (PK), `adjustment_no` (string, unique), `adjustment_date` (date), `reason` (string: Physical count variance, shrinkage, miscount), `status` (Enum: `PENDING`, `APPROVED`), `approved_by` (FK -> `users`), `created_by` (FK -> `users`), timestamps.
- **`stock_adjustment_items`**:
  - `id` (PK), `stock_adjustment_id` (FK -> `stock_adjustments`), `product_id` (FK -> `products`), `system_stock` (decimal), `physical_stock` (decimal), `variance_quantity` (decimal), `adjustment_type` (Enum: `ADD`, `REDUCE`), timestamps.
- **`damage_scrap_inventory`**:
  - `id` (PK), `product_id` (FK -> `products`), `retailer_id` (FK, nullable), `road_id` (FK, nullable), `damage_type` (Enum: `DAMAGED`, `EXPIRED`, `TRANSIT_LOSS`), `quantity` (decimal), `status` (Enum: `QUARANTINED`, `RETURNED_TO_SUPPLIER`, `DESTROYED_SCRAP`), `disposal_date` (date, nullable), `disposal_notes` (text), timestamps.

#### 3.3 Roads & Route Vehicle Dispatch (Mominul Bekari -> Road)
- **`roads`**:
  - `id` (PK), `name` (e.g., Road A, Road B, Road C), `code` (e.g., RD-A), `vehicle_number`, `driver_name`, `driver_phone`, `is_active` (bool), timestamps.
- **`vehicle_trips`**:
  - `id` (PK), `road_id` (FK -> `roads`), `trip_date` (date), `vehicle_number`, `driver_id` (FK -> `users`), `status` (Enum: `DRAFT`, `LOADED`, `DISPATCHED`, `COMPLETED`), `notes` (text), timestamps.
- **`vehicle_trip_items`**:
  - `id` (PK), `vehicle_trip_id` (FK -> `vehicle_trips`), `product_id` (FK -> `products`), `loaded_quantity` (decimal), `sold_quantity` (decimal, default 0), `damaged_quantity` (decimal, default 0), `exchanged_quantity` (decimal, default 0), `returned_quantity` (decimal, default 0), timestamps.

#### 3.4 Tier 3: Retailers (Road-Based Shops & Memos)
- **`retailer_shops`**:
  - `id` (PK), `road_id` (FK -> `roads`), `shop_name`, `owner_name`, `phone`, `alt_phone`, `address`, `credit_limit` (decimal), `opening_due` (decimal), `current_due` (decimal), `is_active` (bool), timestamps.
- **`sales_memos`**:
  - `id` (PK), `memo_no` (string, unique, e.g. MEMO-2026-0001), `retailer_id` (FK -> `retailer_shops`), `road_id` (FK -> `roads`), `vehicle_trip_id` (FK, nullable), `memo_date` (date), `subtotal` (decimal), `damage_deduction` (decimal, default 0), `exchange_deduction` (decimal, default 0), `custom_deduction` (decimal, default 0), `grand_total` (decimal), `paid_amount` (decimal, default 0), `due_amount` (decimal, default 0), `previous_due` (decimal, default 0), `total_net_payable` (decimal), `memo_status` (Enum: `PAID`, `PARTIAL`, `DUE`, `CANCELLED`), `delivery_status` (Enum: `PENDING`, `DELIVERED`, `RETURNED`), `notes`, `created_by` (FK -> `users`), timestamps.
- **`sales_memo_items`**:
  - `id` (PK), `sales_memo_id` (FK -> `sales_memos`), `product_id` (FK -> `products`), `packaging_type` (Enum), `quantity` (decimal), `unit_price` (decimal), `line_total` (decimal), timestamps.
- **`damage_returns`** (or `product_returns`):
  - `id` (PK), `sales_memo_id` (FK, nullable), `retailer_id` (FK -> `retailer_shops`), `product_id` (FK -> `products`), `return_type` (Enum: `DAMAGE`, `EXCHANGE`), `damage_type` (Enum: `DAMAGED`, `EXPIRED`, `TRANSIT_LOSS`, nullable), `quantity` (decimal), `unit_deduction_rate` (decimal), `total_deduction_amount` (decimal), `affects_sellable_stock` (bool, default false: false for scrap quarantine, true for exchange stock increase), `is_custom_amount` (bool, default false), `notes`, `approved_by` (FK -> `users`), timestamps.
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

#### Phase 2: User Authentication, Role & Permission (RBAC) System
- [ ] Implement Session-based Authentication flow (`/login`, `/logout`, CSRF tokens, session invalidation, and rate limiting with `throttle:6,1`).
- [ ] Create `EnsureUserIsActive` middleware to immediately block/revoke sessions of deactivated staff (`is_active = false`).
- [ ] Install & configure `spatie/laravel-permission` with guard configuration and cache invalidation listeners.
- [ ] Seed standard 5 enterprise roles: `SuperAdmin`, `BranchManager`, `Accountant`, `SalesDriver`, `InventoryStorekeeper` alongside 43 granular permissions (`RoleAndPermissionSeeder`).
- [ ] Expose global Inertia authentication state via `HandleInertiaRequests` middleware (`auth.user`, `auth.user.roles`, `auth.user.permissions`).
- [ ] Role & Permission Management UI (`Admin/Roles/Index.vue`): Interactive category-grouped permission matrix with toggle checkboxes and role CRUD.
- [ ] User Management UI (`Admin/Users/Index.vue`): Staff CRUD, role assignment, active/inactive toggle, password management.
- [ ] Implement route-level and UI-level authorization guards (`can()`, `hasRole()`, and dynamic menu filtering in `AppLayout.vue`).

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
  - **Upstream Purchase Prerequisite & 0-Stock Prevention**: Products with 0 central warehouse stock cannot be loaded onto vehicles. Stock must first be ingested through manufacturing supplier purchases (`PURCHASE_IN`).
  - **Stock Transfer**: Atomically deduct loaded quantities from `warehouse_stock`, assign to `vehicle_trip_items`, log `LOAD_TO_VEHICLE` movement in audit ledger.
- [ ] Dispatch Vehicle: Mark trip as `DISPATCHED`.
- [ ] Printable Vehicle Loading Manifest / Gate Pass for the driver.

#### Phase 7: Sales Memo, Billing & Credit (Due) Engine (Road -> Retailer Delivery)
- [ ] Create `sales_memos`, `sales_memo_items` migrations and models.
- [ ] Implement `MemoService`, `StoreMemoRequest`, and `MemoController`.
- [ ] Sales Memo Creation UI:
  - Filter Retailers strictly by the assigned Road.
  - Select active vehicle trip channel or direct central depot sale.
  - Display selected retailer's existing due balance in real time.
  - Product selection with real-time available stock badges (vehicle stock on road trips, warehouse stock on direct sales).
  - **Zero-Stock Selling Prevention**: 0 stock cannot be sold. Prevents issuing memos for items with 0 stock or quantities exceeding available mobile trip/depot stock.
  - Live calculations: Subtotal + Previous Due - Paid Amount = Net Due.
- [ ] Automated status assignment: `PAID`, `PARTIAL`, `DUE`.
- [ ] Update Retailer current due balance atomically in DB transaction.
- [ ] Deduct sold quantities from vehicle trip stock (or central warehouse stock for direct sales).
- [ ] Printable Sales Memo layouts (Thermal 80mm & standard A4 formats).
- [ ] Direct Retailer Due Payment Collection modal.

#### Phase 8: Returns & Exchanges (Damage Returns, Product Exchanges, Custom Deductions & Scrap / Supplier Claims)
- [ ] Create `damage_returns` (or `product_returns`) and `supplier_return_claims` migrations and models.
- [ ] Implement `DamageReturnService` and FormRequests supporting two distinct operational scenarios:
  - **Scenario 1: Damage / Expiry Return (Defective / Unsellable Goods):**
    - Retailer returns damaged, broken, or expired products.
    - Financial impact: Deducted from the new sales memo (`damage_deduction`), reducing retailer payable amount.
    - Stock impact: Does **NOT** enter sellable warehouse stock. Automatically routed to `damage_scrap_inventory` (Quarantine).
    - Can subsequently be claimed back to the manufacturing supplier (`supplier_return_claims`) or written off (`SCRAP_DISPOSAL`).
  - **Scenario 2: Product Exchange (Good Condition / Resellable Stock Return):**
    - Retailer exchanges good-condition, intact products (e.g. slow-moving stock exchanged for fresh/other items).
    - Financial impact: Deducted from the new sales memo (`exchange_deduction`), offsetting new products taken.
    - Stock impact: **Increases sellable stock** (`affects_sellable_stock = true`).
      - On active delivery trips: increases available vehicle trip inventory (`exchanged_quantity`), allowing driver to resell on the same road or return it to depot.
      - On direct central depot memos: records `StockMovementType::EXCHANGE_RETURN`, immediately increasing Central Warehouse sellable stock.
- [ ] Return UI in Memo Screen:
  - Select Return Type: `Damage Return` (Scenario 1) or `Product Exchange` (Scenario 2).
  - Add product rows with quantity and deduction rate (auto-populated from wholesale price).
  - Custom amount deduction toggle and input field (supervisor override/custom discount).
- [ ] Memo Financial Update:
  - Deduct both `damage_deduction` and `exchange_deduction` along with `custom_deduction` from memo subtotal before computing net payable.
- [ ] Damage & Exchange audit report with filters by road, shop, date, product, and return type (Damage vs Exchange).
- **Phase 8 Verification:**
  - [ ] Scenario 1: Adding damaged items deducts from memo total and routes quantity to scrap quarantine inventory without increasing sellable stock.
  - [ ] Scenario 2: Adding exchanged good items deducts from memo total and increases sellable stock in vehicle trip/central warehouse.

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
---

### 5. Authentication & Authorization (RBAC) System Architecture

The security architecture of Mominul Bekari ERP enforces strict segregation of duties across depot management, warehouse custody, road delivery sales, and financial accounting.

#### 5.1 Authentication System Architecture
- **Stateful Session Authentication:** Powered by Laravel's built-in session guard with HTTP-only, secure, encrypted cookies (`SameSite=Lax`).
- **Login Credentials:** Support authentication using Email address (`email`) or Phone number (`phone`) + Password.
- **Brute-Force & Rate Limiting:** Enforced via `LoginRequest` throttled at a strict 6 attempts per minute (`throttle:6,1`) per IP and username.
- **Active User Middleware (`EnsureUserIsActive`):**
  - Evaluates `$user->is_active === true` on every single authenticated HTTP request.
  - If a staff member is marked inactive or terminated by an administrator, the user's session is terminated immediately, redirected to login with an account suspension notification.
- **Session Security & Invalidation:**
  - `session()->regenerate()` called upon successful login to prevent session fixation attacks.
  - `Auth::logout()`, `session()->invalidate()`, and `session()->regenerateToken()` executed upon logout.
- **Password Security:**
  - Enforces `bcrypt` hashing with configurable work factor (default: 12 rounds).
  - Validation requires minimum 8 characters with mixed case and numeric characters for staff accounts.

#### 5.2 Role-Based Access Control (RBAC) Architecture
Role management is powered by `spatie/laravel-permission` with five primary enterprise roles reflecting operational hierarchy:

1. **`SuperAdmin` (Proprietor / Executive Authority):**
   - **Scope:** Unrestricted system-wide authority.
   - **Capabilities:** User management, role assignments, system settings, database backups, audit trail inspection, supervisor discount overrides, and complete financial reporting.
2. **`BranchManager` (Depot Operations Manager):**
   - **Scope:** Oversees physical distribution operations at the central warehouse and all delivery roads.
   - **Capabilities:** Manages vehicles, road assignments, dispatches trips, approves stock adjustments, manages retailer directories, processes damage return claims to suppliers, logs operational expenses, and performs daily road reconciliation.
   - **Restrictions:** Cannot edit core system settings or trigger/download raw database snapshots.
3. **`Accountant` (Financial Auditor & Cashier):**
   - **Scope:** Fiscal accuracy and cash-flow monitoring.
   - **Capabilities:** Records supplier bill payments, verifies retailer cash/MFS collections, tracks outstanding memo credit, audits overhead expense vouchers, reviews profit/loss and ledger reports.
   - **Restrictions:** Cannot dispatch vehicles, adjust warehouse stock, or alter system user accounts.
4. **`SalesDriver` (Road Vehicle Delivery Agent):**
   - **Scope:** Road-level execution for the driver's assigned vehicle trip.
   - **Capabilities:** Views road-specific retailer shops, creates and prints sales memos during delivery, accepts immediate cash/bKash collections, and records damage/exchange return items from retailers.
   - **Restrictions:** Restricted strictly to their active road. Cannot view central warehouse stock balances, modify supplier procurement, or view administrative overhead expenses.
5. **`InventoryStorekeeper` (Warehouse Inventory Custodian):**
   - **Scope:** Physical custody of goods inside Mominul Bekari's central warehouse.
   - **Capabilities:** Receives incoming purchase shipments (`PURCHASE_IN`), loads vehicle trip stock (`LOAD_TO_VEHICLE`), performs periodic physical stock audits (`stock_adjustments`), and quarantines damaged/expired returns (`damage_scrap_inventory`).
   - **Restrictions:** Cannot issue sales memos, collect money, or modify retail price structures.

#### 5.3 Granular Permissions Catalog (43 Permissions Across 13 Modules)

| Module | Permission Name | Description / Functional Boundary |
| :--- | :--- | :--- |
| **Users** | `users.view` | View staff account directory and profiles |
| | `users.create` | Register new staff members |
| | `users.edit` | Update staff details, reset passwords, toggle active status |
| | `users.delete` | Remove staff accounts with no audit history |
| **Roles** | `roles.view` | Inspect role definitions and assigned permissions |
| | `roles.manage` | Create, update, or modify permission matrix for roles |
| **Suppliers** | `suppliers.view` | Access supplier catalog and outstanding balance ledgers |
| | `suppliers.create` | Register new bakery manufacturing suppliers |
| | `suppliers.edit` | Update supplier contact info and commercial details |
| | `suppliers.delete` | Delete supplier with zero transaction history |
| | `suppliers.payment`| Record cash/bank/MFS payments against supplier dues |
| **Purchases** | `purchases.view` | Inspect purchase bills, invoices, and payment statuses |
| | `purchases.create` | Create new purchase bills for incoming stock |
| | `purchases.edit` | Update unverified purchase orders |
| | `purchases.delete` | Cancel/remove invalid purchase records |
| | `purchases.receive`| Confirm receipt and trigger automated warehouse stock-in |
| **Warehouse & Stock** | `stock.view` | View central warehouse stock balances and audit logs |
| | `stock.adjust` | Initiate or approve physical inventory adjustments |
| | `stock.scrap` | Manage quarantined damage inventory and scrap write-offs |
| **Products** | `products.view` | View product catalog, prices, and packaging specifications |
| | `products.create` | Register new bakery products and packaging types |
| | `products.edit` | Update product wholesale prices, cost prices, alert limits |
| | `products.delete` | Remove products with no recorded stock history |
| **Roads** | `roads.view` | View distribution roads (Road A, B, C) and vehicle assignments |
| | `roads.create` | Create new distribution delivery roads |
| | `roads.edit` | Update road details, assigned vehicles, and driver contacts |
| | `roads.delete` | Delete inactive roads with no associated shops or trips |
| **Retailers** | `retailers.view` | Access road-filtered retailer directory and ledger profiles |
| | `retailers.create` | Register new retail shops under a specific road |
| | `retailers.edit` | Update shop details, credit limits, and contact numbers |
| | `retailers.delete` | Delete retail shops with zero memo history |
| | `retailers.payment`| Collect direct payments against outstanding retailer dues |
| **Vehicle Trips** | `trips.view` | Inspect trip loading manifests and vehicle dispatch status |
| | `trips.create` | Prepare vehicle loading sheets and deduct warehouse stock |
| | `trips.dispatch` | Authorize vehicle departure and generate gate pass |
| | `trips.reconcile`| Reconcile unsold goods, verify collections, and close trip |
| **Sales Memos** | `memos.view` | Browse sales memos and delivery statuses |
| | `memos.create` | Issue sales memos, deduct vehicle stock, update retailer due |
| | `memos.edit` | Modify pending/draft sales memos |
| | `memos.cancel` | Cancel unapproved sales memos with stock reversal |
| | `memos.print` | Generate thermal (80mm) or A4 printable sales memos |
| **Damage & Returns** | `damage.view` | Inspect return logs (Damage Scenario 1 & Exchange Scenario 2) |
| | `damage.create` | Record customer damage/exchange deductions on new memos |
| | `damage.claim` | File formal return claims to manufacturing suppliers |
| **Expenses** | `expenses.view` | Browse operational overhead expense vouchers and totals |
| | `expenses.create` | Log vehicle fuel, depot rent, staff salary, utilities |
| | `expenses.edit` | Modify unverified expense entries and attached vouchers |
| | `expenses.delete` | Remove canceled or invalid expense entries |
| **Governance & Reports** | `reports.view` | View road-wise settlements, sales summaries, balance ledgers |
| | `settings.manage` | Update business name, receipt headers, hotline, SMS toggles |
| | `backup.manage` | Trigger manual DB snapshots and download backup archives |

#### 5.4 Role-to-Permission Matrix

| Functional Capability | SuperAdmin | BranchManager | Accountant | SalesDriver | InventoryStorekeeper |
| :--- | :---: | :---: | :---: | :---: | :---: |
| `users.*` (Staff Directory) | ✔ | View Only | ❌ | ❌ | ❌ |
| `roles.*` (Permission Matrix) | ✔ | ❌ | ❌ | ❌ | ❌ |
| `suppliers.view` | ✔ | ✔ | ✔ | ❌ | ❌ |
| `suppliers.create/edit` | ✔ | ✔ | ❌ | ❌ | ❌ |
| `suppliers.payment` | ✔ | ✔ | ✔ | ❌ | ❌ |
| `purchases.view` | ✔ | ✔ | ✔ | ❌ | ✔ |
| `purchases.create/edit` | ✔ | ✔ | ❌ | ❌ | ❌ |
| `purchases.receive` | ✔ | ✔ | ❌ | ❌ | ✔ |
| `stock.view` | ✔ | ✔ | ❌ | ❌ | ✔ |
| `stock.adjust/scrap` | ✔ | ✔ | ❌ | ❌ | ✔ |
| `products.view` | ✔ | ✔ | ❌ | ❌ | ✔ |
| `products.create/edit` | ✔ | ✔ | ❌ | ❌ | ❌ |
| `roads.view` | ✔ | ✔ | ❌ | ✔ | ❌ |
| `roads.create/edit` | ✔ | ✔ | ❌ | ❌ | ❌ |
| `retailers.view` | ✔ | ✔ | ✔ | ✔ | ❌ |
| `retailers.create/edit` | ✔ | ✔ | ❌ | ✔ (Create) | ❌ |
| `retailers.payment` | ✔ | ✔ | ✔ | ✔ | ❌ |
| `trips.view` | ✔ | ✔ | ✔ | ✔ | ✔ |
| `trips.create` | ✔ | ✔ | ❌ | ❌ | ✔ |
| `trips.dispatch` | ✔ | ✔ | ❌ | ❌ | ❌ |
| `trips.reconcile` | ✔ | ✔ | ✔ | ❌ | ❌ |
| `memos.view/print` | ✔ | ✔ | ✔ | ✔ | ❌ |
| `memos.create` | ✔ | ✔ | ❌ | ✔ | ❌ |
| `damage.view` | ✔ | ✔ | ❌ | ✔ | ✔ |
| `damage.create` | ✔ | ✔ | ❌ | ✔ | ❌ |
| `damage.claim` | ✔ | ✔ | ❌ | ❌ | ❌ |
| `expenses.view` | ✔ | ✔ | ✔ | ❌ | ❌ |
| `expenses.create/edit/delete` | ✔ | ✔ | ✔ | ❌ | ❌ |
| `reports.view` | ✔ | ✔ | ✔ | ❌ | ❌ |
| `settings.manage` | ✔ | ❌ | ❌ | ❌ | ❌ |
| `backup.manage` | ✔ | ❌ | ❌ | ❌ | ❌ |

#### 5.5 Multi-Tier Authorization Enforcement Model

1. **Tier 1: Route Middleware Gates**
   - Routes grouped under `middleware(['auth', 'active.user'])`.
   - Specific route sub-groups protected with Spatie middleware: `middleware('permission:memos.create')`, `middleware('role:SuperAdmin|BranchManager')`.
   - Unauthorized access triggers automated `403 Forbidden` response or redirects to dashboard with an error flash.

2. **Tier 2: Controller & Form Request Authorization**
   - Form Requests implement strict `authorize()` checks:
     ```php
     public function authorize(): bool
     {
         return $this->user()->can('memos.create');
     }
     ```
   - Service classes enforce entity ownership and supervisory approval requirements before committing multi-table database transactions.

3. **Tier 3: Frontend UI Reactive Visibility (Inertia + Vue 3)**
   - Shared authentication payload via `HandleInertiaRequests`:
     ```php
     'auth' => [
         'user' => $request->user() ? [
             'id' => $request->user()->id,
             'name' => $request->user()->name,
             'email' => $request->user()->email,
             'roles' => $request->user()->getRoleNames(),
             'permissions' => $request->user()->getAllPermissions()->pluck('name'),
         ] : null,
     ]
     ```
   - In Vue 3 components, navigation sidebar items and action buttons (e.g., `Add Product`, `Reconcile Trip`, `Collect Payment`) are dynamically evaluated using `$page.props.auth.permissions.includes(...)` or helper `can()`.
   - Prevents visual clutter and eliminates user confusion by rendering only the actionable features relevant to the staff member's operational role.

---

### 6. The Complete Supply Chain & Stock Flow Diagram

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
$$\text{Loaded}(P) + \text{Exchanged}(P) = \text{Sold}(P) + \text{Returned}(P)$$
$$\text{Damaged Items Collected}(P) \longrightarrow \text{Damage Scrap Quarantine Inventory}$$
Where:
- $\text{Exchanged}(P)$ represents good-condition products returned by retailers (Scenario 2), which increase sellable stock available in the trip.
- $\text{Damaged}(P)$ represents defective/expired products returned by retailers (Scenario 1), which are strictly isolated into quarantine scrap and never re-enter sellable inventory.

If $\text{Loaded} + \text{Exchanged} \ne \text{Sold} + \text{Returned}$, a **Trip Reconciliation Discrepancy** alert is raised requiring driver/manager sign-off before closing the trip.

#### 6.1 Upstream Supplier Purchase Prerequisite & Zero-Stock Prevention Invariant
Wholesale bakery distribution strictly enforces the natural supply chain sequence:
$$\mathbf{Supplier\ Purchase\ (Stock\ Ingestion)} \implies \mathbf{Central\ Warehouse\ Stock} \implies \mathbf{Vehicle\ Trip\ Loading} \implies \mathbf{Road\ Sales\ Memo}$$

1. **Prerequisite Rule 1: Central Stock Ingestion Prior to Vehicle Loading**
   - Products are initialized with `warehouse_stock = 0.00`.
   - A product **cannot** be loaded onto any delivery vehicle if its Central Warehouse stock is 0 ($\text{warehouse\_stock} \le 0$) or less than the requested loaded quantity ($\text{warehouse\_stock} < \text{loaded\_quantity}$).
   - Attempting to load unpurchased products throws a domain exception: *"Cannot load ':product': Central Warehouse stock is 0. Please purchase stock from a manufacturing supplier first."*
   - In the UI, the product catalog clearly flags unstocked items, and the trip creation sheet restricts selection to items currently in warehouse stock.

2. **Prerequisite Rule 2: Vehicle Stock Prerequisite for Road Sales Memos (0 Stock Cannot Be Sold)**
   - When issuing a Sales Memo from an active road trip, the requested items must have been loaded onto the vehicle and must possess positive remaining stock:
   $$\text{Available Mobile Stock}(P, T) = (\text{Loaded} + \text{Exchanged}) - (\text{Sold} + \text{Damaged}) > 0$$
   - **0 stock is impossible to sell:** A sales memo cannot sell a product that has 0 stock on the vehicle, nor can the sold quantity exceed $\text{Available Mobile Stock}$.
   - Attempting to sell 0 stock or unstocked products throws a domain exception: *"Product ':product' was not loaded on this vehicle trip. 0 stock cannot be sold."*

3. **Prerequisite Rule 3: Direct Central Warehouse Sales Stock Validation**
   - For direct off-vehicle sales memos, $\text{warehouse\_stock} > 0$ and $\ge \text{quantity}$ are strictly verified in a database transaction with `lockForUpdate()`.

---

### 7. Memo Calculation Formula Specifications

For every sales memo issued to a retailer on a road:

$$\text{Subtotal} = \sum (\text{Item Quantity} \times \text{Unit Wholesale Price})$$

$$\text{Net Bill} = \text{Subtotal} - \text{Damage Deduction} - \text{Exchange Deduction} - \text{Custom Deduction}$$

$$\text{Total Outstanding} = \text{Previous Due} + \text{Net Bill}$$

$$\text{Current Due} = \text{Total Outstanding} - \text{Paid Amount}$$

#### Status Auto-assignment:
- If $\text{Current Due} \le 0 \implies \text{PAID}$
- If $\text{Paid Amount} > 0 \land \text{Current Due} > 0 \implies \text{PARTIAL}$
- If $\text{Paid Amount} == 0 \land \text{Current Due} > 0 \implies \text{DUE}$

---

### 8. Step-by-Step Tracker File Reference

A dedicated tracking file `PROGRESS_TRACKER.md` is maintained in `.agents/rules/PROGRESS_TRACKER.md`.
Before beginning any phase or after completing any task, check and update `.agents/rules/PROGRESS_TRACKER.md` to prevent duplicated work or token-limit context loss.
