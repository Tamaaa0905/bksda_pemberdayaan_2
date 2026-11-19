<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\Kawasan;
use App\Models\KegiatanPemberdayaan;
use App\Models\Kelompok;
use App\Models\Nte;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BksdaSeeder extends Seeder
{
    public function run(): void
    {
        $nteRecords = $this->readCsvRecords(database_path('seeders/data/nte_2025.csv'));
        $activityRecords = $this->readCsvRecords(database_path('seeders/data/kegiatan_pemberdayaan_2026.csv'));

        DB::transaction(function () use ($nteRecords, $activityRecords): void {
            $sourceKawasanNames = array_column($nteRecords, 'nama_kawasan');

            Nte::query()
                ->where('tahun', 2025)
                ->whereNotIn('kawasan_id', Kawasan::query()
                    ->whereIn('nama_kawasan', $sourceKawasanNames)
                    ->select('id'))
                ->delete();

            foreach ($nteRecords as $record) {
                $kawasan = Kawasan::updateOrCreate(
                    ['nama_kawasan' => $record['nama_kawasan']],
                    [
                        'fungsi_kawasan' => $record['fungsi_kawasan'],
                        'jumlah_desa_sekitar' => (int) $record['jumlah_desa_sekitar'],
                        'jumlah_desa_difasilitasi' => (int) $record['jumlah_desa_difasilitasi'],
                    ],
                );

                Nte::updateOrCreate(
                    [
                        'kawasan_id' => $kawasan->id,
                        'tahun' => (int) $record['tahun'],
                    ],
                    [
                        'nte_langsung' => (int) $record['nte_langsung'],
                        'nte_tidak_langsung' => (int) $record['nte_tidak_langsung'],
                        'total_nte' => (int) $record['total_nte'],
                        'data_sumber' => json_decode($record['data_sumber'], true, flags: JSON_THROW_ON_ERROR),
                    ],
                );
            }

            foreach ($activityRecords as $record) {
                KegiatanPemberdayaan::updateOrCreate(
                    [
                        'tahun' => (int) $record['tahun'],
                        'jenis' => $record['jenis'],
                        'urutan' => (int) $record['urutan'],
                    ],
                    [
                        'nama_kegiatan' => $record['nama_kegiatan'],
                        'target' => $record['target'] !== '' ? $record['target'] : null,
                        'waktu' => $record['waktu'] !== '' ? $record['waktu'] : null,
                        'lead_by' => $record['lead_by'] !== '' ? $record['lead_by'] : null,
                        'anggota_tim' => $record['anggota_tim'] !== '' ? $record['anggota_tim'] : null,
                        'data_sumber' => json_decode($record['data_sumber'], true, flags: JSON_THROW_ON_ERROR),
                    ],
                );
            }
        });

        $desaPath = database_path('seeders/data/desa_kk.csv');
        $desaHandle = fopen($desaPath, 'r');

        if ($desaHandle === false) {
            throw new \RuntimeException("Tidak dapat membaca data desa: {$desaPath}");
        }

        $desaHeaders = fgetcsv($desaHandle);
        $desaRecords = [];

        while (($row = fgetcsv($desaHandle)) !== false) {
            $desaRecords[] = array_combine($desaHeaders, $row);
        }

        fclose($desaHandle);

        DB::transaction(function () use ($desaRecords): void {
            foreach ($desaRecords as $record) {
                $kawasan = Kawasan::firstOrCreate([
                    'nama_kawasan' => $record['nama_kawasan'],
                ]);
                $kunciDesa = [
                    'kawasan_id' => $kawasan->id,
                    'nama_desa' => $record['nama_desa'],
                ];

                Desa::updateOrCreate(
                    $kunciDesa,
                    [
                        'kode_desa' => $record['kode_desa'] !== '' ? $record['kode_desa'] : null,
                        'kecamatan' => $record['kecamatan'] !== '' ? $record['kecamatan'] : null,
                        'kabupaten' => $record['kabupaten'] !== '' ? $record['kabupaten'] : null,
                        'jumlah_penduduk' => $record['jumlah_penduduk'] !== '' ? (int) $record['jumlah_penduduk'] : null,
                        'jumlah_penutupan_kk' => $record['jumlah_kk'] !== '' ? (int) $record['jumlah_kk'] : null,
                        'data_sumber' => json_decode($record['data_sumber'], true, flags: JSON_THROW_ON_ERROR),
                    ],
                );
            }
        });

        $path = database_path('seeders/data/kelompok_binaan.csv');
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Tidak dapat membaca data kelompok: {$path}");
        }

        $headers = fgetcsv($handle);
        $records = [];

        while (($row = fgetcsv($handle)) !== false) {
            $records[] = array_combine($headers, $row);
        }

        fclose($handle);

        DB::transaction(function () use ($records): void {
            foreach ($records as $record) {
                $kawasan = Kawasan::firstOrCreate([
                    'nama_kawasan' => $record['nama_kawasan'],
                ]);
                $desa = null;

                if ($record['desa_kk_kode'] !== '') {
                    $desa = Desa::query()
                        ->where('kode_desa', $record['desa_kk_kode'])
                        ->whereHas('kawasan', fn ($query) => $query->where('nama_kawasan', $record['desa_kk_kawasan']))
                        ->firstOrFail();
                } elseif ($record['nama_desa'] !== '') {
                    $desa = Desa::firstOrCreate(
                        [
                            'kawasan_id' => $kawasan->id,
                            'nama_desa' => $record['nama_desa'],
                        ],
                        [
                            'kecamatan' => $record['kecamatan'] !== '' ? $record['kecamatan'] : null,
                            'kabupaten' => $record['kabupaten'] !== '' ? $record['kabupaten'] : null,
                        ],
                    );
                }

                Kelompok::updateOrCreate(
                    [
                        'kawasan_id' => $kawasan->id,
                        'tahun_pembinaan' => (int) $record['tahun_pembinaan'],
                        'nama_kelompok' => $record['nama_kelompok'],
                    ],
                    [
                        'desa_id' => $desa?->id,
                        'kecamatan' => $record['kecamatan'] !== '' ? $record['kecamatan'] : null,
                        'kabupaten' => $record['kabupaten'] !== '' ? $record['kabupaten'] : null,
                        'nama_ketua' => $record['nama_ketua'] !== '' ? $record['nama_ketua'] : null,
                        'no_hp' => $record['no_hp'] !== '' ? $record['no_hp'] : null,
                        'jenis_usaha' => $record['jenis_usaha'] !== '' ? $record['jenis_usaha'] : null,
                        'sk_kelompok' => $record['sk_kelompok'] !== '' ? $record['sk_kelompok'] : null,
                        'jumlah_anggota' => (int) $record['jumlah_anggota'],
                        'jumlah_bantuan' => (float) $record['jumlah_bantuan'],
                        'data_sumber' => json_decode($record['data_sumber'], true, flags: JSON_THROW_ON_ERROR),
                    ],
                );
            }
        });
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function readCsvRecords(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Tidak dapat membaca data: {$path}");
        }

        try {
            $headers = fgetcsv($handle);

            if ($headers === false) {
                throw new \RuntimeException("Data CSV kosong: {$path}");
            }

            $records = [];

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) !== count($headers)) {
                    throw new \UnexpectedValueException("Jumlah kolom CSV tidak sesuai: {$path}");
                }

                $records[] = array_combine($headers, $row);
            }
        } finally {
            fclose($handle);
        }

        return $records;
    }
}
