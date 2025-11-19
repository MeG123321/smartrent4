-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Lis 19, 2025 at 09:05 PM
-- Wersja serwera: 10.4.32-MariaDB
-- Wersja PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smartrent`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `actor_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `meta` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `actor_id`, `action`, `meta`, `created_at`) VALUES
(1, 3, 'Wylogowanie', 'Użytkownik wylogował się', '2025-11-19 20:13:13'),
(2, 8, 'Wylogowanie', 'Użytkownik wylogował się', '2025-11-19 20:20:34'),
(3, 9, 'Wylogowanie', 'Użytkownik wylogował się', '2025-11-19 20:21:04'),
(4, 10, 'Wylogowanie', 'Użytkownik wylogował się', '2025-11-19 20:24:34'),
(5, 5, 'Logowanie', 'Zalogowano użytkownika: anna@example.com', '2025-11-19 20:24:52'),
(6, 5, 'Wysłano wiadomość do właściciela', 'property_id:39, to_user:6', '2025-11-19 20:29:08'),
(7, 5, 'Wylogowanie', 'Użytkownik wylogował się', '2025-11-19 20:29:33'),
(8, 6, 'Logowanie', 'Zalogowano użytkownika: jan@example.com', '2025-11-19 20:29:47'),
(9, 6, 'Przypisano mieszkanie (ajax/submit)', 'assignment_id:1, property_id:39, tenant_id:5', '2025-11-19 20:29:54'),
(10, 6, 'Wylogowanie', 'Użytkownik wylogował się', '2025-11-19 20:31:01'),
(11, 6, 'Logowanie', 'Zalogowano użytkownika: jan@example.com', '2025-11-19 20:36:16');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `status` enum('pending','confirmed','ended') NOT NULL DEFAULT 'confirmed',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `property_id`, `tenant_id`, `assigned_by`, `status`, `start_date`, `end_date`, `notes`, `created_at`) VALUES
(1, 39, 5, 6, 'confirmed', NULL, NULL, NULL, '2025-11-19 20:29:54');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `maintenance_reports`
--

CREATE TABLE `maintenance_reports` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) DEFAULT NULL,
  `property_id` int(11) DEFAULT NULL,
  `reported_by` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `from_user_id` int(11) NOT NULL,
  `to_user_id` int(11) NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `body` text NOT NULL,
  `sent_at` datetime DEFAULT current_timestamp(),
  `read_flag` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `from_user_id`, `to_user_id`, `property_id`, `body`, `sent_at`, `read_flag`) VALUES
