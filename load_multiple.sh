#!/bin/bash
# This script will allow you to load data from several
# IMAP accounts, each specified by a separate config file
# e.g. config.account1.php, config.account2.php, etc

for $config in `ls config.*.php`; do
	cp $config config.php
	php application.php
done
mysql -u root -pvorac1ous << EOF
use omnivore
update load_imap_emails set email=REPLACE(email,'<','') where email like '<%';
update load_imap_emails set email=REPLACE(email,'>','') where email like '%>';
update load_imap_emails set email=REPLACE(email,'@mms.att.net','') where email like '%@mms.att.net';

EOF

