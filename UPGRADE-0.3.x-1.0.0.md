# 🔼 UPGRADE GUIDE: Unopim `v0.3.x` → `v1.0.0`

> This guide helps you safely upgrade your Unopim installation from any `v0.3.x` version to `v1.0.0`. You can follow the **manual** steps or you can use the **automated upgrade script** for version 0.3.x to 1.0.0.

---

## 🛠️ MANUAL UPGRADE STEPS

### 1. **Backup Your Current Project**

Before starting the upgrade process:

* **Backup your full project**
* **Backup your database** using `mysqldump` or your preferred method:

```bash
mysqldump -u your_db_user -p your_db_name > backup.sql
```
* **Stop processes** either wait for all processes to stop or stop the queue worker safely.

**Make sure to keep these backups safe in case something goes wrong during the upgrade.**

### 2. **Download the Release**

* Visit the [Unopim GitHub releases page for v1.0.0](https://github.com/unopim/unopim/archive/refs/tags/v1.0.0.zip)
* Download the `.zip` file for the release `v1.0.0`
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
Here’s the updated version with the **note about queues moved into Step 6** instead of appearing after Step 7.
I kept everything else unchanged.

---

### 6. **Process system queue jobs**

Unopim now uses two queues: **system** and **default**.

The **system** queue is used for processing internal jobs, such as product completeness score calculation.

Update your queue worker so it processes the **system** queue as well by using the `--queue=system,default` option.

If you have set up your queue worker through **Supervisor**, make sure to update the command in your Supervisor configuration file to include this option.

This ensures that the worker handles jobs from **both** queues.
You may also choose to set up **dedicated workers** for each queue.

**Example command:**

```bash
php artisan queue:work --queue=system,default
```

### 7. **Restart the Queue Worker**

If you are using a process manager like Supervisor to manage the `queue:work` command, restart the relevant service to apply the changes. Replace `unopim-worker` with your actual worker name, if different:

```bash
sudo supervisorctl restart unopim-worker
```

## 🛠️ AUTOMATED UPGRADE SCRIPT

1. **Download the script**
* Download the upgrade script and keep a backup of your database and project before starting the upgrade process.

2. **Execute script**
* Place the script in the root directory of your Unopim Project. Open terminal at the Unopim directory and execute:

```bash
 ./upgrade_0.3.x_to_1.0.0.sh
```

3. **Start System queue worker and Restart Queue/Supervisor**
* Unopim now uses two queues: **system** and **default**.
* The **system** queue is used for processing internal jobs, such as product completeness score calculation.
* Update supervisor config if queue worker is being managed through supervisor and restart the **supervisor worker**.

**Updated queue command:**

```bash
php artisan queue:work --queue=system,default
```

## ✅ Upgrade Complete!

After following these steps, your Unopim should be successfully upgraded to version `v1.0.0`.
Test your application thoroughly to make sure everything works as expected.
