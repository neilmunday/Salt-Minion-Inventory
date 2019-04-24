--
-- Database: `salt_minion`
--

-- --------------------------------------------------------

--
-- Table structure for table `gpu`
--

DROP TABLE IF EXISTS `gpu`;
CREATE TABLE `gpu` (
  `gpu_id` smallint(5) unsigned NOT NULL,
  `gpu_model` varchar(255) NOT NULL,
  `vendor_id` smallint(5) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `interface`
--

DROP TABLE IF EXISTS `interface`;
CREATE TABLE `interface` (
  `interface_id` smallint(5) unsigned NOT NULL,
  `interface_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `minion`
--

DROP TABLE IF EXISTS `minion`;
CREATE TABLE `minion` (
  `server_id` int(11) unsigned NOT NULL,
  `last_audit` int(11) unsigned DEFAULT NULL,
  `last_seen` int(11) unsigned DEFAULT NULL,
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
  `package_total` smallint(5) unsigned NOT NULL DEFAULT '0',
  `selinux_enabled` tinyint(1) NOT NULL,
  `selinux_enforced` enum('Permissive','Enforcing','Disabled','') NOT NULL DEFAULT 'Disabled'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `minion_disk`
--

DROP TABLE IF EXISTS `minion_disk`;
CREATE TABLE `minion_disk` (
  `server_id` int(11) unsigned NOT NULL,
  `disk_path` varchar(255) NOT NULL,
  `disk_serial` text NOT NULL,
  `disk_size` float unsigned NOT NULL,
  `vendor_id` smallint(5) unsigned NOT NULL,
  `present` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `minion_gpu`
--

DROP TABLE IF EXISTS `minion_gpu`;
CREATE TABLE `minion_gpu` (
  `server_id` int(11) unsigned NOT NULL,
  `gpu_id` smallint(5) unsigned NOT NULL,
  `gpu_qty` smallint(5) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `minion_interface`
--

DROP TABLE IF EXISTS `minion_interface`;
CREATE TABLE `minion_interface` (
  `server_id` int(11) NOT NULL,
  `interface_id` smallint(6) NOT NULL,
  `mac` varchar(17) NOT NULL,
  `present` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `minion_ip4`
--

DROP TABLE IF EXISTS `minion_ip4`;
CREATE TABLE `minion_ip4` (
  `server_id` int(10) unsigned NOT NULL,
  `interface_id` smallint(5) unsigned NOT NULL,
  `ip4` varchar(15) NOT NULL,
  `present` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `minion_package`
--

DROP TABLE IF EXISTS `minion_package`;
CREATE TABLE `minion_package` (
  `server_id` int(11) unsigned NOT NULL,
  `package_id` mediumint(8) unsigned NOT NULL,
  `package_version` varchar(255) NOT NULL,
  `present` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `minion_user`
--

DROP TABLE IF EXISTS `minion_user`;
CREATE TABLE `minion_user` (
  `server_id` int(11) unsigned NOT NULL,
  `user_id` smallint(5) unsigned NOT NULL,
  `present` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `package`
--

DROP TABLE IF EXISTS `package`;
CREATE TABLE `package` (
  `package_id` mediumint(8) unsigned NOT NULL,
  `package_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `user_id` smallint(5) unsigned NOT NULL,
  `user_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `vendor`
--

DROP TABLE IF EXISTS `vendor`;
CREATE TABLE `vendor` (
  `vendor_id` smallint(5) unsigned NOT NULL,
  `vendor_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='vendor';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `gpu`
--
ALTER TABLE `gpu`
  ADD PRIMARY KEY (`gpu_id`);

--
-- Indexes for table `interface`
--
ALTER TABLE `interface`
  ADD PRIMARY KEY (`interface_id`);

--
-- Indexes for table `minion`
--
ALTER TABLE `minion`
  ADD PRIMARY KEY (`server_id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `host` (`host`);

--
-- Indexes for table `minion_disk`
--
ALTER TABLE `minion_disk`
  ADD PRIMARY KEY (`server_id`,`disk_path`);

--
-- Indexes for table `minion_gpu`
--
ALTER TABLE `minion_gpu`
  ADD PRIMARY KEY (`server_id`,`gpu_id`);

--
-- Indexes for table `minion_interface`
--
ALTER TABLE `minion_interface`
  ADD PRIMARY KEY (`server_id`,`interface_id`);

--
-- Indexes for table `minion_ip4`
--
ALTER TABLE `minion_ip4`
  ADD PRIMARY KEY (`server_id`,`interface_id`,`ip4`);

--
-- Indexes for table `minion_package`
--
ALTER TABLE `minion_package`
  ADD PRIMARY KEY (`server_id`,`package_id`,`package_version`) USING BTREE,
  ADD KEY `serverId` (`server_id`);

--
-- Indexes for table `minion_user`
--
ALTER TABLE `minion_user`
  ADD PRIMARY KEY (`server_id`,`user_id`);

--
-- Indexes for table `package`
--
ALTER TABLE `package`
  ADD PRIMARY KEY (`package_id`),
  ADD UNIQUE KEY `package_name` (`package_name`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `vendor`
--
ALTER TABLE `vendor`
  ADD PRIMARY KEY (`vendor_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `gpu`
--
ALTER TABLE `gpu`
  MODIFY `gpu_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `interface`
--
ALTER TABLE `interface`
  MODIFY `interface_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `package`
--
ALTER TABLE `package`
  MODIFY `package_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `vendor`
--
ALTER TABLE `vendor`
  MODIFY `vendor_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT;
