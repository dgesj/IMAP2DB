--
-- Table structure for table `load_imap_emails`
--

--DROP TABLE IF EXISTS `load_imap_emails` ;
CREATE TABLE `load_imap_emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniqid` varchar(255) NOT NULL,
  `time` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body_text` text NOT NULL,
  `body_html` text NOT NULL,
  `mailto` varchar(255) NOT NULL,
  `message_date` varchar(255) NOT NULL,
  `message_id` varchar(255) NOT NULL,
  `imap_username` varchar(255) NOT NULL,
  `imap_host` varchar(255) NOT NULL,
  `imap_mailbox` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin2 AUTO_INCREMENT=22 ;

--
-- Table structure for table `load_imap_files`
--

--DROP TABLE IF EXISTS `load_imap_files` ;
CREATE TABLE `load_imap_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin2 AUTO_INCREMENT=120 ;

ALTER TABLE load_imap_emails ADD INDEX emails_email_indx (email);
ALTER TABLE load_imap_emails ADD INDEX emails_mailbox_indx (imap_mailbox);

