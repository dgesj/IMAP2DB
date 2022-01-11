#!/bin/bash
# This script will allow you to load data from several
# IMAP accounts, each specified by a separate config file
# e.g. config.account1.php, config.account2.php, etc

for $config in `ls config.*.php`; do
	cp $config config.php
	php application.php
done

