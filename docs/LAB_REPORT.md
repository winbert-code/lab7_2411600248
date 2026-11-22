# Laboratory Exercise 7 — Advanced MVC Architecture with Laravel

**Student:** Winbert Timon  
**Student ID:** 2411600248  
**Project:** ByteStock Inventory — Lab 7  
**Repository:** <https://github.com/winbert-code/lab7_2411600248>  
**Application URL:** <http://127.0.0.1:8002>  
**Database:** `lab7_inventory`

---

## 1. Data Integrity and Authorization

### 1.1 Soft deletes in Laravel

Laravel soft deletes do not remove a row from the database. Instead, the
`Illuminate\Database\Eloquent\SoftDeletes` trait adds a `deleted_at` timestamp.
Normal Eloquent queries automatically exclude rows where `deleted_at` is not
null, while `withTrashed()` and `onlyTrashed()` can include or isolate them.
`restore()` simply clears the timestamp.

In this project the `Supplier` model uses `SoftDeletes` (Screenshot 1). When a
supplier is removed through `SupplierController@destroy`, the record stays in
the `suppliers` table with its code, contact details, lead time and pricing.
The index page can show archived suppliers, and an administrator or manager can
call the restore action. The custom route binding uses
`Supplier::withTrashed()->findOrFail($value)` so the restore page can still
resolve a deleted supplier.

The advantage over a hard delete is auditability and referential safety. A
supplier may already be linked to purchase orders, products, notes and
documents. Physically deleting it would either break those foreign keys or
erase the history needed to explain past purchases and delivery performance.
For example, a supplier with received purchase orders contributes to the
on-time delivery percentage; deleting it would silently change supplier
performance history. Soft deletion also allows a mistaken removal to be undone
without re-entering all contact and pricing data.

Restoration is performed by `SupplierController@restore`, after
`SupplierPolicy::restore` authorizes the user. Before restoring, the system and
the user should check that the supplier code is not now used by another active
record, that payment terms and lead time are still current, that product
prices on the pivot table are still valid, and that there are no open purchase
orders assigned to a replacement vendor. Restoring does not automatically
rewire products, so those relationships should be reviewed explicitly.

### 1.2 Gates, Policies and the purchase-order workflow

A **Gate** is a closure-based authorization check, usually suited to
application-wide abilities that are not tied to one model instance, such as
`manage-suppliers` or `export-reports`. A **Policy** is a class whose methods
correspond to actions on a specific model and therefore receives the model
instance. Policies are better when the decision depends on record state.

This project uses both. Gates in `AppServiceProvider` control module-level
capabilities (`admin`, `manage-inventory`, `manage-users`, `create-orders`,
`fulfil-orders`, `export-reports`, `manage-suppliers`, `approve-orders`,
`receive-orders` and `approve-high-value-orders`). Route middleware applies
them before a controller runs. `SupplierPolicy` controls supplier model
actions, while `PurchaseOrderPolicy` (Screenshot 4) controls purchase-order
actions with `authorize()` inside the controller.

The purchase-order rules are:

- Any authenticated user may view the list and a particular order.
- Staff or higher roles may create an order; `create-orders` is checked.
- A draft order may be edited only by its creator or a manager.
- Only pending orders may be approved or rejected, and the actor must have
  `approve-orders`.
- Managers or administrators may send an approved order to the supplier.
- Warehouse, manager or administrator roles may receive an approved/sent
  order; receiving writes stock-in transactions.
- Administrators bypass ordinary checks through the `before()` method, but
  business-state checks such as `canBeApproved()` are still enforced in the
  controller.

These rules are enforced at three layers: route middleware for module access,
policy calls for model actions, and model state helpers such as `isDraft()`,
`canBeApproved()` and `canBeReceived()` in the controller.

### 1.3 Multi-level approval for high-value orders

