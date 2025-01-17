# Usage

1. Rename `config_sample.php` to `config.php`.
2. Fill in MySQL and Salesforce (SF) credentials.
3. Execute `init.sql` in the appropriate database.

# Notes

## realcon.php Usage

- `realcon.php?action=___`

### Possible values for `$action`

- `default`, `view`
- `retrieve`
  - Retrieve data from SF.
- `create`
  - Retrieve data from SF and then insert into the database.
  - Specify reason: `manual`, `crontab`, `cdc`.

## Database Tables

### `JB_template`

```sql
CREATE TABLE `JB_template` (
  `id` VARCHAR(32) NOT NULL,
  `Name` VARCHAR(255) NOT NULL,
  `CompanyName__c` VARCHAR(255) NOT NULL,
  `Job_Classify__c` VARCHAR(255) NOT NULL,
  `CS_Checked__c` TINYINT(1) NOT NULL,
  `Client_ID__c` VARCHAR(255) NOT NULL
) ENGINE = InnoDB;
```

### `JI_template`

```sql
CREATE TABLE `JI_template` (
  `id` VARCHAR(32) NOT NULL,
  `Name` VARCHAR(255) NOT NULL,
  `Bulk_Date__c` VARCHAR(255) NOT NULL,
  `Job___c` VARCHAR(255) NOT NULL
) ENGINE = InnoDB;
```

### Truncate `batch`

```sql
TRUNCATE `batch`;
```

## Changes Implemented (20240116)

### realcon.php Updates

- Added a new action case "historyCount" to retrieve `SELECT COUNT(id) FROM batch`.
- Modified the "history" action case to accept variables for LIMIT and retrieve only the `id`, `batch`, `create_time`, and `extra` columns. The `from` and `to` variables in the query string default to 0 and 50 respectively.

### viewlist.html Updates

- Added "Previous" and "Next" buttons for pagination.
- Updated the page number display to allow user input.
- Added a select option to choose the number of entries to be displayed per page (10, 20, 50, 100).
- Improved the UX by adding horizontal padding, using left and right arrows for previous and next buttons, and vertically aligning the pagination elements.

### sqlexport.php Updates

- Created a new file `sqlexport.php` to handle exporting data from the `batch` table.
- Added functionality to export data rows in `INSERT INTO` SQL statements.
- Added functionality to validate rows within a specified range and display results in a textarea.
- Combined the export and validate forms into one form.
- Added an `automate` action to automatically export batches in specified sizes and display results in a textarea.

### cometd/cometd.js Updates

- Added a cleanup function to keep the latest 500 rows in the `batch` table and remove the older ones.
- Scheduled the cleanup function to run every night at 3:00 AM using the existing cron settings (`cron_hour` and `cron_minute`).
- Executed the cleanup logic after the periodic retrieval script is successful.

## CDC Mimic (20250115)

- Add action to retrieve data per interval.
- Edit `.env` `COMETD_CDCMIMIC_INTERVAL` to use. `0=disabled`, value `>0` will be interval in milliseconds.

## PM2 Related (20240715)

### Init Your Script with PM2
```sh
pm2 start /var/www/html/cometd/cometd.js --name cometd-script --cwd /var/www/html/cometd
```

### Resume Script when stopped
```sh
pm2 start cometd-script
```

### Set Up a Cron Schedule to Restart the Script at 03:30 Every Night
```sh
pm2 restart cometd-script --cron "30 3 * * *"
```

### Verify the Cron Schedule
```sh
pm2 list
```

### Remove the Cron Job for a Specific Process
```sh
pm2 uncron cometd-script
```

### Stop a Process
```sh
pm2 stop cometd-script
```

### Delete a Process
```sh
pm2 delete cometd-script
```

### Save PM2 current status
```sh
pm2 save --force
```

## CDC Related (20240212)
1. `cd cometd`
2. `npm install dotenv axios jsforce`
3. `node cometd`

### Reference

[Salesforce Streaming API in Node.js](https://gaogang.wordpress.com/2020/03/31/subscribe-to-salesforce-streaming-api-in-nodejs/)
