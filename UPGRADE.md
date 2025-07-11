# 🔼 UPGRADE GUIDE

> This guide helps you safely upgrade your Unopim installation. Follow the **manual upgrade steps** or use the **automated upgrade script** for minor patches.

([See change impact classification details](CHANGE_IMPACT_CLASSIFICATION.md))

## High impact changes

## Medium impact changes

## Low impact changes


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
