# 🔼 UPGRADE GUIDE

> This guide helps you safely upgrade your Unopim installation. Follow the **manual upgrade steps** or use the **automated upgrade script** for minor patches.

([See change impact classification details](CHANGE_IMPACT_CLASSIFICATION.md))

## 🔴 High Impact Changes

- **Constructor Signature Changes**  
  Existing class constructors have been modified:
  - `Exporter` (`Product/Exporter.php`): Added new dependency `ProductSource`.
  - `AbstractExporter` class now rely on `$exportBuffer`, altering the construction and initialization flow.
- **Interface Contract Update**  
  - `BufferInterface::addData` signature changed from:
    ```php
    public function addData($item, $filePath, array $options = []);
    ```
    to:
    ```php
    public function addData($item);
    ```
- **Export Source Refactor**  
  - Major logic change in `getResults()` flow to handle ElasticSearch-based or generic database exports.
- **Export Finalization Workflow Changed**  
  - Export file generation now handled via `flush()` in `Export.php` rather than per-batch.

---

## 🟠 Medium Impact Changes

- Introduced `AbstractCursor` and `AbstractElasticCursor` as base cursor classes for streaming exports.
- Added `ProductCursor` to support paginated export via ElasticSearch or DB query.
- Integrated [`openspout/openspout`](https://github.com/openspout/openspout) for efficient streaming file generation (CSV/XLSX).
- Introduced `JSONFileBuffer` to handle intermediate export buffering for improved performance.
- Optimized export preparation by caching and pre-processing common values (e.g., attribute values, super attributes).
- Logging and profiling improvements (e.g., execution timing in `prepareProducts()`).
- **Added attribute option datagrid** in select and multiselect attributes to support managing large datasets efficiently.
- **Introduced dynamic column and filter management** in the product datagrid, allowing control over visible columns and filters.

---

## 🟢 Low Impact Changes

- Code formatting, naming standardization, and file organization improvements.
- Removed unused methods and redundant logic (e.g., unused `getNextItemsFromIds()`).
- Updated "code" validation rule** to only allow underscore `_` as a special character.

---

- [Manual Upgrade Steps](#manual-upgrade-steps)
- [Automated Upgrade Script](#automated-upgrade-script)

---

<a name="manual-upgrade-steps"></a>
## 🛠️ Manual Upgrade Steps

### 1. **Backup Your Current Project**

Before starting the upgrade process:

* **Backup your full project**
* **Backup your database** using `mysqldump` or your preferred method:

```bash
mysqldump -u your_db_user -p your_db_name > backup.sql
```
* **Stop processes** either wait for all processes to stop or stop the queue worker safely.

**Make sure to keep these backups safe in case the upgrade process encounters any problem.**

### 2. **Download the Release**

* Visit the [Unopim GitHub latest release page](https://github.com/unopim/unopim/releases/latest)
* Download the `.zip` file for the latest version
* Extract the contents to a folder on your system.

### 3. **Copy Necessary Files**

Copy the following files from your existing Unopim project to the newly extracted version:

* `.env` file
* `storage/` folder (to keep your data intact like images)

This ensures your environment settings and any uploaded files stay intact during the upgrade.

### 4. **Install Dependencies**

Navigate to the extracted folder, and install the necessary dependencies with Composer:

```bash
composer install
```

### 5. **Run Database Migrations**

Run the following command to apply any necessary database schema updates:

```bash
php artisan migrate
```

### 6. **Clear Cache & Link Storage**

Ensure that the system is cleared of any cached data and properly linked:

```bash
php artisan optimize:clear
php artisan storage:link
```

This step will optimize your application and create the necessary symbolic link to your `storage` folder.

### 7. **Restart the Queue Worker**

If you are using a process manager like Supervisor to manage the `queue:work` command, restart the relevant service to apply the changes. Replace `unopim-worker` with your actual worker name, if different:

```bash
sudo supervisorctl restart unopim-worker
```

---

### 8. **Rebuild Elasticsearch Indexes**

If Elasticsearch service is running, you must clear and rebuild the indexes to reflect updated structures or data:

```bash
php artisan unopim:elastic:clear   # Clear existing Elasticsearch data
php artisan unopim:product:index   # Re-index all products
php artisan unopim:category:index  # Re-index all categories


## ✅ Upgrade Complete!

After following these steps, your Unopim should be successfully upgraded. Test your application thoroughly to make sure everything works as expected.

---

<a name="automated-upgrade-script"></a>
## 🛠️ Automated Upgrade Script

### 1. Backup your project

Although the upgrade script automatically creates the backup of your project as well as the database, For additional safety you can manually keep the backups in case the upgrade process encounters any problems.

Before starting the upgrade process:

* **Backup your full project**
* **Backup your database** using `mysqldump` or your preferred method:

```bash
mysqldump -u your_db_user -p your_db_name > backup.sql
```
* **Stop processes** either wait for all processes to stop or stop the queue worker safely.

**Make sure to keep these backups safe in case the upgrade process encounters any problem.**

### 2. Execute the Script

Place the script in the directory of your Unopim project and execute it from the terminal:

```bash
./upgrade.sh
```

---

## ✅ Upgrade Complete!

After following these steps, your Unopim should be successfully upgraded. Test your application thoroughly to make sure everything works as expected.
