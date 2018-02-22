SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for sessions
-- ----------------------------
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `SID` varchar(32) NOT NULL,
  `UID` int(32) DEFAULT '0',
  `data` text,
  `access` int(11) DEFAULT NULL,
  `status` enum('1','2') DEFAULT '1',
  `remote_address` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`SID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
