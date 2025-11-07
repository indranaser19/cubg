-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 07, 2025 at 01:26 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cubg_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Nama Cabang (Cikini, Tangerang, dll)',
  `address` text,
  `tv_config_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'Konfigurasi TV Info per cabang',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `running_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `name`, `address`, `tv_config_json`, `created_at`, `running_text`) VALUES
(1, 'Kantor Pusat', NULL, NULL, '2025-10-29 22:06:59', 'Selamat Datang di CU Bererod Gratia, informasi lengkap bisa kunjungi website : koperasi-cubg.org'),
(2, 'Cikini', NULL, NULL, '2025-10-29 22:06:59', 'Selamat Datang di CU Bererod Gratia, informasi lengkap bisa kunjungi website : koperasi-cubg.org'),
(3, 'Tangerang', NULL, NULL, '2025-10-29 22:06:59', 'Selamat Datang di CU Bererod Gratia, informasi lengkap bisa kunjungi website : koperasi-cubg.org'),
(4, 'Kampung Sawah', NULL, NULL, '2025-10-29 22:06:59', 'Selamat Datang di CU Bererod Gratia, informasi lengkap bisa kunjungi website : koperasi-cubg.org'),
(5, 'Tanjung Priok', NULL, NULL, '2025-10-29 22:06:59', 'Selamat Datang di CU Bererod Gratia, informasi lengkap bisa kunjungi website : koperasi-cubg.org'),
(6, 'Bintaro', NULL, NULL, '2025-10-29 22:06:59', 'Selamat Datang di CU Bererod Gratia, informasi lengkap bisa kunjungi website : koperasi-cubg.org'),
(7, 'Duren Sawit', NULL, NULL, '2025-10-29 22:06:59', 'Selamat Datang di CU Bererod Gratia, informasi lengkap bisa kunjungi website : koperasi-cubg.org'),
(8, 'Pamulang', NULL, NULL, '2025-10-29 22:06:59', 'Selamat Datang di CU Bererod Gratia, informasi lengkap bisa kunjungi website : koperasi-cubg.org'),
(9, 'Bantar Gebang', NULL, NULL, '2025-10-29 22:06:59', 'Selamat Datang di CU Bererod Gratia, informasi lengkap bisa kunjungi website : koperasi-cubg.org'),
(10, 'Pasar Kemis', NULL, NULL, '2025-10-29 22:06:59', 'Selamat Datang di CU Bererod Gratia, informasi lengkap bisa kunjungi website : koperasi-cubg.org');

-- --------------------------------------------------------

--
-- Table structure for table `fma_applications`
--

