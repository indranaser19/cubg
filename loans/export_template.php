<?php
// Template ini dipanggil oleh export_pdf.php
// Variabel $loan, $biz, dan $worth sudah tersedia di sini

// Fungsi helper untuk format
function fCurrency($value) {
    if ($value === null || $value === '') return '-';
    return 'Rp ' . number_format($value, 0, ',', '.');
}
function fString($value) {
    if ($value === null || $value === '') return '-';
    return htmlspecialchars($value);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        /* CSS Sederhana untuk mPDF */
        body { font-family: sans-serif; font-size: 10pt; }
        .header { text-align: center; border-bottom: 1px solid #000; padding-bottom: 5px; }
        .header h3 { margin: 0; }
        .header p { margin: 0; font-size: 9pt; }
        h4 { background-color: #f0f0f0; padding: 5px; margin-top: 15px; margin-bottom: 5px; }
        .content-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .content-table td { vertical-align: top; padding: 4px; }
        .label { font-weight: bold; width: 35%; }
        .value { width: 65%; border-bottom: 1px dotted #999; }
        
        .full-width-table { width: 100%; border-collapse: collapse; margin-top: 10px; border: 1px solid #000; }
        .full-width-table th, .full-width-table td { border: 1px solid #000; padding: 5px; text-align: left; }
        .full-width-table th { background-color: #f0f0f0; }
        
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    
    <div class="header">
        <h3>KSP CREDIT UNION BEREROD GRATIA</h3>
        <p>Badan Hukum Nomor: 631/BH/MENEG.I/VII/2007</p>
        <h2 style="margin-top: 10px;">SURAT PERMOHONAN PINJAMAN</h2>
    </div>

    <table class="content-table" style="margin-top: 20px;">
        <tr>
            <td class="label">Nomor Permohonan:</td>
            <td class="value"><?php echo fString($loan['application_number']); ?> (ID: <?php echo $loan['id']; ?>)</td>
            <td class="label">Tanggal:</td>
            <td class="value"><?php echo date('d-m-Y', strtotime($loan['application_date'])); ?></td>
        </tr>
        <tr>
            <td class="label">Kantor Pelayanan:</td>
            <td class="value"><?php echo fString($loan['branch_name']); ?></td>
            <td class="label"></td>
            <td class="value"></td>
        </tr>
    </table>
    
    <h4>A. DATA DIRI ANGGOTA</h4>
    <table class="content-table">
        <tr>
            <td class="label">1. Nama (sesuai KTP)</td>
            <td class="value"><?php echo fString($loan['applicant_name']); ?></td>
            <td class="label">No. Buku Anggota</td>
            <td class="value"><?php echo fString($loan['applicant_ba_number']); ?></td>
        </tr>
        <tr>
            <td class="label">2. Pekerjaan</td>
            <td class="value"><?php echo fString($loan['applicant_occupation']); ?></td>
            <td class="label">Jabatan Sekarang</td>
            <td class="value"><?php echo fString($loan['applicant_position']); ?></td>
        </tr>
        <tr>
            <td class="label">3. Tempat/Tgl Lahir</td>
            <td class="value"><?php echo fString($loan['applicant_birth_place']); ?>, <?php echo date('d-m-Y', strtotime($loan['applicant_birth_date'])); ?></td>
            <td class="label">Jenis Kelamin</td>
            <td class="value"><?php echo fString($loan['applicant_gender']); ?></td>
        </tr>
        <tr>
            <td class="label">4. Alamat Rumah (KTP)</td>
            <td class="value" colspan="3"><?php echo fString($loan['applicant_ktp_address']); ?></td>
        </tr>
        <tr>
            <td class="label">5. Alamat Tinggal saat ini</td>
            <td class="value" colspan="3"><?php echo fString($loan['applicant_current_address']); ?></td>
        </tr>
        <tr>
            <td class="label">6. No. Telepon / HP</td>
            <td class="value"><?php echo fString($loan['applicant_phone']); ?></td>
            <td class="label">Status</td>
            <td class="value"><?php echo fString($loan['applicant_marital_status']); ?></td>
        </tr>
    </table>
    
    <h4>B. DATA SUAMI / ISTRI</h4>
    <table class="content-table">
        <tr>
            <td class="label">7. Nama</td>
            <td class="value"><?php echo fString($loan['spouse_name']); ?></td>
            <td class="label">No. Buku Anggota</td>
            <td class="value"><?php echo fString($loan['spouse_ba_number']); ?></td>
        </tr>
         <tr>
            <td class="label">8. Pekerjaan</td>
            <td class="value"><?php echo fString($loan['spouse_occupation']); ?></td>
            <td class="label">Jabatan Sekarang</td>
            <td class="value"><?php echo fString($loan['spouse_position']); ?></td>
        </tr>
    </table>
    
    <h4>E. PINJAMAN YANG DIMOHON</h4>
    <table class="content-table">
        <tr>
            <td class="label">19. Jumlah Permohonan</td>
            <td class="value"><?php echo fCurrency($loan['loan_amount_requested']); ?></td>
            <td class="label">Jangka Waktu</td>
            <td class="value"><?php echo fString($loan['loan_term_months']); ?> Bulan</td>
        </tr>
        <tr>
            <td class="label">20. Jenis Pinjaman</td>
            <td class="value"><?php echo fString($loan['loan_type']); ?></td>
            <td class="label">21. Tujuan Pinjaman</td>
            <td class="value"><?php echo fString($loan['loan_purpose']); ?></td>
        </tr>
        <tr>
            <td class="label">22. Jaminan</td>
            <td class="value"><?php echo fString($loan['loan_collateral_type']); ?></td>
            <td class="label">Pemilik Jaminan</td>
            <td class="value"><?php echo fString($loan['loan_collateral_owner']); ?></td>
        </tr>
        <tr>
            <td class="label">Status Jaminan</td>
            <td class="value"><?php echo fString($loan['loan_collateral_status']); ?></td>
            <td class="label">Harga Jaminan</td>
            <td class="value"><?php echo fCurrency($loan['loan_collateral_value']); ?></td>
        </tr>
    </table>

    <div class="page-break"></div>
    <div class="header">
        <h2 style="margin-top: 10px;">Laporan Laba/Rugi Usaha/Bisnis Peminjam</h2>
    </div>
    
    <table class="full-width-table">
        <thead>
            <tr>
                <th>Uraian</th>
                <th>Per Bulan (dalam rupiah)</th>
                <th>Total (dalam rupiah)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>A. Penjualan barang dagangan</td>
                <td><?php echo fCurrency($biz['sales_monthly']); ?></td>
                <td><?php echo fCurrency($biz['sales_total']); ?></td>
            </tr>
            <tr>
                <td>B. Harga pokok barang dagangan</td>
                <td><?php echo fCurrency($biz['cogs_monthly']); ?></td>
                <td><?php echo fCurrency($biz['cogs_total']); ?></td>
            </tr>
            <tr>
                <td colspan="3"><strong>D. Biaya operasional</strong></td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">Gaji Karyawan</td>
                <td><?php echo fCurrency($biz['op_payroll_monthly']); ?></td>
                <td><?php echo fCurrency($biz['op_payroll_total']); ?></td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">Sewa lokasi</td>
                <td><?php echo fCurrency($biz['op_rent_monthly']); ?></td>
                <td><?php echo fCurrency($biz['op_rent_total']); ?></td>
            </tr>
            </tbody>
    </table>

    <div class="page-break"></div>
    <div class="header">
        <h2 style="margin-top: 10px;">Laporan Kekayaan Bersih Calon Peminjam</h2>
    </div>

    <table class="full-width-table">
        <thead>
            <tr>
                <th colspan="2">ASET</th>
                <th colspan="2">KEWAJIBAN (UTANG)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="2"><strong>ASET LANCAR</strong></td>
                <td colspan="2"><strong>KEWAJIBAN JANGKA PENDEK</strong></td>
            </tr>
            <tr>
                <td>Uang Tunai</td>
                <td><?php echo fCurrency($worth['asset_cash']); ?></td>
                <td>Pinjaman di CU (<12 bln)</td>
                <td><?php echo fCurrency($worth['liability_cu_short_term']); ?></td>
            </tr>
            <tr>
                <td>Simpanan di bank</td>
                <td><?php echo fCurrency($worth['asset_bank_savings']); ?></td>
                <td>Utang Kartu Kredit / KTA</td>
                <td><?php echo fCurrency($worth['liability_credit_card_kta']); ?></td>
            </tr>
            <tr>
                <td colspan="2"><strong>INVESTASI</strong></td>
                <td colspan="2"><strong>KEWAJIBAN JANGKA PANJANG</strong></td>
            </tr>
             <tr>
                <td>Saldo Simp. Megapolitan</td>
                <td><?php echo fCurrency($worth['invest_megapolitan_savings']); ?></td>
                <td>Sisa Pinjaman Perumahan</td>
                <td><?php echo fCurrency($worth['liability_housing_loan']); ?></td>
            </tr>
            <tr>
                <td>Nilai Aset Usaha / bisnis</td>
                <td><?php echo fCurrency($worth['invest_business_assets']); ?></td>
                <td>Sisa Pinjaman Kendaraan</td>
                <td><?php echo fCurrency($worth['liability_vehicle_loan']); ?></td>
            </tr>
            </tbody>
    </table>

</body>
</html>