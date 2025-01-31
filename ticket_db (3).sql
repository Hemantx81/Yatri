-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 31, 2025 at 12:28 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ticket_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@gmail.com', '$2y$10$4cJiJzcg376QE7H1X906UOnj/2iyMlEFUlw8yJ.ZYK/sEVjsSxs0K', '2024-12-09 10:00:55', '2024-12-09 10:00:55');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `route_id` int(11) DEFAULT NULL,
  `seats_booked` varchar(255) DEFAULT NULL,
  `booking_time` datetime DEFAULT NULL,
  `payment_status` enum('Pending','Completed','Failed') DEFAULT 'Pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `route_id`, `seats_booked`, `booking_time`, `payment_status`, `created_at`, `total_amount`) VALUES
(1, 5, 1, ',30', '2025-01-26 10:58:51', 'Completed', '2025-01-26 10:58:51', 3000.00),
(2, 6, 4, ',16', '2025-01-26 11:00:34', 'Completed', '2025-01-26 11:00:34', 2200.00),
(3, 8, 3, ',4,6,8', '2025-01-26 11:02:59', 'Completed', '2025-01-26 11:02:59', 4000.00);

-- --------------------------------------------------------

--
-- Table structure for table `buses`
--

CREATE TABLE `buses` (
  `id` int(11) NOT NULL,
  `bus_name` varchar(100) NOT NULL,
  `image_path` varchar(255) DEFAULT 'assets/images/default_bus.jpg',
  `total_seats` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_ac` tinyint(1) DEFAULT 0,
  `is_wifi` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buses`
--

INSERT INTO `buses` (`id`, `bus_name`, `image_path`, `total_seats`, `created_at`, `is_ac`, `is_wifi`) VALUES
(1, 'Sunrise', 'assets/images/bus_6795c1e92b0e79.57153957.jpg', 30, '2025-01-26 05:02:33', 1, 1),
(2, 'Karnali Flowing Superfast', 'assets/images/bus_6795c20d4b8568.74401818.jpg', 32, '2025-01-26 05:03:09', 1, 1),
(3, 'Gorkha Express', 'assets/images/bus_6795c23feef3e6.60869961.jpg', 32, '2025-01-26 05:03:59', 1, 1),
(4, 'Nepalgunj Star Express', 'assets/images/bus_6795c2c1741ff2.59291963.jpg', 28, '2025-01-26 05:06:09', 1, 0),
(5, 'Ghodaghodi Express', 'assets/images/bus_6795c2feed7a12.24250328.jpg', 30, '2025-01-26 05:07:10', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `bus_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` decimal(2,1) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `payment_method` enum('eSewa','Khalti') NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('Pending','Completed','Failed') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `payment_method`, `transaction_id`, `payment_amount`, `payment_status`, `created_at`) VALUES
(1, 1, 'Khalti', 'KH1737868441', 3000.00, 'Completed', '2025-01-26 05:14:01'),
(2, 2, 'eSewa', 'ES1737868537', 2200.00, 'Completed', '2025-01-26 05:15:37'),
(3, 3, 'eSewa', 'ES1737868682', 4000.00, 'Completed', '2025-01-26 05:18:02');

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

CREATE TABLE `routes` (
  `id` int(11) NOT NULL,
  `bus_id` int(11) NOT NULL,
  `source` varchar(100) NOT NULL,
  `destination` varchar(100) NOT NULL,
  `departure_time` datetime NOT NULL,
  `arrival_time` datetime NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` enum('active','disabled') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `routes`
--

INSERT INTO `routes` (`id`, `bus_id`, `source`, `destination`, `departure_time`, `arrival_time`, `price`, `status`) VALUES
(1, 1, 'Kathmandu', 'Pokhara', '2025-01-28 10:53:00', '2025-01-29 10:53:00', 15000.00, 'disabled'),
(2, 1, 'pokhara', 'Kathmandu', '2025-01-31 10:53:00', '2025-02-01 10:53:00', 1300.00, 'active'),
(3, 2, 'Nepalgunj', 'Butwal', '2025-01-29 10:54:00', '2025-01-29 20:00:00', 1000.00, 'active'),
(4, 2, 'Nepalgunj', 'Kanchanpur', '2025-02-01 10:55:00', '2025-02-01 16:55:00', 1100.00, 'active'),
(5, 5, 'kohapur', 'Dhangadi', '2025-01-30 10:56:00', '2025-01-30 18:56:00', 900.00, 'active'),
(6, 4, 'Kathmandu', 'Pokhara', '2025-01-30 10:56:00', '2025-01-31 10:56:00', 1600.00, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `seat_availability`
--

CREATE TABLE `seat_availability` (
  `id` int(11) NOT NULL,
  `route_id` int(11) DEFAULT NULL,
  `seat_number` varchar(5) DEFAULT NULL,
  `booking_time` datetime DEFAULT NULL,
  `status` enum('available','reserved','booked') DEFAULT NULL,
  `bus_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seat_availability`
--

INSERT INTO `seat_availability` (`id`, `route_id`, `seat_number`, `booking_time`, `status`, `bus_id`) VALUES
(489, NULL, '1', NULL, 'available', 1),
(490, NULL, '2', NULL, 'available', 1),
(491, NULL, '3', NULL, 'available', 1),
(492, NULL, '4', NULL, 'available', 1),
(493, NULL, '5', NULL, 'available', 1),
(494, NULL, '6', NULL, 'available', 1),
(495, NULL, '7', NULL, 'available', 1),
(496, NULL, '8', NULL, 'available', 1),
(497, NULL, '9', NULL, 'available', 1),
(498, NULL, '10', NULL, 'available', 1),
(499, NULL, '11', NULL, 'available', 1),
(500, NULL, '12', NULL, 'available', 1),
(501, NULL, '13', NULL, 'available', 1),
(502, NULL, '14', NULL, 'available', 1),
(503, NULL, '15', NULL, 'available', 1),
(504, NULL, '16', NULL, 'available', 1),
(505, NULL, '17', NULL, 'available', 1),
(506, NULL, '18', NULL, 'available', 1),
(507, NULL, '19', NULL, 'available', 1),
(508, NULL, '20', NULL, 'available', 1),
(509, NULL, '21', NULL, 'available', 1),
(510, NULL, '22', NULL, 'available', 1),
(511, NULL, '23', NULL, 'available', 1),
(512, NULL, '24', NULL, 'available', 1),
(513, NULL, '25', NULL, 'available', 1),
(514, NULL, '26', NULL, 'available', 1),
(515, NULL, '27', NULL, 'available', 1),
(516, NULL, '28', NULL, 'available', 1),
(517, NULL, '29', NULL, 'available', 1),
(518, NULL, '30', NULL, 'available', 1),
(519, NULL, '1', NULL, 'available', 2),
(520, NULL, '2', NULL, 'available', 2),
(521, NULL, '3', NULL, 'available', 2),
(522, NULL, '4', NULL, 'available', 2),
(523, NULL, '5', NULL, 'available', 2),
(524, NULL, '6', NULL, 'available', 2),
(525, NULL, '7', NULL, 'available', 2),
(526, NULL, '8', NULL, 'available', 2),
(527, NULL, '9', NULL, 'available', 2),
(528, NULL, '10', NULL, 'available', 2),
(529, NULL, '11', NULL, 'available', 2),
(530, NULL, '12', NULL, 'available', 2),
(531, NULL, '13', NULL, 'available', 2),
(532, NULL, '14', NULL, 'available', 2),
(533, NULL, '15', NULL, 'available', 2),
(534, NULL, '16', NULL, 'available', 2),
(535, NULL, '17', NULL, 'available', 2),
(536, NULL, '18', NULL, 'available', 2),
(537, NULL, '19', NULL, 'available', 2),
(538, NULL, '20', NULL, 'available', 2),
(539, NULL, '21', NULL, 'available', 2),
(540, NULL, '22', NULL, 'available', 2),
(541, NULL, '23', NULL, 'available', 2),
(542, NULL, '24', NULL, 'available', 2),
(543, NULL, '25', NULL, 'available', 2),
(544, NULL, '26', NULL, 'available', 2),
(545, NULL, '27', NULL, 'available', 2),
(546, NULL, '28', NULL, 'available', 2),
(547, NULL, '29', NULL, 'available', 2),
(548, NULL, '30', NULL, 'available', 2),
(549, NULL, '31', NULL, 'available', 2),
(550, NULL, '32', NULL, 'available', 2),
(551, NULL, '1', NULL, 'available', 3),
(552, NULL, '2', NULL, 'available', 3),
(553, NULL, '3', NULL, 'available', 3),
(554, NULL, '4', NULL, 'available', 3),
(555, NULL, '5', NULL, 'available', 3),
(556, NULL, '6', NULL, 'available', 3),
(557, NULL, '7', NULL, 'available', 3),
(558, NULL, '8', NULL, 'available', 3),
(559, NULL, '9', NULL, 'available', 3),
(560, NULL, '10', NULL, 'available', 3),
(561, NULL, '11', NULL, 'available', 3),
(562, NULL, '12', NULL, 'available', 3),
(563, NULL, '13', NULL, 'available', 3),
(564, NULL, '14', NULL, 'available', 3),
(565, NULL, '15', NULL, 'available', 3),
(566, NULL, '16', NULL, 'available', 3),
(567, NULL, '17', NULL, 'available', 3),
(568, NULL, '18', NULL, 'available', 3),
(569, NULL, '19', NULL, 'available', 3),
(570, NULL, '20', NULL, 'available', 3),
(571, NULL, '21', NULL, 'available', 3),
(572, NULL, '22', NULL, 'available', 3),
(573, NULL, '23', NULL, 'available', 3),
(574, NULL, '24', NULL, 'available', 3),
(575, NULL, '25', NULL, 'available', 3),
(576, NULL, '26', NULL, 'available', 3),
(577, NULL, '27', NULL, 'available', 3),
(578, NULL, '28', NULL, 'available', 3),
(579, NULL, '29', NULL, 'available', 3),
(580, NULL, '30', NULL, 'available', 3),
(581, NULL, '31', NULL, 'available', 3),
(582, NULL, '32', NULL, 'available', 3),
(583, NULL, '1', NULL, 'available', 4),
(584, NULL, '2', NULL, 'available', 4),
(585, NULL, '3', NULL, 'available', 4),
(586, NULL, '4', NULL, 'available', 4),
(587, NULL, '5', NULL, 'available', 4),
(588, NULL, '6', NULL, 'available', 4),
(589, NULL, '7', NULL, 'available', 4),
(590, NULL, '8', NULL, 'available', 4),
(591, NULL, '9', NULL, 'available', 4),
(592, NULL, '10', NULL, 'available', 4),
(593, NULL, '11', NULL, 'available', 4),
(594, NULL, '12', NULL, 'available', 4),
(595, NULL, '13', NULL, 'available', 4),
(596, NULL, '14', NULL, 'available', 4),
(597, NULL, '15', NULL, 'available', 4),
(598, NULL, '16', NULL, 'available', 4),
(599, NULL, '17', NULL, 'available', 4),
(600, NULL, '18', NULL, 'available', 4),
(601, NULL, '19', NULL, 'available', 4),
(602, NULL, '20', NULL, 'available', 4),
(603, NULL, '21', NULL, 'available', 4),
(604, NULL, '22', NULL, 'available', 4),
(605, NULL, '23', NULL, 'available', 4),
(606, NULL, '24', NULL, 'available', 4),
(607, NULL, '25', NULL, 'available', 4),
(608, NULL, '26', NULL, 'available', 4),
(609, NULL, '27', NULL, 'available', 4),
(610, NULL, '28', NULL, 'available', 4),
(611, NULL, '1', NULL, 'available', 5),
(612, NULL, '2', NULL, 'available', 5),
(613, NULL, '3', NULL, 'available', 5),
(614, NULL, '4', NULL, 'available', 5),
(615, NULL, '5', NULL, 'available', 5),
(616, NULL, '6', NULL, 'available', 5),
(617, NULL, '7', NULL, 'available', 5),
(618, NULL, '8', NULL, 'available', 5),
(619, NULL, '9', NULL, 'available', 5),
(620, NULL, '10', NULL, 'available', 5),
(621, NULL, '11', NULL, 'available', 5),
(622, NULL, '12', NULL, 'available', 5),
(623, NULL, '13', NULL, 'available', 5),
(624, NULL, '14', NULL, 'available', 5),
(625, NULL, '15', NULL, 'available', 5),
(626, NULL, '16', NULL, 'available', 5),
(627, NULL, '17', NULL, 'available', 5),
(628, NULL, '18', NULL, 'available', 5),
(629, NULL, '19', NULL, 'available', 5),
(630, NULL, '20', NULL, 'available', 5),
(631, NULL, '21', NULL, 'available', 5),
(632, NULL, '22', NULL, 'available', 5),
(633, NULL, '23', NULL, 'available', 5),
(634, NULL, '24', NULL, 'available', 5),
(635, NULL, '25', NULL, 'available', 5),
(636, NULL, '26', NULL, 'available', 5),
(637, NULL, '27', NULL, 'available', 5),
(638, NULL, '28', NULL, 'available', 5),
(639, NULL, '29', NULL, 'available', 5),
(640, NULL, '30', NULL, 'available', 5),
(641, 1, '1', NULL, 'available', 1),
(642, 1, '2', NULL, 'available', 1),
(643, 1, '3', NULL, 'available', 1),
(644, 1, '4', NULL, 'available', 1),
(645, 1, '5', NULL, 'available', 1),
(646, 1, '6', NULL, 'available', 1),
(647, 1, '7', NULL, 'available', 1),
(648, 1, '8', NULL, 'available', 1),
(649, 1, '9', NULL, 'available', 1),
(650, 1, '10', NULL, 'available', 1),
(651, 1, '11', NULL, 'available', 1),
(652, 1, '12', NULL, 'available', 1),
(653, 1, '13', NULL, 'available', 1),
(654, 1, '14', NULL, 'available', 1),
(655, 1, '15', NULL, 'available', 1),
(656, 1, '16', NULL, 'available', 1),
(657, 1, '17', NULL, 'available', 1),
(658, 1, '18', NULL, 'available', 1),
(659, 1, '19', NULL, 'available', 1),
(660, 1, '20', NULL, 'available', 1),
(661, 1, '21', NULL, 'available', 1),
(662, 1, '22', NULL, 'available', 1),
(663, 1, '23', NULL, 'available', 1),
(664, 1, '24', NULL, 'available', 1),
(665, 1, '25', NULL, 'available', 1),
(666, 1, '26', NULL, 'available', 1),
(667, 1, '27', NULL, 'available', 1),
(668, 1, '28', NULL, 'available', 1),
(669, 1, '29', NULL, 'available', 1),
(670, 1, '30', '2025-01-26 10:58:51', 'booked', 1),
(671, 2, '1', NULL, 'available', 1),
(672, 2, '2', NULL, 'available', 1),
(673, 2, '3', NULL, 'available', 1),
(674, 2, '4', NULL, 'available', 1),
(675, 2, '5', NULL, 'available', 1),
(676, 2, '6', NULL, 'available', 1),
(677, 2, '7', NULL, 'available', 1),
(678, 2, '8', NULL, 'available', 1),
(679, 2, '9', NULL, 'available', 1),
(680, 2, '10', NULL, 'available', 1),
(681, 2, '11', NULL, 'available', 1),
(682, 2, '12', NULL, 'available', 1),
(683, 2, '13', NULL, 'available', 1),
(684, 2, '14', NULL, 'available', 1),
(685, 2, '15', NULL, 'available', 1),
(686, 2, '16', NULL, 'available', 1),
(687, 2, '17', NULL, 'available', 1),
(688, 2, '18', NULL, 'available', 1),
(689, 2, '19', NULL, 'available', 1),
(690, 2, '20', NULL, 'available', 1),
(691, 2, '21', NULL, 'available', 1),
(692, 2, '22', NULL, 'available', 1),
(693, 2, '23', NULL, 'available', 1),
(694, 2, '24', NULL, 'available', 1),
(695, 2, '25', NULL, 'available', 1),
(696, 2, '26', NULL, 'available', 1),
(697, 2, '27', NULL, 'available', 1),
(698, 2, '28', NULL, 'available', 1),
(699, 2, '29', NULL, 'available', 1),
(700, 2, '30', NULL, 'available', 1),
(701, 3, '1', NULL, 'available', 2),
(702, 3, '2', NULL, 'available', 2),
(703, 3, '3', NULL, 'available', 2),
(704, 3, '4', '2025-01-26 11:02:59', 'booked', 2),
(705, 3, '5', NULL, 'available', 2),
(706, 3, '6', '2025-01-26 11:02:59', 'booked', 2),
(707, 3, '7', NULL, 'available', 2),
(708, 3, '8', '2025-01-26 11:02:59', 'booked', 2),
(709, 3, '9', NULL, 'available', 2),
(710, 3, '10', NULL, 'available', 2),
(711, 3, '11', NULL, 'available', 2),
(712, 3, '12', NULL, 'available', 2),
(713, 3, '13', NULL, 'available', 2),
(714, 3, '14', NULL, 'available', 2),
(715, 3, '15', NULL, 'available', 2),
(716, 3, '16', NULL, 'available', 2),
(717, 3, '17', NULL, 'available', 2),
(718, 3, '18', NULL, 'available', 2),
(719, 3, '19', NULL, 'available', 2),
(720, 3, '20', NULL, 'available', 2),
(721, 3, '21', NULL, 'available', 2),
(722, 3, '22', NULL, 'available', 2),
(723, 3, '23', NULL, 'available', 2),
(724, 3, '24', NULL, 'available', 2),
(725, 3, '25', NULL, 'available', 2),
(726, 3, '26', NULL, 'available', 2),
(727, 3, '27', NULL, 'available', 2),
(728, 3, '28', NULL, 'available', 2),
(729, 3, '29', NULL, 'available', 2),
(730, 3, '30', NULL, 'available', 2),
(731, 3, '31', NULL, 'available', 2),
(732, 3, '32', NULL, 'available', 2),
(733, 4, '1', NULL, 'available', 2),
(734, 4, '2', NULL, 'available', 2),
(735, 4, '3', NULL, 'available', 2),
(736, 4, '4', NULL, 'available', 2),
(737, 4, '5', NULL, 'available', 2),
(738, 4, '6', NULL, 'available', 2),
(739, 4, '7', NULL, 'available', 2),
(740, 4, '8', NULL, 'available', 2),
(741, 4, '9', NULL, 'available', 2),
(742, 4, '10', NULL, 'available', 2),
(743, 4, '11', NULL, 'available', 2),
(744, 4, '12', NULL, 'available', 2),
(745, 4, '13', NULL, 'available', 2),
(746, 4, '14', NULL, 'available', 2),
(747, 4, '15', NULL, 'available', 2),
(748, 4, '16', '2025-01-26 11:00:34', 'booked', 2),
(749, 4, '17', NULL, 'available', 2),
(750, 4, '18', NULL, 'available', 2),
(751, 4, '19', NULL, 'available', 2),
(752, 4, '20', NULL, 'available', 2),
(753, 4, '21', NULL, 'available', 2),
(754, 4, '22', NULL, 'available', 2),
(755, 4, '23', NULL, 'available', 2),
(756, 4, '24', NULL, 'available', 2),
(757, 4, '25', NULL, 'available', 2),
(758, 4, '26', NULL, 'available', 2),
(759, 4, '27', NULL, 'available', 2),
(760, 4, '28', NULL, 'available', 2),
(761, 4, '29', NULL, 'available', 2),
(762, 4, '30', NULL, 'available', 2),
(763, 4, '31', NULL, 'available', 2),
(764, 4, '32', NULL, 'available', 2),
(765, 5, '1', NULL, 'available', 5),
(766, 5, '2', NULL, 'available', 5),
(767, 5, '3', NULL, 'available', 5),
(768, 5, '4', NULL, 'available', 5),
(769, 5, '5', NULL, 'available', 5),
(770, 5, '6', NULL, 'available', 5),
(771, 5, '7', NULL, 'available', 5),
(772, 5, '8', NULL, 'available', 5),
(773, 5, '9', NULL, 'available', 5),
(774, 5, '10', NULL, 'available', 5),
(775, 5, '11', NULL, 'available', 5),
(776, 5, '12', NULL, 'available', 5),
(777, 5, '13', NULL, 'available', 5),
(778, 5, '14', NULL, 'available', 5),
(779, 5, '15', NULL, 'available', 5),
(780, 5, '16', NULL, 'available', 5),
(781, 5, '17', NULL, 'available', 5),
(782, 5, '18', NULL, 'available', 5),
(783, 5, '19', NULL, 'available', 5),
(784, 5, '20', NULL, 'available', 5),
(785, 5, '21', NULL, 'available', 5),
(786, 5, '22', NULL, 'available', 5),
(787, 5, '23', NULL, 'available', 5),
(788, 5, '24', NULL, 'available', 5),
(789, 5, '25', NULL, 'available', 5),
(790, 5, '26', NULL, 'available', 5),
(791, 5, '27', NULL, 'available', 5),
(792, 5, '28', NULL, 'available', 5),
(793, 5, '29', NULL, 'available', 5),
(794, 5, '30', NULL, 'available', 5),
(795, 6, '1', NULL, 'available', 4),
(796, 6, '2', NULL, 'available', 4),
(797, 6, '3', NULL, 'available', 4),
(798, 6, '4', NULL, 'available', 4),
(799, 6, '5', NULL, 'available', 4),
(800, 6, '6', NULL, 'available', 4),
(801, 6, '7', NULL, 'available', 4),
(802, 6, '8', NULL, 'available', 4),
(803, 6, '9', NULL, 'available', 4),
(804, 6, '10', NULL, 'available', 4),
(805, 6, '11', NULL, 'available', 4),
(806, 6, '12', NULL, 'available', 4),
(807, 6, '13', NULL, 'available', 4),
(808, 6, '14', NULL, 'available', 4),
(809, 6, '15', NULL, 'available', 4),
(810, 6, '16', NULL, 'available', 4),
(811, 6, '17', NULL, 'available', 4),
(812, 6, '18', NULL, 'available', 4),
(813, 6, '19', NULL, 'available', 4),
(814, 6, '20', NULL, 'available', 4),
(815, 6, '21', NULL, 'available', 4),
(816, 6, '22', NULL, 'available', 4),
(817, 6, '23', NULL, 'available', 4),
(818, 6, '24', NULL, 'available', 4),
(819, 6, '25', NULL, 'available', 4),
(820, 6, '26', NULL, 'available', 4),
(821, 6, '27', NULL, 'available', 4),
(822, 6, '28', NULL, 'available', 4);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`, `phone`) VALUES
(5, 'Ram Thapa', 'ram22@gmail.com', '$2y$10$xCRQmxFT6cZ9u8EmDSew8OpdKtTB716M1XE2B/y7DVjcXZ26lTrwW', '2025-01-26 04:39:26', '9814256378'),
(6, 'Nisha Chaudhary', 'nisha11@gmail.com', '$2y$10$ecE1nO0Z5Xd4YFVbotpZW.YgVzGLDsltphfYSVIrtEf97UnIWoa4y', '2025-01-26 04:41:00', '9814256789'),
(7, 'Manish Gurung', 'manish33@gmail.com', '$2y$10$sTXtjgkDkdHaFqGFP2GDUOC0SotoPliwNqaav.rLq5uKDFQNQOZcS', '2025-01-26 04:41:42', '9874562563'),
(8, 'Naresh Thakuri', 'naresh01@gmail.com', '$2y$10$aHSBhmQ5DwCWaANkaNr5SuxlOkTmJ6x27tZULxEREcyqGM6tofAvG', '2025-01-26 04:42:17', '9814556320'),
(9, 'Shobha', 'shobha23@gmail.com', '$2y$10$PvF3NPnC8qNzFiWhciSqhOWbzsqIL5cA4lQgqzZvumx1zkE5FLvl6', '2025-01-26 04:42:47', '9874122563'),
(10, 'Janaki Thakuri', 'jan234@gmail.com', '$2y$10$aJv03a8bL.iJGEA8JwIo1OAhh3.oHlOg2qQbU4oRhjpC.lTHG65Qu', '2025-01-26 04:43:39', '9814523654'),
(11, 'Sudip KC', 'sudip99@gmail.com', '$2y$10$3UW6hxAJ3J3z6p/4YlAR..tA.2.XrC8KZ/JLzqNiaFvH8/F2fYcDu', '2025-01-26 04:44:36', '9825634569');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `bookings_ibfk_2` (`route_id`);

--
-- Indexes for table `buses`
--
ALTER TABLE `buses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bus_id` (`bus_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bus_id` (`bus_id`);

--
-- Indexes for table `seat_availability`
--
ALTER TABLE `seat_availability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bus_id` (`bus_id`),
  ADD KEY `fk_route` (`route_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `buses`
--
ALTER TABLE `buses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `seat_availability`
--
ALTER TABLE `seat_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=823;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`);

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `routes`
--
ALTER TABLE `routes`
  ADD CONSTRAINT `routes_ibfk_1` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seat_availability`
--
ALTER TABLE `seat_availability`
  ADD CONSTRAINT `fk_bus_id` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_route` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `seat_availability_ibfk_1` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