The multi-level workflow is already implemented. `config/replenishment.php`
defines `high_value_threshold` as 50,000 PHP, and
`PurchaseOrder::isHighValue()` compares the calculated order total with that
threshold. `approval_level` records the current stage: `1` for first approval
and `2` for final approval.

When a manager approves a high-value pending order, the controller sets
`approval_level` to `1`, adds an approval note, logs the activity, notifies
administrators and leaves the status as `pending`. The policy then refuses a
second approval from a non-administrator while `approval_level >= 1`. An
administrator's approval sets the status to `approved`, records `approved_by`
and `approved_at`, sets `approval_level` to `2` and queues the approval email.
Normal orders skip the first stage and are approved immediately. The dashboard
widget `awaitingFinal` count exposes orders waiting for administrator approval.

---

## 2. Automation, Scheduling and Notifications

### 2.1 Reorder point calculation and automated detection

The reorder point is:

> **Reorder point = (average daily usage × lead time) + safety stock**

`ReorderService` (Screenshot 3) calculates average daily usage from stock-out
transactions in the last 30 days configured by `usage_window_days`. Lead time
is taken from the primary supplier/product pivot where available, then the
supplier record, then the configured seven-day default. Safety stock comes
from the product field or `default_safety_stock`. The suggested quantity uses
a simplified EOQ:

> `sqrt((2 × annual demand × ordering cost) / holding cost)`

with a 20% holding-cost rate, a 500 PHP ordering cost, minimum/package quantity
rounding, and a floor that covers at least the remaining lead-time demand plus
safety stock.

Automated detection runs through `ReorderService::getProductsNeedingReorder()`.
It aggregates usage in one query, loads active products with their supplier
and category, computes each reorder point and returns products whose current
stock is at or below that point. Results are cached as plain scalar arrays for
60 seconds; the command calls the service with `fresh: true` so scheduled runs
always recalculate.

The flow is: scheduler invokes `replenishment:check-reorder`; the command
prints a table of alerts; alerts are grouped by supplier; products with an
open draft/pending/sent order inside `dedupe_days` are filtered out; a draft
purchase order with line items is created per supplier inside a database
transaction; an activity log and system note are written; managers and
administrators are notified. Screenshot 16 shows the command output.

Fully automatic sending or approval is risky: a demand spike, wrong lead time,
stale supplier price or duplicate order could commit the business to an
unnecessary purchase. This implementation therefore creates **drafts only**,
de-duplicates recent open orders, keeps human approval in the workflow, logs
every generated order and offers `--dry-run` for safe inspection.

### 2.2 Laravel Task Scheduling

Laravel's scheduler defines recurring work in PHP rather than creating one
cron entry per task. A single operating-system cron entry invokes
`php artisan schedule:run` every minute; Laravel then decides which due tasks
to execute. This makes the schedule version-controlled, readable and portable.

The schedule is declared in `app/Console/Kernel.php` (Screenshot 6):

```php
$schedule->command('replenishment:check-reorder')
    ->dailyAt('01:00')
    ->timezone('Asia/Manila')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/replenishment.log'));
```

`withoutOverlapping()` prevents a second run if the previous one has not
finished, `runInBackground()` avoids holding the scheduler process, and output
is appended to `storage/logs/replenishment.log`. `php artisan schedule:list`
shows `0 17 * * *` because 01:00 Asia/Manila is 17:00 UTC on the previous day.

If the command fails, the exception is written to the Laravel log and the
command returns a non-zero status. A production deployment should add
`->onFailure()` to notify administrators through Slack/email and monitor the
log. The command already catches the business case of no active administrators
with a warning. Compared with traditional cron, Laravel's API keeps timing,
timezone, overlap protection and failure hooks beside the application code;
cron is still the single trigger, but all meaningful scheduling logic lives in
Laravel.

### 2.3 Mailables and queued notifications

