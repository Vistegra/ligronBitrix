-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Хост: MariaDB-10.3
-- Время создания: Мар 06 2026 г., 13:14
-- Версия сервера: 10.3.39-MariaDB
-- Версия PHP: 8.4.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `ligron_user`
--

-- --------------------------------------------------------

--
-- Структура таблицы `combination_dealer_ligron`
--

CREATE TABLE `combination_dealer_ligron` (
  `id` int(11) NOT NULL,
  `inn_dealer` varchar(20) NOT NULL,
  `user_code` varchar(10) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `combination_dealer_ligron`
--

INSERT INTO `combination_dealer_ligron` (`id`, `inn_dealer`, `user_code`, `active`) VALUES
(1, '000000000001', 'U001', 1),
(2, 'D001', 'U002', 1),
(3, 'D002', 'U001', 1),
(4, 'D002', 'U002', 1),
(5, 'D003', 'U003', 1),
(6, 'D003', 'U004', 1),
(7, 'D004', 'U003', 1),
(8, 'D004', 'U004', 1),
(9, 'D005', 'U005', 1),
(10, 'D005', 'U002', 1),
(11, '500403759605', 'U001', 1),
(12, '7710001467', 'U001', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `combination_dealer_salons`
--

CREATE TABLE `combination_dealer_salons` (
  `id` int(11) NOT NULL,
  `inn_dealer` varchar(20) NOT NULL,
  `salon_code` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `combination_dealer_salons`
--

INSERT INTO `combination_dealer_salons` (`id`, `inn_dealer`, `salon_code`) VALUES
(1, '000000000001', 'S001'),
(2, 'D002', 'S002'),
(3, 'D002', 'S003'),
(4, 'D003', 'S004'),
(5, 'D004', 'S004'),
(6, 'D005', 'S005'),
(7, 'D001', 'S001'),
(8, 'D001', 'S003'),
(9, 'D003', 'S003'),
(10, '7710001467', 'S002'),
(11, '000000000001', '017587980');

-- --------------------------------------------------------

--
-- Структура таблицы `dealers`
--

CREATE TABLE `dealers` (
  `id` int(11) NOT NULL,
  `inn_dealer` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `dealers`
--

INSERT INTO `dealers` (`id`, `inn_dealer`, `name`, `active`) VALUES
(1, 'D001', 'Dealer One', 1),
(2, 'D002', 'Dealer Two', 1),
(3, 'D003', 'Dealer Three', 1),
(4, 'D004', 'Dealer Four', 1),
(5, 'D005', 'Test Dealer2', 1),
(6, '000000000001', 'Test Dealer', 1),
(8, 'D010', 'Dealer Ten', 1),
(9, '500403759605', 'Dealer ИП Иванов', 1),
(10, '7710001467', 'Артис', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `dealer_roles`
--

CREATE TABLE `dealer_roles` (
  `id` int(11) NOT NULL,
  `role_code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `dealer_roles`
--

INSERT INTO `dealer_roles` (`id`, `role_code`, `name`) VALUES
(1, 'admin_dealer', 'Админ дилер'),
(2, 'manager_dealer', 'Менеджер дилер'),
(5, 'user_dealer', 'Менеджер дилер');

-- --------------------------------------------------------

--
-- Структура таблицы `dealer_users`
--

CREATE TABLE `dealer_users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `salon_code` varchar(20) NOT NULL,
  `role_code` varchar(20) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `dealer_users`
--

INSERT INTO `dealer_users` (`id`, `username`, `password`, `salon_code`, `role_code`, `name`, `phone`, `email`, `active`) VALUES
(1, 'dealer_user1', 'hash1', 'S001', 'admin_dealer', 'Dealer User 1', '111-111', 'du1@mail.com', 1),
(2, 'dealer_user2', 'hash2', 'S002', 'user_dealer', 'Dealer User 2', '222-222', 'du2@mail.com', 1),
(3, 'dealer_user3', 'hash3', 'S003', 'admin_dealer', 'Dealer User 3', '333-333', 'du3@mail.com', 1),
(4, 'dealer_user4', 'hash4', 'S004', 'manager_dealer', 'Dealer User 4', '444-444', 'du4@mail.com', 1),
(5, 'dealer_user5', 'hash5', 'S005', 'admin_dealer', 'Dealer User 5', '555-555', 'du5@mail.com', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `filling`
--

CREATE TABLE `filling` (
  `id` int(11) NOT NULL,
  `user_code` varchar(10) NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `user_code_filling` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `ligron_roles`
--

CREATE TABLE `ligron_roles` (
  `id` int(11) NOT NULL,
  `role_code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `ligron_roles`
--

INSERT INTO `ligron_roles` (`id`, `role_code`, `name`) VALUES
(1, 'ADMIN', 'Administrator'),
(2, 'USER', 'User');

-- --------------------------------------------------------

--
-- Структура таблицы `ligron_users`
--

CREATE TABLE `ligron_users` (
  `id` int(11) NOT NULL,
  `user_code` varchar(10) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `role_code` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(50) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `ligron_users`
--

INSERT INTO `ligron_users` (`id`, `user_code`, `name`, `role_code`, `username`, `password`, `email`, `phone`, `active`) VALUES
(1, 'U001', 'Ligron Admin 1', 'ADMIN', 'ladmin1', 'pass1', 'la1@mail.com', '900-001', 1),
(2, 'U002', 'Ligron User 1', 'USER', 'luser1', 'pass2', 'lu1@mail.com', '900-002', 1),
(3, 'U003', 'Ligron Admin 2', 'ADMIN', 'ladmin2', 'pass3', 'la2@mail.com', '900-003', 1),
(4, 'U004', 'Ligron User 2', 'USER', 'luser2', 'pass4', 'lu2@mail.com', '900-004', 1),
(5, 'U005', 'Ligron Admin 3', 'ADMIN', 'ladmin3', 'pass5', 'la3@mail.com', '900-005', 1),
(7, 'U006', 'Ligron Admin 6', 'ADMIN', 'ladmin6', 'pass6', 'la3@mail.com', '900-005', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `salons`
--

CREATE TABLE `salons` (
  `id` int(11) NOT NULL,
  `salon_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `salons`
--

INSERT INTO `salons` (`id`, `salon_code`, `name`, `active`) VALUES
(1, 'S001', 'Salon One', 1),
(2, 'S002', 'Salon Two', 1),
(3, 'S003', 'Salon Three', 1),
(4, 'S004', 'Salon Four', 1),
(5, 'S005', 'Salon Five', 1),
(6, 'S006', 'Salon One', 1),
(7, '017587980', 'Salon Test', 1);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `combination_dealer_ligron`
--
ALTER TABLE `combination_dealer_ligron`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IX_cmd_dealer` (`inn_dealer`),
  ADD KEY `IX_cmd_user` (`user_code`);

--
-- Индексы таблицы `combination_dealer_salons`
--
ALTER TABLE `combination_dealer_salons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cds_dealer` (`inn_dealer`),
  ADD KEY `IX_combination_dealer_salons_salon` (`salon_code`);

--
-- Индексы таблицы `dealers`
--
ALTER TABLE `dealers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `inn_dealer` (`inn_dealer`);

--
-- Индексы таблицы `dealer_roles`
--
ALTER TABLE `dealer_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_code` (`role_code`);

--
-- Индексы таблицы `dealer_users`
--
ALTER TABLE `dealer_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`username`),
  ADD KEY `fk_du_role` (`role_code`),
  ADD KEY `IX_dealer_users_salon` (`salon_code`);

--
-- Индексы таблицы `filling`
--
ALTER TABLE `filling`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IX_filling_user` (`user_code`),
  ADD KEY `IX_filling_user_filling` (`user_code_filling`);

--
-- Индексы таблицы `ligron_roles`
--
ALTER TABLE `ligron_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_code` (`role_code`);

--
-- Индексы таблицы `ligron_users`
--
ALTER TABLE `ligron_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_code` (`user_code`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `IX_ligron_users_role` (`role_code`);

--
-- Индексы таблицы `salons`
--
ALTER TABLE `salons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `salon_code` (`salon_code`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `combination_dealer_ligron`
--
ALTER TABLE `combination_dealer_ligron`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT для таблицы `combination_dealer_salons`
--
ALTER TABLE `combination_dealer_salons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT для таблицы `dealers`
--
ALTER TABLE `dealers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `dealer_roles`
--
ALTER TABLE `dealer_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `dealer_users`
--
ALTER TABLE `dealer_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `filling`
--
ALTER TABLE `filling`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `ligron_roles`
--
ALTER TABLE `ligron_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `ligron_users`
--
ALTER TABLE `ligron_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `salons`
--
ALTER TABLE `salons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `combination_dealer_ligron`
--
ALTER TABLE `combination_dealer_ligron`
  ADD CONSTRAINT `fk_cmd_dealer` FOREIGN KEY (`inn_dealer`) REFERENCES `dealers` (`inn_dealer`),
  ADD CONSTRAINT `fk_cmd_user` FOREIGN KEY (`user_code`) REFERENCES `ligron_users` (`user_code`);

--
-- Ограничения внешнего ключа таблицы `combination_dealer_salons`
--
ALTER TABLE `combination_dealer_salons`
  ADD CONSTRAINT `fk_cds_dealer` FOREIGN KEY (`inn_dealer`) REFERENCES `dealers` (`inn_dealer`),
  ADD CONSTRAINT `fk_cds_salon` FOREIGN KEY (`salon_code`) REFERENCES `salons` (`salon_code`);

--
-- Ограничения внешнего ключа таблицы `dealer_users`
--
ALTER TABLE `dealer_users`
  ADD CONSTRAINT `fk_du_role` FOREIGN KEY (`role_code`) REFERENCES `dealer_roles` (`role_code`),
  ADD CONSTRAINT `fk_du_salon` FOREIGN KEY (`salon_code`) REFERENCES `salons` (`salon_code`);

--
-- Ограничения внешнего ключа таблицы `filling`
--
ALTER TABLE `filling`
  ADD CONSTRAINT `fk_fill_user` FOREIGN KEY (`user_code`) REFERENCES `ligron_users` (`user_code`),
  ADD CONSTRAINT `fk_fill_user_fill` FOREIGN KEY (`user_code_filling`) REFERENCES `ligron_users` (`user_code`);

--
-- Ограничения внешнего ключа таблицы `ligron_users`
--
ALTER TABLE `ligron_users`
  ADD CONSTRAINT `fk_lu_role` FOREIGN KEY (`role_code`) REFERENCES `ligron_roles` (`role_code`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
