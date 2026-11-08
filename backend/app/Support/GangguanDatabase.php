<?php

namespace App\Support;

use Illuminate\Database\LostConnectionDetector;
use Illuminate\Database\QueryException;
use Illuminate\Database\SQLiteDatabaseDoesNotExistException;
use Throwable;

/**
 * Merangkum error basis data menjadi data yang aman dicatat ke log dan dikirim ke klien.
 *
 * Pesan QueryException memuat SQL beserta nilai binding-nya (misalnya email saat login),
 * jadi yang diambil hanya pesan dari driver (PDO): kode SQLSTATE, kode error driver,
 * dan sebab singkat seperti "Connection refused". SQL dan nilai binding tidak pernah ikut.
 */
final class GangguanDatabase
{
    /** Kode error MySQL yang berarti server tidak bisa dihubungi atau menolak koneksi. */
    private const KODE_KONEKSI = [1044, 1045, 1049, 2002, 2003, 2005, 2006, 2013];

    /**
     * @return array{koneksi_gagal: bool, sqlstate: string|null, kode_driver: int|null, sebab: string}
     */
    public static function rangkum(Throwable $e): array
    {
        $sumber = $e instanceof QueryException && $e->getPrevious() ? $e->getPrevious() : $e;
        $pesan = $sumber->getMessage();

        preg_match('/SQLSTATE\[(\w+)\](?: \[(\d+)\])?/', $pesan, $kode);
        $sqlstate = $kode[1] ?? null;
        $kodeDriver = isset($kode[2]) ? (int) $kode[2] : null;

        $koneksiGagal = $sumber instanceof SQLiteDatabaseDoesNotExistException
            || in_array($kodeDriver, self::KODE_KONEKSI, true)
            || str_starts_with((string) $sqlstate, '08')
            || (new LostConnectionDetector)->causedByLostConnection($sumber);

        return [
            'koneksi_gagal' => $koneksiGagal,
            'sqlstate' => $sqlstate,
            'kode_driver' => $kodeDriver,
            'sebab' => self::sebabSingkat($pesan),
        ];
    }

    /**
     * Baris pertama pesan driver, tanpa bagian "(Connection: ..., SQL: ...)" dan tanpa path berkas.
     */
    private static function sebabSingkat(string $pesan): string
    {
        $pesan = strtok($pesan, "\n") ?: $pesan;
        $pesan = preg_replace('/\s*\(Connection: .*$/s', '', $pesan) ?? $pesan;
        $pesan = preg_replace('/path \[[^\]]*\]/', 'path [disembunyikan]', $pesan) ?? $pesan;

        return mb_strimwidth(trim($pesan), 0, 200, '...');
    }
}
