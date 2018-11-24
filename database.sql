--
-- Database: `salt_minion`
--
CREATE DATABASE IF NOT EXISTS `salt_minion` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `salt_minion`;
--
-- Table structure for table `minion`
--
CREATE TABLE `minion` (
  `server_id` int(11) unsigned NOT NULL,
  `last_audit` datetime DEFAULT NULL,
  `last_seen` datetime DEFAULT NULL,
  `id` varchar(255) NOT NULL,
  `biosreleasedate` varchar(255) DEFAULT NULL,
  `biosversion` varchar(255) DEFAULT NULL,
  `cpu_model` varchar(255) DEFAULT NULL,
  `fqdn` text,
  `host` varchar(255) DEFAULT NULL,
  `kernel` varchar(255) DEFAULT NULL,
  `kernelrelease` varchar(255) DEFAULT NULL,
  `mem_total` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `num_cpus` smallint(5) unsigned NOT NULL DEFAULT '0',
  `num_gpus` int(10) unsigned NOT NULL DEFAULT '0',
  `os` varchar(255) DEFAULT NULL,
  `osrelease` varchar(255) DEFAULT NULL,
  `saltversion` varchar(255) DEFAULT NULL,
  `package_total` smallint(5) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
--
-- Table structure for table `minion_package`
--
CREATE TABLE `minion_package` (
  `server_id` int(11) unsigned NOT NULL,
  `package_id` mediumint(8) unsigned NOT NULL,
  `package_version` varchar(255) NOT NULL,
  `present` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
--
-- Table structure for table `package`
--
CREATE TABLE `package` (
  `package_id` mediumint(8) unsigned NOT NULL,
  `package_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
--
-- Indexes for table `minion`
--
ALTER TABLE `minion`
  ADD PRIMARY KEY (`server_id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `host` (`host`);
--
-- Indexes for table `minion_package`
--
ALTER TABLE `minion_package`
  ADD PRIMARY KEY (`server_id`,`package_id`,`package_version`) USING BTREE,
  ADD KEY `serverId` (`server_id`);
--
-- Indexes for table `package`
--
ALTER TABLE `package`
  ADD PRIMARY KEY (`package_id`),
  ADD UNIQUE KEY `package_name` (`package_name`);
--
-- AUTO_INCREMENT for table `package`
--
ALTER TABLE `package`
  MODIFY `package_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT;
