

-- Database: `finance_tracker`


CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('income','expense') NOT NULL DEFAULT 'expense',
  `user_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `type`, `user_id`) VALUES
(1, 'Salary and Wages', 'income', 0),
(2, 'Business Income', 'income', 0),
(3, 'Investments', 'income', 0),
(4, 'Rental', 'income', 0),
(5, 'Government Payments', 'income', 0),
(6, 'Gifts & Entertainment', 'income', 0),
(8, 'Utility Bills', 'expense', 0),
(9, 'Groceries', 'expense', 0),
(10, 'Other Income or Other Expenses', 'expense', 0),
(135, 'wwwe', 'income', 12),
(145, 'sssdsd', 'income', 12),
(150, 'sdds', 'income', 7),
(154, 'sasawawaw', 'income', 7),
(156, 'Hwhej', 'expense', 7),
(157, 'Apple', 'income', 13);

-- --------------------------------------------------------

--
-- Table structure for table `financial_goals`
--

CREATE TABLE `financial_goals` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `target_amount` decimal(10,2) NOT NULL,
  `current_amount` decimal(10,2) NOT NULL,
  `deadline` date NOT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `financial_goals`
--

INSERT INTO `financial_goals` (`id`, `title`, `target_amount`, `current_amount`, `deadline`, `is_archived`, `created_at`, `user_id`, `status`) VALUES
(2, 'ballek', 545.00, 454.00, '2024-12-02', 1, '2024-12-02 05:53:53', 12, 'active'),
(4, 'pakaya', 2000.00, 10020.00, '2024-12-05', 0, '2024-12-03 07:53:04', 12, 'failed');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expiry` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used` tinyint(1) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


--
-- Table structure for table `pending_verifications`
--

CREATE TABLE `pending_verifications` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `verification_token` varchar(64) NOT NULL,
  `created_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_name` (`user_id`,`name`);

--
-- Indexes for table `financial_goals`
--
ALTER TABLE `financial_goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_goals` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pending_verifications`
--
ALTER TABLE `pending_verifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_date` (`user_id`,`date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=158;

--
-- AUTO_INCREMENT for table `financial_goals`
--
ALTER TABLE `financial_goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `pending_verifications`
--
ALTER TABLE `pending_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `financial_goals`
--
ALTER TABLE `financial_goals`
  ADD CONSTRAINT `fk_user_goals` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