A Laravel Mailable is a class that builds an email envelope, content view and
optional attachments. This project has four queued mailables in `app/Mail`:
`PurchaseOrderCreated`, `PurchaseOrderApproved` (Screenshot 5),
`PurchaseOrderSent` and `LowStockAlert`. Each implements `ShouldQueue`, uses
the `Queueable` and `SerializesModels` traits, and renders a Blade template
from `resources/views/emails`. Events dispatch them with
`Mail::to(...)->queue(...)`, while in-app bell alerts use database
notifications.

Queuing moves SMTP work outside the request/command cycle, so a slow mail
server cannot delay the user or the scheduler. The local `.env` uses
`QUEUE_CONNECTION=database` and `MAIL_MAILER=log`; the generated jobs table
stores queued messages and `php artisan queue:work --stop-when-empty` processes
them. Screenshot 15 shows the captured `PurchaseOrderApproved` message.

For a more advanced queue setup, the `.env` can select `redis` or `sqs`, the
queue worker can be supervised, and failed jobs can be stored with
`queue:failed-table`. Administrator-customizable templates would be stored in
the database (subject, HTML body, available placeholders) and rendered through
a small template service, with versioned defaults in Blade as a fallback.

---

## 3. Dashboard Performance and Feature Extension

### 3.1 View Composers and query optimization

A View Composer is a callback registered with `View::composer()` that supplies
data whenever a specific Blade view is rendered. `ViewServiceProvider` binds
four procurement widgets: pending approvals, recent purchase orders, supplier
performance and reorder alerts. `DashboardController` therefore does not need
to know how each widget obtains its data.

Each composer uses `Cache::remember()` with the configured 60-second TTL.
Queries are narrowed with counts/sums in SQL, `take(5)` or `take(6)` limits,
selected columns, eager loading and one grouped usage query for reorder
calculation. The cached payloads are **plain arrays of scalar values**, not
Eloquent models or Collections, because the production cache store is the
database and serialized model instances do not round-trip reliably. API
controllers use the same pattern with shorter TTLs.

### 3.2 Multiple dashboard modules without slowing the page

I would keep each module behind a read model or service method with an explicit
contract, for example `Dashboard\PendingOrdersSummary` or a `DashboardData`
aggregator that calls the existing services. Controllers should not query
models directly for unrelated modules. Each module would choose the smallest
query needed, use pagination/cursors for feeds, eager-load only displayed
relations, add database indexes on filtered columns, and cache with a TTL
matched to how quickly the data changes.

Expensive metrics can be precomputed by scheduled jobs into summary tables,
while live counters can be loaded asynchronously through the existing API
endpoints. API Resources keep the response shape stable, and the dashboard can
poll only the widgets that change frequently. Cache keys should include tenant
or filter parameters, and invalidating a write should be explicit for affected
widgets.

### 3.3 Goods Received Notes, barcode scanning and supplier portal

A Goods Received Note (GRN) feature would add a `goods_received_notes` table
with number, purchase order ID, supplier ID, received date, receiver ID,
status and notes, plus a `goods_received_note_items` table with purchase order
item ID, expected quantity, received quantity, accepted/rejected quantity,
unit cost and optional batch/serial numbers. `PurchaseOrder` has
`hasMany(GoodsReceivedNote)`, `PurchaseOrderItem` has
`hasMany(GoodsReceivedNoteItem)`, and received quantities on the PO item are
derived from GRN lines. This replaces the current single `received_quantity`
value with an auditable delivery history and supports partial deliveries.

Barcode scanning can use a browser `BarcodeDetector` or a handheld scanner that
types into a focused input. The receiving page would resolve SKU or
GTIN/barcode to a `PurchaseOrderItem`, increment the scanned quantity, flag
over-receipts and submit the accumulated GRN. Debouncing, duplicate-scan
detection and server-side validation are important.

