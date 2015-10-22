CREATE TABLE `sphinx_update_last_date` (
    `sphinx_server_id` varchar(40) NOT NULL,
    `index` varchar(50) NOT NULL,
    `last_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (`sphinx_server_id`,`index`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;