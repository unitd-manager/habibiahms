#!/bin/bash

# Backup directory
BACKUP_DIR="/home/habibiahms/public_html/util"

# Timestamp for backup file
DATE=$(date "+%F_%H-%M-%S")
BACKUP_FILE="$BACKUP_DIR/all_db_backup_$DATE.sql.gz"

# MySQL dump with gzip
/usr/bin/mysqldump -u root -h localhost --all-databases --ssl-mode=DISABLED \
--no-tablespaces --routines --events --triggers --single-transaction --quick \
--default-character-set=utf8mb4 2>> "$BACKUP_DIR/backup_errors.log" \
| /bin/gzip > "$BACKUP_FILE"

# Cleanup backups older than 7 days
find "$BACKUP_DIR" -type f -name "all_db_backup_*.sql.gz" -mtime +2 -exec rm -f {} \;