CREATE TABLE `fma_applications` (
  `id` int NOT NULL,
  `branch_id` int NOT NULL,
  `kode_tracking` varchar(20) NOT NULL,
  `nama_lengkap` varchar(255) NOT NULL,
  `nama_panggilan` varchar(100) DEFAULT NULL,
  `no_ktp` varchar(20) NOT NULL,
  `tempat_lahir` varchar(100) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') NOT NULL,
  `status_perkawinan` varchar(50) NOT NULL,
  `nama_pasangan` varchar(255) DEFAULT NULL,
  `nama_gadis_ibu_kandung` varchar(255) NOT NULL,
  `agama` varchar(50) DEFAULT NULL,
  `pendidikan_terakhir` varchar(50) DEFAULT NULL,
  `pekerjaan` varchar(100) DEFAULT NULL,
  `usaha` varchar(100) DEFAULT NULL,
  `alamat_tempat_kerja` text,
  `pendapatan_bulanan` varchar(100) DEFAULT NULL,
  `no_telepon` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `alamat_ktp` text NOT NULL,
  `alamat_domisili` text NOT NULL,
  `anggota_cu_lain` enum('Ya','Tidak') NOT NULL,
  `sumber_informasi` varchar(100) DEFAULT NULL,
  `nama_perekomendasi` varchar(255) DEFAULT NULL,
  `nama_ahli_waris` varchar(255) NOT NULL,
  `hubungan_ahli_waris` varchar(50) NOT NULL,
  `status_permohonan` enum('Baru','Diproses','Diterima','Ditolak','Perlu Lengkapi','Sudah Menjadi Anggota') NOT NULL DEFAULT 'Baru',
  `catatan_admin` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by_user_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `fma_applications`
--

INSERT INTO `fma_applications` (`id`, `branch_id`, `kode_tracking`, `nama_lengkap`, `nama_panggilan`, `no_ktp`, `tempat_lahir`, `tanggal_lahir`, `jenis_kelamin`, `status_perkawinan`, `nama_pasangan`, `nama_gadis_ibu_kandung`, `agama`, `pendidikan_terakhir`, `pekerjaan`, `usaha`, `alamat_tempat_kerja`, `pendapatan_bulanan`, `no_telepon`, `email`, `alamat_ktp`, `alamat_domisili`, `anggota_cu_lain`, `sumber_informasi`, `nama_perekomendasi`, `nama_ahli_waris`, `hubungan_ahli_waris`, `status_permohonan`, `catatan_admin`, `created_at`, `updated_at`, `updated_by_user_id`) VALUES
(1, 2, 'FMA-20251101-2E4C', 'Indra Naser Tola', 'Indra', '1234567890123456', 'Ternate', '2025-11-01', 'Laki-laki', 'Kawin', 'Emilia', 'Ribka', 'Katholik', 'S1', 'Karyawan Swasta', 'Lainnya', '5, Jl. Utan Kayu Raya No.46, RT.5/RW.5, Utan Kayu Utara, Kec. Matraman, Kota Jakarta Timur, Daerah Khusus Ibukota Jakarta 13120', '> 5.000.000 - 6.000.000', '081280901779', 'indra.naser@live.com', '5, Jl. Utan Kayu Raya No.46, RT.5/RW.5, Utan Kayu Utara, Kec. Matraman, Kota Jakarta Timur, Daerah Khusus Ibukota Jakarta 13120', '5, Jl. Utan Kayu Raya No.46, RT.5/RW.5, Utan Kayu Utara, Kec. Matraman, Kota Jakarta Timur, Daerah Khusus Ibukota Jakarta 13120', 'Tidak', 'Rekomendasi Anggota', 'Ada', 'Calla', 'Anak', 'Baru', '', '2025-11-01 05:57:52', '2025-11-01 09:13:21', 1);

-- --------------------------------------------------------

--
-- Table structure for table `loan_applications`
--

CREATE TABLE `loan_applications` (
  `id` int NOT NULL,
  `branch_id` int NOT NULL,
  `created_by_user_id` int NOT NULL,
  `application_number` varchar(50) DEFAULT NULL COMMENT 'Nomor Permohonan (bisa di-generate)',
  `application_date` date DEFAULT NULL,
  `applicant_name` varchar(100) NOT NULL,
  `applicant_ba_number` varchar(50) DEFAULT NULL,
  `applicant_occupation` varchar(100) DEFAULT NULL,
  `applicant_position` varchar(100) DEFAULT NULL,
  `applicant_birth_place` varchar(100) DEFAULT NULL,
  `applicant_birth_date` date DEFAULT NULL,
  `applicant_gender` enum('Laki-laki','Perempuan') DEFAULT NULL,
  `applicant_ktp_address` text,
  `applicant_marital_status` enum('Kawin','Tidak Kawin') DEFAULT NULL,
  `applicant_current_address` text,
  `applicant_phone` varchar(20) DEFAULT NULL,
  `spouse_name` varchar(100) DEFAULT NULL,
  `spouse_ba_number` varchar(50) DEFAULT NULL,
  `spouse_occupation` varchar(100) DEFAULT NULL,
  `spouse_position` varchar(100) DEFAULT NULL,
  `spouse_work_address` text,
  `spouse_work_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_address` text,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_relation` varchar(50) DEFAULT NULL,
  `financial_saving_saham` decimal(15,2) DEFAULT NULL,
  `financial_saving_megapolitan` decimal(15,2) DEFAULT NULL,
  `financial_saving_padanan` decimal(15,2) DEFAULT NULL,
  `financial_remaining_loan` decimal(15,2) DEFAULT NULL,
  `financial_other_loan` decimal(15,2) DEFAULT NULL,
  `financial_other_savings` varchar(255) DEFAULT NULL COMMENT 'Simpanan lainnya: Bank BCA/BRI/dll',
  `loan_amount_requested` decimal(15,2) NOT NULL,
  `loan_term_months` int NOT NULL COMMENT 'Jangka waktu (bulan)',
  `loan_type` varchar(100) DEFAULT NULL,
  `tracking_code` varchar(50) DEFAULT NULL,
  `loan_purpose` text,
  `loan_collateral_type` varchar(255) DEFAULT NULL COMMENT 'Jaminan yg diserahkan',
  `loan_collateral_owner` varchar(100) DEFAULT NULL,
  `loan_collateral_status` varchar(100) DEFAULT NULL,
  `loan_collateral_location` text,
  `loan_collateral_value` decimal(15,2) DEFAULT NULL,
  `loan_monthly_payment_capacity` decimal(15,2) DEFAULT NULL,
  `status_id` int NOT NULL DEFAULT '1' COMMENT 'Foreign key ke loan_statuses',
  `status` enum('baru','diproses','disetujui','ditolak') DEFAULT 'baru',
  `sub_status` varchar(100) DEFAULT NULL COMMENT 'Survei, Rapat Cabang, Rapat Komite, Rapat Dewan',
  `is_deleted` tinyint(1) DEFAULT '0' COMMENT 'Flag untuk soft delete',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Tabel inti untuk permohonan pinjaman (dari PDF Hal. 1)';

--
-- Dumping data for table `loan_applications`
--

INSERT INTO `loan_applications` (`id`, `branch_id`, `created_by_user_id`, `application_number`, `application_date`, `applicant_name`, `applicant_ba_number`, `applicant_occupation`, `applicant_position`, `applicant_birth_place`, `applicant_birth_date`, `applicant_gender`, `applicant_ktp_address`, `applicant_marital_status`, `applicant_current_address`, `applicant_phone`, `spouse_name`, `spouse_ba_number`, `spouse_occupation`, `spouse_position`, `spouse_work_address`, `spouse_work_phone`, `emergency_contact_name`, `emergency_contact_address`, `emergency_contact_phone`, `emergency_contact_relation`, `financial_saving_saham`, `financial_saving_megapolitan`, `financial_saving_padanan`, `financial_remaining_loan`, `financial_other_loan`, `financial_other_savings`, `loan_amount_requested`, `loan_term_months`, `loan_type`, `tracking_code`, `loan_purpose`, `loan_collateral_type`, `loan_collateral_owner`, `loan_collateral_status`, `loan_collateral_location`, `loan_collateral_value`, `loan_monthly_payment_capacity`, `status_id`, `status`, `sub_status`, `is_deleted`, `created_at`, `updated_at`) VALUES
(2, 2, 2, NULL, '2025-10-30', 'Indra Naser', '', '', '', '', NULL, 'Laki-laki', '', 'Tidak Kawin', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, '', '10000000.00', 24, 'Pinjaman Kendaraan - Motor', NULL, '', '', '', '', '', NULL, NULL, 7, 'baru', NULL, 1, '2025-10-30 08:39:58', '2025-11-01 07:18:23'),
(3, 2, 2, NULL, '2025-10-30', 'indra', '', '', '', '', NULL, 'Laki-laki', '', 'Tidak Kawin', '', '', '', '', '', '', '', '', '', '', '', '', '0.00', '0.00', '0.00', '0.00', '0.00', '0', '3000000.00', 24, 'Pinjaman Produktif', NULL, '', '', '', '', '', '0.00', '0.00', 1, 'baru', NULL, 1, '2025-10-30 10:36:39', '2025-11-01 07:18:34'),
(4, 2, 2, NULL, '2025-10-31', 'indraaaaa', '', '', '', '', NULL, 'Laki-laki', '', 'Tidak Kawin', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, '', '80000000.00', 24, 'Pinjaman Produktif', 'CUBG-002-251031-4', '', '', '', '', '', NULL, NULL, 1, 'baru', NULL, 1, '2025-10-31 02:58:08', '2025-11-01 07:18:19'),
(5, 2, 2, NULL, '2025-10-31', 'inn', '', '', '', '', NULL, 'Laki-laki', '', 'Tidak Kawin', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, '', '10000000.00', 22, 'Pinjaman Produktif', 'CUBG-002-251031-5', '', '', '', '', '', NULL, NULL, 5, 'baru', NULL, 1, '2025-10-31 03:09:50', '2025-11-01 07:18:09'),
(6, 2, 2, NULL, '2025-10-31', 'Indra', '030059003002568', 'Staf', 'Staf', 'Ternate', '2025-10-31', 'Laki-laki', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, ', 'Kawin', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, ', '081280901779', 'Emillia', '030059003002767', 'staf', 'staf', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', '23424234234', 'Emilia', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit', '3453453', 'test', '10000000.00', '20000000.00', '80000.00', '8000000.00', '800000.00', '900000', '80000000.00', 0, 'Pinjaman Mikro Gratia', 'CUBG-002-251031-6', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, ', 'BPKB', 'indra', 'test', 'good', '900000.00', '200000.00', 4, 'baru', NULL, 0, '2025-10-31 03:48:15', '2025-11-01 07:17:39'),
(7, 2, 2, NULL, '2025-11-03', 'Indra Naser', '', '', '', '', NULL, 'Laki-laki', '', 'Tidak Kawin', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, '', '3000000.00', 24, 'Pinjaman Produktif', 'CUBG-002-251103-7', '', '', '', '', '', NULL, NULL, 1, 'baru', NULL, 0, '2025-11-03 08:40:07', '2025-11-03 08:40:14');

-- --------------------------------------------------------

--
-- Table structure for table `loan_application_details`
--

CREATE TABLE `loan_application_details` (
  `id` int NOT NULL,
  `loan_app_id` int NOT NULL COMMENT 'Relasi ke loan_applications.id',
  `sales_monthly` decimal(15,2) DEFAULT NULL,
  `sales_total` decimal(15,2) DEFAULT NULL,
  `cogs_monthly` decimal(15,2) DEFAULT NULL,
  `cogs_total` decimal(15,2) DEFAULT NULL,
  `op_payroll_monthly` decimal(15,2) DEFAULT NULL,
  `op_payroll_total` decimal(15,2) DEFAULT NULL,
  `op_rent_monthly` decimal(15,2) DEFAULT NULL,
  `op_rent_total` decimal(15,2) DEFAULT NULL,
  `op_utilities_monthly` decimal(15,2) DEFAULT NULL,
  `op_utilities_total` decimal(15,2) DEFAULT NULL,
  `op_transport_monthly` decimal(15,2) DEFAULT NULL,
  `op_transport_total` decimal(15,2) DEFAULT NULL,
  `op_admin_monthly` decimal(15,2) DEFAULT NULL,
  `op_admin_total` decimal(15,2) DEFAULT NULL,
  `op_maintenance_monthly` decimal(15,2) DEFAULT NULL,
  `op_maintenance_total` decimal(15,2) DEFAULT NULL,
  `op_promotion_monthly` decimal(15,2) DEFAULT NULL,
  `op_promotion_total` decimal(15,2) DEFAULT NULL,
  `modal_cubg_loan` decimal(15,2) DEFAULT NULL,
  `modal_equity` decimal(15,2) DEFAULT NULL,
  `modal_other_source` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `modal_other_amount` decimal(15,2) DEFAULT NULL,
  `asset_cash` decimal(15,2) DEFAULT NULL,
  `asset_bank_savings` decimal(15,2) DEFAULT NULL,
  `asset_cu_daily_savings` decimal(15,2) DEFAULT NULL,
  `asset_current_other` decimal(15,2) DEFAULT NULL,
  `invest_megapolitan_savings` decimal(15,2) DEFAULT NULL,
  `invest_other_cu_savings` decimal(15,2) DEFAULT NULL,
  `invest_business_assets` decimal(15,2) DEFAULT NULL,
  `invest_commercial_property` decimal(15,2) DEFAULT NULL,
  `invest_other` decimal(15,2) DEFAULT NULL,
  `asset_home_value` decimal(15,2) DEFAULT NULL,
  `asset_home_contents_value` decimal(15,2) DEFAULT NULL,
  `asset_vehicle_value` decimal(15,2) DEFAULT NULL,
  `asset_jewelry_value` decimal(15,2) DEFAULT NULL,
  `asset_personal_other` decimal(15,2) DEFAULT NULL,
  `liability_cu_short_term` decimal(15,2) DEFAULT NULL,
  `liability_credit_card_kta` decimal(15,2) DEFAULT NULL,
  `liability_short_term_other` decimal(15,2) DEFAULT NULL,
  `liability_housing_loan` decimal(15,2) DEFAULT NULL,
  `liability_vehicle_loan` decimal(15,2) DEFAULT NULL,
  `liability_consumptive_loan` decimal(15,2) DEFAULT NULL,
  `liability_productive_loan` decimal(15,2) DEFAULT NULL,
  `loan_term_months` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan_application_details`
--

INSERT INTO `loan_application_details` (`id`, `loan_app_id`, `sales_monthly`, `sales_total`, `cogs_monthly`, `cogs_total`, `op_payroll_monthly`, `op_payroll_total`, `op_rent_monthly`, `op_rent_total`, `op_utilities_monthly`, `op_utilities_total`, `op_transport_monthly`, `op_transport_total`, `op_admin_monthly`, `op_admin_total`, `op_maintenance_monthly`, `op_maintenance_total`, `op_promotion_monthly`, `op_promotion_total`, `modal_cubg_loan`, `modal_equity`, `modal_other_source`, `modal_other_amount`, `asset_cash`, `asset_bank_savings`, `asset_cu_daily_savings`, `asset_current_other`, `invest_megapolitan_savings`, `invest_other_cu_savings`, `invest_business_assets`, `invest_commercial_property`, `invest_other`, `asset_home_value`, `asset_home_contents_value`, `asset_vehicle_value`, `asset_jewelry_value`, `asset_personal_other`, `liability_cu_short_term`, `liability_credit_card_kta`, `liability_short_term_other`, `liability_housing_loan`, `liability_vehicle_loan`, `liability_consumptive_loan`, `liability_productive_loan`, `loan_term_months`) VALUES
(1, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(2, 3, '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', 0),
(3, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(4, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(5, 6, '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', 0),
(6, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `loan_application_logs`
--

CREATE TABLE `loan_application_logs` (
  `id` int NOT NULL,
  `loan_application_id` int NOT NULL,
  `user_id` int NOT NULL,
  `action` varchar(100) NOT NULL COMMENT 'e.g., status_update, sub_status_update, create',
  `sub_status` varchar(100) DEFAULT NULL COMMENT 'Survei, Rapat Cabang, dll.',
  `notes` text,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Audit trail untuk setiap perubahan status pinjaman';

--
-- Dumping data for table `loan_application_logs`
--

INSERT INTO `loan_application_logs` (`id`, `loan_application_id`, `user_id`, `action`, `sub_status`, `notes`, `timestamp`) VALUES
(1, 6, 1, 'soft_delete', NULL, 'Data dipindahkan ke recycle bin', '2025-11-01 07:13:19'),
(2, 4, 1, 'soft_delete', NULL, 'Data dipindahkan ke recycle bin', '2025-11-01 07:13:52'),
(3, 4, 1, 'restore', NULL, 'Data dikembalikan dari recycle bin', '2025-11-01 07:16:57'),
(4, 6, 1, 'restore', NULL, 'Data dikembalikan dari recycle bin', '2025-11-01 07:17:39'),
(5, 5, 1, 'soft_delete', NULL, 'Data dipindahkan ke recycle bin', '2025-11-01 07:18:09'),
(6, 4, 1, 'soft_delete', NULL, 'Data dipindahkan ke recycle bin', '2025-11-01 07:18:19'),
(7, 2, 1, 'soft_delete', NULL, 'Data dipindahkan ke recycle bin', '2025-11-01 07:18:23'),
(8, 3, 1, 'soft_delete', NULL, 'Data dipindahkan ke recycle bin', '2025-11-01 07:18:34');

-- --------------------------------------------------------

--
-- Table structure for table `loan_business_finances`
--

CREATE TABLE `loan_business_finances` (
  `id` int NOT NULL,
  `loan_application_id` int NOT NULL,
  `sales_monthly` decimal(15,2) DEFAULT NULL,
  `sales_total` decimal(15,2) DEFAULT NULL,
  `cogs_monthly` decimal(15,2) DEFAULT NULL,
  `cogs_total` decimal(15,2) DEFAULT NULL,
  `op_payroll_monthly` decimal(15,2) DEFAULT NULL,
  `op_payroll_total` decimal(15,2) DEFAULT NULL,
  `op_rent_monthly` decimal(15,2) DEFAULT NULL,
  `op_rent_total` decimal(15,2) DEFAULT NULL,
  `op_utilities_monthly` decimal(15,2) DEFAULT NULL,
  `op_utilities_total` decimal(15,2) DEFAULT NULL,
  `op_transport_monthly` decimal(15,2) DEFAULT NULL,
  `op_transport_total` decimal(15,2) DEFAULT NULL,
  `op_admin_monthly` decimal(15,2) DEFAULT NULL,
  `op_admin_total` decimal(15,2) DEFAULT NULL,
  `op_maintenance_monthly` decimal(15,2) DEFAULT NULL,
  `op_maintenance_total` decimal(15,2) DEFAULT NULL,
  `op_promotion_monthly` decimal(15,2) DEFAULT NULL,
  `op_promotion_total` decimal(15,2) DEFAULT NULL,
  `modal_cubg_loan` decimal(15,2) DEFAULT NULL,
  `modal_equity` decimal(15,2) DEFAULT NULL,
  `modal_other_source` varchar(100) DEFAULT NULL,
  `modal_other_amount` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Data Laba/Rugi dan Modal Usaha (dari PDF Hal. 2)';

-- --------------------------------------------------------

--
-- Table structure for table `loan_documents`
--

CREATE TABLE `loan_documents` (
  `id` int NOT NULL,
  `loan_application_id` int NOT NULL,
  `uploaded_by_user_id` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `document_type` varchar(100) DEFAULT NULL COMMENT 'e.g., KTP, KK, Slip Gaji',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Menyimpan path file dokumen pendukung';

-- --------------------------------------------------------

--
-- Table structure for table `loan_net_worth`
--

CREATE TABLE `loan_net_worth` (
  `id` int NOT NULL,
  `loan_application_id` int NOT NULL,
  `asset_cash` decimal(15,2) DEFAULT NULL,
  `asset_bank_savings` decimal(15,2) DEFAULT NULL,
  `asset_cu_daily_savings` decimal(15,2) DEFAULT NULL,
  `asset_current_other` decimal(15,2) DEFAULT NULL,
  `invest_megapolitan_savings` decimal(15,2) DEFAULT NULL,
  `invest_other_cu_savings` decimal(15,2) DEFAULT NULL,
  `invest_business_assets` decimal(15,2) DEFAULT NULL,
  `invest_commercial_property` decimal(15,2) DEFAULT NULL,
  `invest_other` decimal(15,2) DEFAULT NULL,
  `asset_home_value` decimal(15,2) DEFAULT NULL,
  `asset_home_contents_value` decimal(15,2) DEFAULT NULL,
  `asset_vehicle_value` decimal(15,2) DEFAULT NULL,
  `asset_jewelry_value` decimal(15,2) DEFAULT NULL,
  `asset_personal_other` decimal(15,2) DEFAULT NULL,
  `liability_cu_short_term` decimal(15,2) DEFAULT NULL,
  `liability_credit_card_kta` decimal(15,2) DEFAULT NULL,
  `liability_short_term_other` decimal(15,2) DEFAULT NULL,
  `liability_housing_loan` decimal(15,2) DEFAULT NULL,
  `liability_vehicle_loan` decimal(15,2) DEFAULT NULL,
  `liability_consumptive_loan` decimal(15,2) DEFAULT NULL,
  `liability_productive_loan` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Data Kekayaan Bersih (dari PDF Hal. 3)';

-- --------------------------------------------------------

--
-- Table structure for table `loan_statuses`
--

CREATE TABLE `loan_statuses` (
  `id` int NOT NULL,
  `status_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `badge_class` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'e.g., bg-primary, bg-success'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan_statuses`
--

INSERT INTO `loan_statuses` (`id`, `status_name`, `badge_class`) VALUES
(1, 'Baru Dibuat', 'bg-primary'),
(2, 'Diproses (Survei)', 'bg-info'),
(3, 'Disetujui', 'bg-success'),
(4, 'Ditolak', 'bg-danger'),
(5, 'Lunas', 'bg-secondary'),
(6, 'Diproses (Rapat KC)', 'bg-warning'),
(7, 'Diproses (Rapat KP)', 'bg-warning'),
(8, 'Diproses (Rapat DP)', 'bg-warning');

-- --------------------------------------------------------

--
-- Table structure for table `loan_status_history`
--

CREATE TABLE `loan_status_history` (
  `id` int NOT NULL,
  `loan_app_id` int NOT NULL,
  `status_id` int NOT NULL,
  `notes` text,
  `changed_by_user_id` int NOT NULL,
  `changed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `loan_status_history`
--

INSERT INTO `loan_status_history` (`id`, `loan_app_id`, `status_id`, `notes`, `changed_by_user_id`, `changed_at`) VALUES
(1, 2, 7, '', 2, '2025-10-30 17:36:06'),
(2, 6, 1, 'test', 2, '2025-10-31 11:20:16'),
(3, 6, 1, 'test', 2, '2025-10-31 11:20:16'),
(4, 6, 2, '', 2, '2025-10-31 11:20:51'),
(5, 6, 6, '', 2, '2025-10-31 11:27:20'),
(6, 6, 4, 'kurang dokumennya', 2, '2025-10-31 11:27:48'),
(7, 5, 2, 'tanggal 31 februari', 2, '2025-10-31 12:10:52'),
(8, 5, 6, ' dhcfghjfj', 2, '2025-10-31 12:11:17'),
(9, 5, 8, '', 2, '2025-10-31 12:11:32'),
(10, 5, 4, '', 2, '2025-10-31 12:12:01'),
(11, 5, 3, '', 2, '2025-10-31 12:12:18'),
(12, 5, 4, '', 2, '2025-10-31 12:12:29'),
(13, 5, 5, '', 2, '2025-10-31 12:12:38');

-- --------------------------------------------------------

--
-- Table structure for table `queue_numbers`
--

CREATE TABLE `queue_numbers` (
  `id` int NOT NULL,
  `branch_id` int NOT NULL,
  `queue_number` int NOT NULL,
  `status` enum('waiting','called','skipped','finished') DEFAULT 'waiting',
  `created_at_date` date NOT NULL,
  `created_at_time` time NOT NULL,
  `called_at` datetime DEFAULT NULL,
  `served_at` datetime DEFAULT NULL,
  `teller_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Manajemen nomor antrian harian per cabang';

--
-- Dumping data for table `queue_numbers`
--

INSERT INTO `queue_numbers` (`id`, `branch_id`, `queue_number`, `status`, `created_at_date`, `created_at_time`, `called_at`, `served_at`, `teller_id`) VALUES
(1, 2, 1, 'finished', '2025-10-30', '14:22:01', '2025-10-30 14:22:16', '2025-10-30 14:33:15', 3),
(2, 2, 2, 'finished', '2025-10-30', '14:31:41', '2025-10-30 14:33:18', '2025-10-30 14:33:47', 3),
(3, 2, 3, 'finished', '2025-10-30', '14:33:05', '2025-10-30 14:33:49', '2025-10-30 14:33:53', 3),
(4, 2, 4, 'finished', '2025-10-30', '14:36:58', '2025-10-30 14:37:10', '2025-10-30 14:47:34', 3),
(5, 2, 5, 'skipped', '2025-10-30', '14:48:04', '2025-10-30 14:49:27', NULL, 3),
(6, 2, 6, 'skipped', '2025-10-30', '14:50:12', '2025-10-30 14:50:18', NULL, 3),
(7, 2, 7, 'finished', '2025-10-30', '14:51:10', '2025-10-30 14:51:32', '2025-10-30 14:51:41', 3),
(8, 2, 8, 'skipped', '2025-10-30', '14:51:17', '2025-10-30 14:53:12', NULL, 3),
(9, 2, 9, 'finished', '2025-10-30', '14:52:01', '2025-10-30 14:53:16', '2025-10-30 14:53:20', 3),
(10, 2, 10, 'finished', '2025-10-30', '14:52:06', '2025-10-30 14:53:23', '2025-10-30 14:53:32', 3),
(11, 2, 11, 'finished', '2025-10-30', '14:52:09', '2025-10-30 14:53:51', '2025-10-30 14:57:08', 3),
(12, 2, 12, 'finished', '2025-10-30', '14:52:20', '2025-10-30 14:57:12', '2025-10-30 17:02:30', 3),
(13, 3, 1, 'finished', '2025-10-30', '15:00:39', '2025-10-30 15:00:40', '2025-10-30 15:00:47', 6),
(14, 3, 2, 'finished', '2025-10-30', '15:00:59', '2025-10-30 15:01:27', '2025-10-30 15:01:29', 6),
(15, 3, 3, 'finished', '2025-10-30', '15:00:59', '2025-10-30 15:01:30', '2025-10-30 15:01:30', 6),
(16, 3, 4, 'finished', '2025-10-30', '15:00:59', '2025-10-30 15:01:30', '2025-10-30 15:01:31', 6),
(17, 3, 5, 'finished', '2025-10-30', '15:00:59', '2025-10-30 15:01:31', '2025-10-30 15:01:32', 6),
(18, 3, 6, 'finished', '2025-10-30', '15:00:59', '2025-10-30 15:01:32', '2025-10-30 15:01:32', 6),
(19, 3, 7, 'finished', '2025-10-30', '15:00:59', '2025-10-30 15:02:16', '2025-10-30 15:02:17', 6),
(20, 3, 8, 'finished', '2025-10-30', '15:01:15', '2025-10-30 15:02:17', '2025-10-30 15:02:18', 6),
(21, 3, 9, 'finished', '2025-10-30', '15:01:21', '2025-10-30 15:02:18', '2025-10-30 15:02:18', 6),
(22, 2, 13, 'called', '2025-10-30', '17:02:16', '2025-10-30 17:02:32', NULL, 3),
(23, 2, 1, 'finished', '2025-10-31', '11:32:39', '2025-10-31 11:32:49', '2025-10-31 11:33:09', 3),
(24, 2, 2, 'called', '2025-10-31', '11:33:18', '2025-10-31 11:33:23', NULL, 3),
(25, 2, 1, 'finished', '2025-11-01', '10:46:22', '2025-11-01 10:46:24', '2025-11-01 11:23:28', 3),
(26, 2, 2, 'finished', '2025-11-01', '11:23:26', '2025-11-01 11:23:32', '2025-11-01 11:26:43', 3),
(27, 2, 3, 'finished', '2025-11-01', '11:26:30', '2025-11-01 11:26:46', '2025-11-01 11:29:30', 3),
(28, 2, 1, 'called', '2025-11-03', '15:46:12', '2025-11-03 15:46:18', NULL, 3),
(29, 2, 1, 'finished', '2025-11-06', '13:54:02', '2025-11-06 13:54:05', '2025-11-06 14:21:17', 3),
(30, 2, 2, 'finished', '2025-11-06', '13:54:09', '2025-11-06 14:25:11', '2025-11-06 15:11:15', 3),
(31, 2, 3, 'finished', '2025-11-06', '15:11:10', '2025-11-06 15:11:17', '2025-11-06 15:11:43', 3),
(32, 2, 4, 'finished', '2025-11-06', '15:11:44', '2025-11-06 15:11:49', '2025-11-06 16:10:51', 3),
(33, 2, 5, 'finished', '2025-11-06', '16:10:49', '2025-11-06 16:10:53', '2025-11-06 16:11:35', 3),
(34, 2, 6, 'skipped', '2025-11-06', '16:11:37', '2025-11-06 16:12:29', NULL, 3),
(35, 2, 7, 'finished', '2025-11-06', '16:12:33', '2025-11-06 16:13:55', '2025-11-06 16:13:57', 3),
(36, 2, 8, 'finished', '2025-11-06', '16:12:37', '2025-11-06 16:13:59', '2025-11-06 16:14:25', 3),
(37, 2, 9, 'finished', '2025-11-06', '16:14:20', '2025-11-06 16:14:27', '2025-11-06 16:17:18', 3),
(38, 2, 10, 'called', '2025-11-06', '16:14:22', '2025-11-06 16:17:24', NULL, 3),
(39, 2, 11, 'waiting', '2025-11-06', '16:14:24', NULL, NULL, NULL),
(40, 2, 1, 'finished', '2025-11-07', '17:04:24', '2025-11-07 17:06:14', '2025-11-07 17:06:17', 3),
(41, 2, 2, 'finished', '2025-11-07', '17:04:33', '2025-11-07 17:06:20', '2025-11-07 17:06:57', 3),
(42, 2, 3, 'finished', '2025-11-07', '17:06:55', '2025-11-07 17:07:01', '2025-11-07 17:08:51', 3),
(43, 2, 4, 'finished', '2025-11-07', '17:06:55', '2025-11-07 17:08:52', '2025-11-07 17:10:57', 3),
(44, 2, 5, 'finished', '2025-11-07', '17:10:56', '2025-11-07 17:11:07', '2025-11-07 17:14:11', 3),
(45, 2, 6, 'finished', '2025-11-07', '17:14:04', '2025-11-07 17:14:12', '2025-11-07 17:14:51', 3),
(46, 2, 7, 'finished', '2025-11-07', '17:14:05', '2025-11-07 17:14:52', '2025-11-07 17:15:21', 3),
(47, 2, 8, 'called', '2025-11-07', '17:15:19', '2025-11-07 17:42:05', NULL, 3),
(48, 2, 9, 'waiting', '2025-11-07', '17:15:19', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `recycle_bin`
--

CREATE TABLE `recycle_bin` (
  `id` int NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `record_id` int NOT NULL,
  `data_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tv_slides`
--

CREATE TABLE `tv_slides` (
  `id` int NOT NULL,
  `branch_id` int NOT NULL COMMENT 'Slide ini untuk cabang mana',
  `uploaded_by_user_id` int NOT NULL,
  `type` enum('text','image','video','youtube') NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `media_path` varchar(255) DEFAULT NULL COMMENT 'Path ke file gambar/video atau URL YouTube',
  `content_text` text COMMENT 'Jika tipenya text',
  `duration_seconds` int DEFAULT '10',
  `slide_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `schedule_from` datetime DEFAULT NULL,
  `schedule_to` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Konten slide untuk Modul TV Informasi';

--
-- Dumping data for table `tv_slides`
--

INSERT INTO `tv_slides` (`id`, `branch_id`, `uploaded_by_user_id`, `type`, `title`, `media_path`, `content_text`, `duration_seconds`, `slide_order`, `is_active`, `schedule_from`, `schedule_to`, `created_at`) VALUES
(9, 2, 1, 'youtube', '', 'https://www.youtube.com/embed/xoJISbnOnLY?autoplay=1&mute=1&loop=1&playlist=xoJISbnOnLY&controls=0&showinfo=0&modestbranding=1', NULL, 10, 2, 1, NULL, NULL, '2025-11-07 03:04:36'),
(10, 2, 1, 'youtube', '', 'https://www.youtube.com/embed/7sMVTEWaUQ4?autoplay=1&mute=1&loop=1&playlist=7sMVTEWaUQ4&controls=0&showinfo=0&modestbranding=1', NULL, 10, 1, 1, NULL, NULL, '2025-11-07 03:09:53'),
(11, 2, 1, 'image', '', '../uploads/tv_media/slide_690d6357444731.68962284.png', NULL, 10, 0, 1, NULL, NULL, '2025-11-07 03:11:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `branch_id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('superadmin','admin_tv','branch_user','credit_officer','teller','user_diklat') NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Manajemen pengguna dan hak akses';

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `branch_id`, `username`, `password_hash`, `full_name`, `email`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'admin', '$2y$10$CeWd2.dnH8nhpmEYaZXMaObqAvWpB8C9TS3fxzBvsghUew78EMtpe', 'Admin', 'cubgkp@gmail.com', 'superadmin', 1, '2025-10-30 05:06:59', '2025-11-01 07:05:08'),
(2, 2, 'kreditckn', '$2y$10$th65W8imF3PI.gf6uCCylOyFHiY92LPAgmChgVg7ygSG5bbE3TfHK', 'Kredit Cikini', '', 'credit_officer', 1, '2025-10-30 06:35:21', '2025-10-30 06:35:21'),
(3, 2, 'kasirckn', '$2y$10$8VeCMfT79Ys7ietYscHeAeq9O8o5gmdyMMjgzsZEBdKbRDE2A1ece', 'Kasir Cikini', NULL, 'teller', 1, '2025-10-30 06:38:14', '2025-10-30 06:38:14'),
(4, 2, 'komiteckn', '$2y$10$DCJ3OzDyYoNPCUHTYvjele73KGaeFUCF/Dd6EUaj/6vg2ZqdkxI/K', 'Komite Cikini', NULL, 'branch_user', 1, '2025-10-30 06:38:41', '2025-10-30 06:38:41'),
(5, 2, 'tvckn', '$2y$10$mlC5/OfY3qXC7EsEWQcV0esB7.jA1DXeGxiKxQg1FVNdQnc./G0kq', 'TV Informasi', NULL, 'admin_tv', 1, '2025-10-30 06:39:05', '2025-10-30 06:39:05'),
(6, 3, 'kasirtgr', '$2y$10$tCqnUi6FHMBSuSW8SEFvHefxZOnPfS6po2SYVUWt3qUfm5m9TuMbK', 'Kasir Tangerang', NULL, 'teller', 1, '2025-10-30 07:59:11', '2025-10-30 07:59:11'),
(7, 2, 'diklatckn', '$2y$10$3v9j/Z6t6Pz0jhEJr2JtxurSL5fJKrfQ3FeZ1T4bcTSRsnN52S4U6', 'Diklat Cikini', NULL, 'user_diklat', 1, '2025-11-01 05:41:37', '2025-11-01 05:41:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fma_applications`
--
ALTER TABLE `fma_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_ktp` (`no_ktp`),
  ADD UNIQUE KEY `kode_tracking` (`kode_tracking`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `status_permohonan` (`status_permohonan`),
  ADD KEY `fma_applications_ibfk_2` (`updated_by_user_id`);

--
-- Indexes for table `loan_applications`
--
ALTER TABLE `loan_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `application_number` (`application_number`),
  ADD UNIQUE KEY `tracking_code_unique` (`tracking_code`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `created_by_user_id` (`created_by_user_id`);

--
-- Indexes for table `loan_application_details`
--
ALTER TABLE `loan_application_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_app_id` (`loan_app_id`);

--
-- Indexes for table `loan_application_logs`
--
ALTER TABLE `loan_application_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_application_id` (`loan_application_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `loan_business_finances`
--
ALTER TABLE `loan_business_finances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `loan_application_id` (`loan_application_id`);

--
-- Indexes for table `loan_documents`
--
ALTER TABLE `loan_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_application_id` (`loan_application_id`),
  ADD KEY `uploaded_by_user_id` (`uploaded_by_user_id`);

--
-- Indexes for table `loan_net_worth`
--
ALTER TABLE `loan_net_worth`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `loan_application_id` (`loan_application_id`);

--
-- Indexes for table `loan_statuses`
--
ALTER TABLE `loan_statuses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loan_status_history`
--
ALTER TABLE `loan_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_app_id` (`loan_app_id`),
  ADD KEY `status_id` (`status_id`);

--
-- Indexes for table `queue_numbers`
--
ALTER TABLE `queue_numbers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_branch_date_status` (`branch_id`,`created_at_date`,`status`),
  ADD KEY `teller_id` (`teller_id`);

--
-- Indexes for table `recycle_bin`
--
ALTER TABLE `recycle_bin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tv_slides`
--
ALTER TABLE `tv_slides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `uploaded_by_user_id` (`uploaded_by_user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `branch_id` (`branch_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `fma_applications`
--
ALTER TABLE `fma_applications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `loan_applications`
--
ALTER TABLE `loan_applications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `loan_application_details`
--
ALTER TABLE `loan_application_details`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `loan_application_logs`
--
ALTER TABLE `loan_application_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `loan_business_finances`
--
ALTER TABLE `loan_business_finances`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_documents`
--
ALTER TABLE `loan_documents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_statuses`
--
ALTER TABLE `loan_statuses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `loan_status_history`
--
ALTER TABLE `loan_status_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `queue_numbers`
--
ALTER TABLE `queue_numbers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `recycle_bin`
--
ALTER TABLE `recycle_bin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tv_slides`
--
ALTER TABLE `tv_slides`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
