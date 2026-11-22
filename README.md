# ByteStock — Laboratory Exercise 7

Advanced Laravel MVC implementation of **Laboratory Exercise 7: Automated
Inventory Replenishment and Supplier Management System**. This folder is a
separate copy of the Lab 6 project; the Lab 5 and Lab 6 folders, their `.env`
files and their databases were left unchanged.

- **Lab 5:** port `8000`
- **Lab 6:** port `8001`, database `computer_parts_inventory`
- **Lab 7:** port `8002`, database `lab7_inventory`

Repository: <https://github.com/winbert-code/lab7_2411600248>

## Stack

- Laravel 13 / PHP 8.3
- MySQL through XAMPP (`lab7_inventory`)
- Bootstrap 5 + Bootstrap Icons (CDN)
- Server-rendered Blade views; no npm build step required
- `barryvdh/laravel-dompdf` for PDF exports

## Quick start

1. Start Apache and MySQL in XAMPP, then create the Lab 7 database:

   ```powershell
   C:\xampp2\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS lab7_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

2. Keep the committed `.env.example` as the template. The working `.env` is
   **not committed** and uses these Lab 7 values:

   ```dotenv
   APP_URL=http://127.0.0.1:8002
   DB_DATABASE=lab7_inventory
   QUEUE_CONNECTION=database
   CACHE_STORE=database
   MAIL_MAILER=log
   ```

3. Install dependencies if `vendor/` is absent, then migrate and seed:

   ```powershell
   composer install
   C:\xampp2\php\php.exe artisan migrate:fresh --seed --force
   ```

4. Serve the application on port `8002`:

   ```powershell
   C:\xampp2\php\php.exe artisan serve --host=127.0.0.1 --port=8002
   ```

5. Open <http://127.0.0.1:8002> and sign in:

   | Email | Password | Role |
   | --- | --- | --- |
   | `winbert@gmail.com` | `winbert906` | admin |
   | `manager@bytestock.test` | `manager123` | manager |
   | `staff@bytestock.test` | `staff123` | staff |
   | `warehouse@bytestock.test` | `warehouse123` | warehouse |

## Lab 7 features

| Lab part | Implementation |
| --- | --- |
| 1 — Supplier CRUD | `Supplier` model with soft deletes, unique codes, lead time, payment terms, minimum order amount, status, many-to-many product catalogue, polymorphic notes/documents, search/filter, create/edit/show/restore pages and `SupplierPolicy`. |
| 2 — Purchase order workflow | `PurchaseOrder` + `PurchaseOrderItem` with `reference_number`, supplier link, expected/received dates, taxes, status history, draft/pending/approved/sent/received/rejected/cancelled transitions, rejection reasons and receiving that writes stock-in transactions. |
| 3 — Reorder service and scheduler | `ReorderService` computes reorder point as `(average daily usage × lead time) + safety stock` and suggested quantity with a simplified EOQ. `replenishment:check-reorder` runs daily at 01:00 Asia/Manila, groups alerts by supplier, de-duplicates open orders and creates draft POs. |
| 4 — Mailables | Queued `PurchaseOrderCreated`, `PurchaseOrderApproved`, `PurchaseOrderSent` and `LowStockAlert` mailables with Blade templates, plus database bell notifications. |
| 5 — Dashboard widgets | View Composers for pending approvals, recent purchase orders, supplier performance and reorder alerts; cached as plain arrays with a 60-second TTL; paired with JSON API endpoints. |
| 6 — Supplier performance | On-time percentage, received order count, average delivery days, order accuracy and total approved value, shown in a searchable report with CSV/PDF export. |
| 7 — Advanced search and filtering | Suppliers, purchase orders, products and performance reports support text search plus status/date/supplier filters. |
| 8 — Exports | CSV and PDF exports for products, purchase orders, transactions and supplier performance under `/exports/...`. |
| 9 — Testing | 48 automated tests / 255 assertions covering replenishment, approvals, policies, receiving, printable receipts, dashboard/API, exports, search and soft deletes. |
| 10 — Documentation | This README plus `docs/LAB_REPORT.md` and the 18 required screenshots in `screenshots/`. |

## Authorization

Gates in `App\Providers\AppServiceProvider` guard module-level abilities such as
`manage-suppliers`, `approve-orders`, `receive-orders` and `export-reports`.
`PurchaseOrderPolicy` guards model-specific actions. Managers can approve normal
orders; orders above `config('replenishment.high_value_threshold')` require a
manager's first approval followed by an administrator's final approval. Draft
orders may only be changed by their creator or a manager.

## Scheduled command and queues

```powershell
# Show the registered schedule
C:\xampp2\php\php.exe artisan schedule:list

