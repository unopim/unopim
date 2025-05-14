# 🔼 UPGRADE GUIDE: Unopim `v0.1.x` → `v0.2.0`

> This guide helps you safely upgrade your Unopim installation from any `v0.1.x` version to `v0.2.0`. You can follow the **manual** steps.

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

Make sure to keep these backups safe in case something goes wrong during the upgrade.

### 2. **Download the Release**

* Visit the [Unopim GitHub releases page for v0.2.0](https://github.com/unopim/unopim/archive/refs/tags/v0.2.0.zip)
* Download the `.zip` file for the latest version `v0.2.0`
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

## ✅ Upgrade Complete!

After following these steps, your Unopim should be successfully upgraded to version `v0.2.0`. Test your application thoroughly to make sure everything works as expected.
