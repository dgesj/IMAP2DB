--
-- Table structure for table `emails`
--

--DROP TABLE IF EXISTS `emails` ;
CREATE TABLE `emails` (
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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=22 ;

--
-- Table structure for table `files`
--

--DROP TABLE IF EXISTS `files` ;
CREATE TABLE `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=120 ;
