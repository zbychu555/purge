--
-- Schema definition for purge queue table
--
CREATE TABLE tx_purge_cachequeue (

	uid int(11) NOT NULL auto_increment,
	path text NOT NULL,

	PRIMARY KEY (uid)

);