A supplier portal would add a `suppliers` authentication guard, a
`supplier_users` table and a dedicated login. Portal controllers would be
scoped by `supplier_id` so a supplier can only see its own purchase orders and
GRNs. Suppliers could acknowledge an order by setting `acknowledged_at` and
adding a note; policies would prevent them from changing prices or status
beyond acknowledgement. Email notifications would link to the portal.

---

## 4. Conclusion

This laboratory exercise extended the ByteStock application from a role-based
inventory system into a procurement-oriented Laravel application. The most
important lesson was that advanced MVC is not simply a matter of adding more
controllers: responsibilities must be separated deliberately. Supplier and
purchase-order behavior belongs in models and policies, replenishment
mathematics belongs in a service, recurring work belongs in a scheduled
command, email content belongs in Mailable classes and Blade templates, and
dashboard data belongs in View Composers and API resources.

Implementing reorder points connected several parts of the system that had
previously been separate. A stock-out transaction now influences daily usage,
daily usage influences the reorder point, the reorder point creates a draft
purchase order, and the approval workflow controls when that order can be sent
and received. The experience showed why state transitions need explicit
guards: an order must be pending before approval, approved before sending, and
sent or approved before stock can be received. Adding two-step approval for
high-value orders made it clear that authorization must consider both the
user's role and the current record state.

The testing phase also highlighted practical Laravel issues. Database-backed
caching requires serializable values, so widget caches store plain arrays
instead of Eloquent models. Legacy Lab 6 columns had to remain synchronized
with new Lab 7 columns so earlier records still worked. SQLite and MySQL did
not support the same date functions, so supplier metrics were rewritten with
portable Carbon calculations. Queued mail required a worker, and local mail
was inspected through Laravel's log transport.

Overall, the exercise strengthened my understanding of policies, service
objects, schedulers, queues, caching, exports and clean reporting. It also
demonstrated the value of keeping Lab 7 isolated from Lab 5 and Lab 6 through
a separate directory, database and port. The resulting system is safer, more
automated and easier to extend with features such as goods received notes,
barcode receiving and a supplier portal.

---

## 5. Output / Results — Screenshots

| # | Screenshot | File |
| --- | --- | --- |
| 1 | VS Code showing the Supplier model code | `screenshots/01-supplier-model.png` |
| 2 | VS Code showing the PurchaseOrder model code | `screenshots/02-purchase-order-model.png` |
| 3 | VS Code showing the ReorderService class | `screenshots/03-reorder-service.png` |
| 4 | VS Code showing the PurchaseOrderPolicy code | `screenshots/04-purchase-order-policy.png` |
| 5 | VS Code showing the Mailable class | `screenshots/05-mailable-class.png` |
| 6 | VS Code showing the scheduled command in Kernel.php | `screenshots/06-scheduled-command.png` |
| 7 | phpMyAdmin showing the suppliers table with data | `screenshots/07-suppliers-table.png` |
| 8 | phpMyAdmin showing the purchase_orders table with data | `screenshots/08-purchase-orders-table.png` |
| 9 | Web browser displaying the Supplier Index page | `screenshots/09-supplier-index.png` |
| 10 | Web browser displaying the Supplier Create form | `screenshots/10-supplier-create.png` |
| 11 | Web browser displaying the Purchase Order Index page | `screenshots/11-purchase-order-index.png` |
| 12 | Web browser displaying the Purchase Order Create form | `screenshots/12-purchase-order-create.png` |
| 13 | Web browser displaying the Purchase Order Show page | `screenshots/13-purchase-order-show.png` |
| 14 | Web browser displaying the Procurement Dashboard with widgets | `screenshots/14-procurement-dashboard.png` |
| 15 | Web browser showing an email notification | `screenshots/15-email-notification.png` |
| 16 | Terminal showing the scheduled command output | `screenshots/16-scheduled-command-output.png` |
| 17 | Web browser displaying the Supplier Performance Report | `screenshots/17-supplier-performance.png` |
| 18 | GitHub repository showing the updated project files | `screenshots/18-github-repository.png` |
