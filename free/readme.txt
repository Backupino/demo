=== Backupino ===
Contributors: backupino
Tags: backup, restore, migration, clone, s3
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Backup, clone and migrate a WordPress site - files and database - into one resumable package with a standalone installer.

== Description ==

Backupino packs your whole site into a single archive plus a self-contained `installer.php` that rebuilds
the site on an empty server, without WordPress being installed first.

**Built for real shared hosting**

* Builds run in small, resumable slices - no `max_execution_time` surprises.
* The database is dumped with pure SQL (keyset pagination), so `mysqldump`/`exec` are not required.
* URL rewriting understands PHP serialized data, nested serialization and JSON payloads.
* Packages are stored on this server, protected by .htaccess, and served through a permanent download link.

**Free vs Pro**

The free plugin creates manual backups, migrations and clones - including the installer and the URL
rewrite. Restoring a backup you already own is never locked behind a payment.

The Pro add-on (installed separately, not part of this plugin) adds cloud destinations, custom
schedules, restore in place with rollback, multisite support and incremental packages.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate it through the *Plugins* screen.
3. Open **Backupino** in the admin menu and press *Create backup*.

== Frequently Asked Questions ==

= Does the destination server need WordPress? =

No. Upload the archive and `installer.php` to an empty directory and open the installer in a browser.

= Where are packages stored? =

In `wp-content/uploads/backupino/packages` by default, protected by `.htaccess` and `index.php`.

= Can I run backups from the command line? =

Yes: `wp backupino create`, `wp backupino jobs`, `wp backupino status`.

== Changelog ==

= 0.1.0 =
* Initial development release: resumable build pipeline, standalone installer, CLI and REST API.
