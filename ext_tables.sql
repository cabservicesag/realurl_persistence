#
# Table structure for table 'tx_realurlpersistence_url'
#
CREATE TABLE tx_realurlpersistence_url (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	hash varchar(255) DEFAULT '' NOT NULL,
	url text NOT NULL,
	parameters text NOT NULL,

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY tstamp (tstamp),
	UNIQUE hash (hash)

);
