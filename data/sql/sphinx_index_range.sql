CREATE TABLE `sphinx_index_range` (
    `sphinx_server_id` varchar(40) NOT NULL,
    `index` varchar(50) NOT NULL,
    `start` int(11) NOT NULL,
    `end` int(11) NOT NULL,
    PRIMARY KEY (`sphinx_server_id`,`index`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;