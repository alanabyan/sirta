<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class BuatAdmin extends Command
{
    protected $signature = 'sirta:admin
        {--nama= : Nama lengkap}
        {--username= : Username untuk masuk}
        {--password= : Kata sandi (min. 8 karakter). Sebaiknya dikosongkan agar ditanya langsung}
        {--reset : Ubah kata sandi bila username sudah ada}';

    protected $description = 'Membuat akun Administrator (atau mengatur ulang kata sandinya dengan --reset)';

    public function handle(): int
    {
        $username = $this->option('username') ?: $this->ask('Username');
        $user = User::where('username', $username)->first();

        if ($user && ! $this->option('reset')) {
            $this->error("Username “{$username}” sudah dipakai. Tambahkan --reset untuk mengganti kata sandinya.");

            return self::FAILURE;
        }

        if (! $user && $this->option('reset')) {
            $this->error("Username “{$username}” tidak ditemukan, jadi tidak ada yang bisa direset. Hapus --reset untuk membuat akun baru.");

            return self::FAILURE;
        }

        $password = $this->option('password') ?: $this->secret('Kata sandi (min. 8 karakter)');
        if (! $this->option('password')) { // diketik langsung → minta konfirmasi
            if ($password !== $this->secret('Ulangi kata sandi')) {
                $this->error('Kata sandi tidak sama.');

                return self::FAILURE;
            }
        }

        $data = ['username' => $username, 'password' => $password];
        $aturan = ['username' => ['required', 'regex:/^[A-Za-z0-9._-]+$/', 'max:50'], 'password' => ['required', 'string', 'min:8']];

        if ($user) {
            $v = Validator::make($data, $aturan);
        } else {
            $data['nama'] = $this->option('nama') ?: $this->ask('Nama lengkap');
            $v = Validator::make($data, $aturan + ['nama' => ['required', 'string', 'max:120']]);
        }
        if ($v->fails()) {
            foreach ($v->errors()->all() as $e) {
                $this->error($e);
            }

            return self::FAILURE;
        }

        if ($user) {
            $user->update(['password' => $password, 'is_active' => true]);
            $user->tokens()->delete(); // keluarkan semua sesi lama
            $this->info("Kata sandi {$username} diperbarui dan semua sesi lamanya dikeluarkan.");
        } else {
            User::create(['name' => $data['nama'], 'username' => $username, 'password' => $password, 'role' => 'administrator', 'is_active' => true]);
            $this->info("Administrator “{$username}” berhasil dibuat. Silakan masuk, lalu atur pengguna lain dari menu Pengguna Sistem.");
        }

        return self::SUCCESS;
    }
}