(1, 5, 6, 39, 'sd', '2025-11-19 20:29:08', 0);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('due','paid','overdue') NOT NULL DEFAULT 'due',
  `paid_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `assignment_id`, `due_date`, `amount`, `status`, `paid_at`, `notes`, `created_at`) VALUES
(1, 1, '2025-12-19', 1400.00, 'due', NULL, NULL, '2025-11-19 20:29:54');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `city` varchar(120) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `is_rented` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`id`, `title`, `description`, `price`, `city`, `image`, `owner_id`, `is_rented`, `created_at`) VALUES
(31, 'Elegancki Apartament w Centrum Warszawy', 'Nowoczesny apartament w sercu Warszawy z widokiem na Wisłę. Pełne wyposażenie, dostęp do siłowni i basenu.', 1500.00, 'Warszawa', 'zdjecie1.png', 5, 0, '2025-11-19 20:12:14'),
(32, 'Nowoczesny Loft w Krakowie', 'Designerski loft z otwartą przestrzenią. Idealne dla pracowników biurowych. Blisko dworca, restauracji i kawiarni.', 1200.00, 'Kraków', 'zdjecie2.png', 5, 0, '2025-11-19 20:12:14'),
(33, 'Przytulny Pokój w Gdańsku', '', 800.00, 'Gdańsk', 'zdjecie3.png', 6, 0, '2025-11-19 20:12:14'),
(34, 'Luksusowa Willa w Wrocławiu', 'Piękna willa z ogrodem, basen, sauna. Doskonałe miejsce na imprezy lub wyskok firmowy. 5 sypialni, 3 łazienki.', 2000.00, 'Wrocław', 'zdjecie4.png', 6, 0, '2025-11-19 20:12:14'),
(35, 'Studio w Poznaniu', 'Małe ale wygodne studio. Nowoczesna kuchnia, klimatyzacja, WiFi. Dla singli lub pary.', 950.00, 'Poznań', 'zdjecie5.png', 7, 0, '2025-11-19 20:12:14'),
(36, 'Rodzinny Dom w Łodzi', 'Duży dom z ogrodem dla rodziny. 4 sypialnie, nowoczesne urządzenia, parking.', 1800.00, 'Łódź', 'zdjecie6.png', 7, 0, '2025-11-19 20:12:14'),
(37, 'Biznesowy Apartament w Warszawie', 'Apartament dla profesjonalistów. Business center, gym, 24h concierge. Wszystko dla pracownika nomadycznego.', 1600.00, 'Warszawa', 'zdjecie7.png', 7, 0, '2025-11-19 20:12:14'),
(38, 'Przystanowisko Artysty w Krakowie', 'Artystyczne studio z dużymi oknami. Idealne do pracy twórczej. Blisko galerii i atelieru.', 1100.00, 'Kraków', 'zdjecie8.png', 6, 0, '2025-11-19 20:12:14'),
(39, 'Plaża Apartament w Gdyni', 'Apartament z widokiem na morze. Blisko plaży, przystani i portu. Idealne wakacje bez podróży.', 1400.00, 'Gdynia', 'zdjecie9.png', 6, 0, '2025-11-19 20:12:14'),
(40, 'Horyzont Apartament we Wrocławiu', 'Dach Wrocławia - apartament na ostatnim piętrze z tarasem. Zachód słońca z panoramą miasta.', 1750.00, 'Wrocław', 'zdjecie10.png', 5, 0, '2025-11-19 20:12:14');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `rentals`
--

CREATE TABLE `rentals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','in_progress','closed') DEFAULT 'open',
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(3, 'Mr_G', 'MATEUSZ.GWADERA109@GMAIL.COM', '$2y$10$kUVciV769F3UA2ypNECQQe7ZdhSKbHN4bUaMvBmte2VRJZe3ifxoO', 'admin', '2025-11-09 14:06:21'),
(5, 'Anna Kowalska', 'anna@example.com', '$2y$10$kkzousheGx4V06XsKvJQCOdLEp.Whlx9CjnAN.3jDZu/ZAj2nHK/W', 'user', '2025-11-19 20:20:30'),
(6, 'Jan Nowak', 'jan@example.com', '$2y$10$2KUwYh/djQn/QiieI62s4.DR1l6hySJpKC1ftd5IsOkPEzE2Jaeci', 'user', '2025-11-19 20:21:01'),
(7, 'Maria Lewandowska', 'maria@example.com', '$2y$10$3acRfaXl1XvE.AxIpBx4FOGW5k8XJb87WnOi1mo8FB07fx.K31LQS', 'user', '2025-11-19 20:21:27');

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `actor_id` (`actor_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indeksy dla tabeli `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- Indeksy dla tabeli `maintenance_reports`
--
ALTER TABLE `maintenance_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indeksy dla tabeli `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from_user_id` (`from_user_id`),
  ADD KEY `to_user_id` (`to_user_id`);

--
-- Indeksy dla tabeli `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`);

--
-- Indeksy dla tabeli `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indeksy dla tabeli `rentals`
--
ALTER TABLE `rentals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indeksy dla tabeli `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indeksy dla tabeli `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeksy dla tabeli `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `maintenance_reports`
--
ALTER TABLE `maintenance_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `rentals`
--
ALTER TABLE `rentals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_fk_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_fk_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance_reports`
--
ALTER TABLE `maintenance_reports`
  ADD CONSTRAINT `maintenance_fk_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `maintenance_fk_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_fk_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `properties`
--
ALTER TABLE `properties`
  ADD CONSTRAINT `properties_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rentals`
--
ALTER TABLE `rentals`
  ADD CONSTRAINT `rentals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rentals_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