# Preview the reorder suggestions without changing data
C:\xampp2\php\php.exe artisan replenishment:check-reorder --dry-run

# Create draft purchase orders now
C:\xampp2\php\php.exe artisan replenishment:check-reorder

# Process queued mailables
C:\xampp2\php\php.exe artisan queue:work --stop-when-empty

# Run the whole test suite
C:\xampp2\php\php.exe artisan test
```

The scheduler entry is defined in `app/Console/Kernel.php`:

```php
$schedule->command('replenishment:check-reorder')
    ->dailyAt('01:00')
    ->timezone('Asia/Manila')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/replenishment.log'));
```

With `MAIL_MAILER=log`, sent messages are written to
`storage/logs/mail.log`. Change `.env` to SMTP/Mailtrap credentials in a
non-committed local configuration to inspect messages in a real inbox.

## Important files

```text
app/Models/Supplier.php
app/Models/PurchaseOrder.php
app/Models/PurchaseOrderItem.php
app/Policies/SupplierPolicy.php
app/Policies/PurchaseOrderPolicy.php
app/Services/ReorderService.php
app/Services/SearchService.php
app/Mail/*.php
app/Console/Kernel.php
app/Console/Commands/CheckReorderPoints.php
app/Providers/ViewServiceProvider.php
app/Http/Controllers/Api/DashboardController.php
app/Http/Controllers/SupplierController.php
app/Http/Controllers/PurchaseOrderController.php
app/Http/Controllers/SupplierPerformanceController.php
app/Http/Controllers/ExportController.php
config/replenishment.php
database/migrations/*supplier*
database/migrations/*purchase_order*replenishment*
database/seeders/*
resources/views/suppliers/*
resources/views/purchase-orders/*
resources/views/widgets/*
resources/views/emails/*
resources/views/sales-orders/receipt.blade.php
docs/LAB_REPORT.md
docs/Lab7_2411600248_Timon_Filled.docx
docs/Sales_Receipt_DEMO-SO-1001.pdf
screenshots/01-*.png … screenshots/19-*.png
```

## Report and submission

The lab report source is `docs/LAB_REPORT.md`. The generated PDF is named
`Lab7_2411600248_Timon.pdf` and is committed at the repository root. It is
produced from the original Laboratory Exercise 7 DOCX template with all
original sections preserved: answers are inserted under **QUESTIONS**, the 18
required screenshots plus a printed sales receipt are inserted under
**Output / Results**, and the conclusion is the final section. The editable,
filled source document is `docs/Lab7_2411600248_Timon_Filled.docx`; it keeps
every original template section, table, header/footer and embedded image.
The printable receipt is also saved separately as
`docs/Sales_Receipt_DEMO-SO-1001.pdf`, and the `sales-orders/receipt` page
includes a **Print receipt** button.

## GitHub

The `.env` file, logs, generated framework caches and `vendor/` are ignored;
never commit local secrets.

```powershell
git init
git add .
git commit -m "Lab 7: advanced MVC, replenishment and supplier management"
git remote add origin https://github.com/winbert-code/lab7_2411600248.git
git push -u origin main
```
