# Partial Return & Sub-Unit Exchange Architecture

## Scenarios Handled

### Scenario A: Weight-Based Bulk Pack Partial Exchange (e.g., Chanachur 5kg packet -> 2 kg return)
- **Use Case**: Retailer purchases a 5kg sealed packet of Chanachur (wholesale price: ৳850.00). Next day, retailer wants to return 2 kg and exchange for Cake.
- **Financial Calculation**:
  - Rate per kg = $\text{wholesale\_price} / \text{weight\_per\_unit\_kg} = 850 / 5 = \text{৳}170/\text{kg}$.
  - Credit Deduction = $2\text{ kg} \times \text{৳}170 = \text{৳}340.00$.
- **Stock Routing**:
  - Fractional conversion = $2 / 5 = 0.400$ packet equivalent.
  - Central warehouse sealed inventory is **NEVER** incremented by loose/fractional contents.
  - Quarantined in `damage_scrap_inventory` under `category = 'REPACK_HOLDING'`, `damage_type = 'BROKEN_PACKET'`, `weight_kg = 2.000` for reprocessing/rebagging.

---

### Scenario B: Cartoon / Pieces Master Box Partial Exchange & Damages (e.g., Cake 24 pcs = 1 Cartoon -> 5 pcs return)
- **Use Case**: Retailer purchases Cake supplied in Master Cartoons of 24 pieces (wholesale price: ৳480.00 per cartoon). Later, retailer returns 5 pieces (either damaged/crushed or for exchange with other goods).
- **Financial Calculation**:
  - Rate per piece = $\text{wholesale\_price} / \text{units\_per\_cartoon} = 480 / 24 = \text{৳}20.00/\text{pc}$.
  - Credit Deduction = $5\text{ pcs} \times \text{৳}20 = \text{৳}100.00$.
- **Stock Routing**:
  - Fractional conversion = $5 / 24 = 0.208$ cartoon equivalent.
  - **Damages**:
    - Deducted as `damage_deduction` on sales memo.
    - Routed to `damage_scrap_inventory` with `category = 'SCRAP_DISPOSAL'`, `damage_type = 'DAMAGED'`, `status = 'QUARANTINED'`, `return_unit = 'PCS'`, `pieces_count = 5`.
    - Warehouse sellable cartoon stock does NOT increase.
  - **Exchanges**:
    - Deducted as `exchange_deduction` on sales memo.
    - Routed to `damage_scrap_inventory` with `category = 'REPACK_HOLDING'`, `damage_type = 'BROKEN_PACKET'`, `status = 'REPACK_HOLDING'`, `return_unit = 'PCS'`, `pieces_count = 5`.
    - Warehouse sellable sealed cartoon stock does NOT increase (prevents selling incomplete master boxes).
    - Can be repacked in the bakery packaging bay once 24 pieces are assembled or sold as individual pieces.

---

## Technical Implementation Summary

1. **Database Schema**:
   - `damage_returns`: `return_unit` (`PACKAGE`, `KG`, `PCS`), `sub_unit_quantity`, `conversion_factor`, `condition_status`, `destination_location`.
   - `damage_scrap_inventory`: `category` (`SCRAP_DISPOSAL`, `REPACK_HOLDING`), `weight_kg`, `pieces_count`, `return_unit`.
2. **Memo Service Engine** (`MemoService.php`):
   - Supports pro-rated unit deduction rates for both `units_per_cartoon` / `pieces_per_packet` and `weight_per_unit_kg`.
   - Atomic transaction routing broken packaging into `REPACK_HOLDING` vs `SCRAP_DISPOSAL`.
3. **Frontend UI**:
   - `Memos/Index.vue`: Automatic unit selector (`Full Cartoon/Pack`, `Loose Pieces (Pcs)`, `Loose Weight (Kg)`), live piece-to-cartoon conversion display, and clear routing badges.
   - `Memos/Show.vue`: Shows pieces count (e.g. `5 pcs (0.21 ctn eq)`) and rates per piece on printable and thermal receipts.
   - `Returns/Index.vue`: Metric cards tracking loose pieces (`pieces_count`), loose weight (`weight_kg`), and total units awaiting repackaging.
