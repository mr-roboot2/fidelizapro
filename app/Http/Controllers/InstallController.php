<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use PDO;
use Throwable;

class InstallController extends Controller
{
    public function welcome()
    {
        $base = base_path();

        if (!file_exists($base.'/.env') && file_exists($base.'/.env.example')) {
            @copy($base.'/.env.example', $base.'/.env');
        }

        $checks = [
            'PHP >= 8.2'                => version_compare(PHP_VERSION, '8.2', '>='),
            'Extensão BCMath'           => extension_loaded('bcmath'),
            'Extensão Ctype'            => extension_loaded('ctype'),
            'Extensão cURL'             => extension_loaded('curl'),
            'Extensão DOM'              => extension_loaded('dom'),
            'Extensão Fileinfo'         => extension_loaded('fileinfo'),
            'Extensão GD'               => extension_loaded('gd'),
            'Extensão Mbstring'         => extension_loaded('mbstring'),
            'Extensão OpenSSL'          => extension_loaded('openssl'),
            'Extensão PDO MySQL'        => extension_loaded('pdo_mysql'),
            'Extensão Tokenizer'        => extension_loaded('tokenizer'),
            'Extensão XML'              => extension_loaded('xml'),
            'Extensão Zip'              => extension_loaded('zip'),
            'Pasta storage/ gravável'   => is_writable($base.'/storage'),
            'Pasta bootstrap/cache/ gravável' => is_writable($base.'/bootstrap/cache'),
            'Arquivo .env gravável'     => file_exists($base.'/.env') && is_writable($base.'/.env'),
            'Vendor instalado (composer install)' => file_exists($base.'/vendor/autoload.php'),
        ];

        $allOk = !in_array(false, $checks, true);

        return view('install.welcome', compact('checks', 'allOk'));
    }

    public function database()
    {
        $env = $this->readEnv();

        return view('install.database', [
            'db_host'     => $env['DB_HOST']     ?? '127.0.0.1',
            'db_port'     => $env['DB_PORT']     ?? '3306',
            'db_database' => $env['DB_DATABASE'] ?? 'fidelizapro',
            'db_username' => $env['DB_USERNAME'] ?? '',
            'db_password' => '',
        ]);
    }

    public function databaseStore(Request $request)
    {
        $data = $request->validate([
            'db_host'     => 'required|string',
            'db_port'     => 'required|numeric',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        try {
            $dsn = "mysql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_database']};charset=utf8mb4";
            new PDO($dsn, $data['db_username'], $data['db_password'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors([
                'connection' => 'Falha ao conectar no banco: ' . $e->getMessage(),
            ]);
        }

        $this->writeEnv([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST'       => $data['db_host'],
            'DB_PORT'       => $data['db_port'],
            'DB_DATABASE'   => $data['db_database'],
            'DB_USERNAME'   => $data['db_username'],
            'DB_PASSWORD'   => $this->envEscape($data['db_password'] ?? ''),
        ]);

        return redirect('/install/app');
    }

    public function app(Request $request)
    {
        $env = $this->readEnv();

        return view('install.app', [
            'app_name'     => $env['APP_NAME']     ?? 'FidelizaPro',
            'app_url'      => $env['APP_URL']      ?? rtrim($request->getSchemeAndHttpHost(), '/'),
            'app_timezone' => $env['APP_TIMEZONE'] ?? 'America/Sao_Paulo',
        ]);
    }

    public function appStore(Request $request)
    {
        $data = $request->validate([
            'app_name'     => 'required|string|max:80',
            'app_url'      => 'required|url',
            'app_timezone' => 'required|string',
            'seed'         => 'nullable|in:1,0',
        ]);

        $this->writeEnv([
            'APP_NAME'     => $this->envEscape($data['app_name']),
            'APP_ENV'      => 'production',
            'APP_DEBUG'    => 'false',
            'APP_URL'      => rtrim($data['app_url'], '/'),
            'APP_TIMEZONE' => $data['app_timezone'],
        ]);

        $env = $this->readEnv();
        if (empty($env['APP_KEY'])) {
            try {
                Artisan::call('key:generate', ['--force' => true]);
            } catch (Throwable $e) {
                return back()->withInput()->withErrors([
                    'key' => 'Falha ao gerar APP_KEY: ' . $e->getMessage(),
                ]);
            }
        }

        Artisan::call('config:clear');

        try {
            if ($request->input('seed') === '1') {
                Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
            } else {
                Artisan::call('migrate', ['--force' => true]);
            }
        } catch (Throwable $e) {
            return back()->withInput()->withErrors([
                'migration' => 'Falha nas migrations: ' . $e->getMessage(),
            ]);
        }

        return redirect('/install/admin')->with('seeded', $request->input('seed') === '1');
    }

    public function admin()
    {
        $existing = null;
        try {
            $existing = User::where('role', 'super_admin')->first();
        } catch (Throwable $e) {
            return redirect('/install/app')->withErrors([
                'migration' => 'Tabela users não encontrada. Refaça a etapa de configuração.',
            ]);
        }

        return view('install.admin', compact('existing'));
    }

    public function adminStore(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (User::where('email', $data['email'])->exists()) {
            return back()->withInput()->withErrors([
                'email' => 'Já existe um usuário com este e-mail.',
            ]);
        }

        User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => 'super_admin',
            'ativo'    => true,
        ]);

        try { Artisan::call('storage:link'); } catch (Throwable $e) {}

        try {
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
        } catch (Throwable $e) {}

        @file_put_contents(
            storage_path('installed.lock'),
            'Instalado em '.date('Y-m-d H:i:s')
        );

        return redirect('/install/complete')->with('admin_email', $data['email']);
    }

    public function adminSkip()
    {
        $existing = User::where('role', 'super_admin')->first();
        if (!$existing) {
            return redirect('/install/admin')->withErrors([
                'admin' => 'Nenhum super admin encontrado. Crie a conta abaixo.',
            ]);
        }

        try { Artisan::call('storage:link'); } catch (Throwable $e) {}

        try {
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
        } catch (Throwable $e) {}

        @file_put_contents(
            storage_path('installed.lock'),
            'Instalado em '.date('Y-m-d H:i:s')
        );

        return redirect('/install/complete')->with('admin_email', $existing->email);
    }

    public function complete()
    {
        return view('install.complete', [
            'admin_email' => session('admin_email'),
        ]);
    }

    protected function readEnv(): array
    {
        $path = base_path('.env');
        if (!file_exists($path)) {
            return [];
        }

        $env = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (!str_contains($line, '=')) continue;
            [$key, $value] = explode('=', $line, 2);
            $env[trim($key)] = trim($value, "\"' \t");
        }

        return $env;
    }

    protected function writeEnv(array $values): void
    {
        $path    = base_path('.env');
        $content = file_exists($path) ? file_get_contents($path) : '';

        foreach ($values as $key => $value) {
            $line    = $key.'='.$value;
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $line, $content);
            } else {
                $content = rtrim($content, "\n")."\n".$line."\n";
            }
        }

        file_put_contents($path, $content);
    }

    protected function envEscape(string $value): string
    {
        if ($value === '') {
            return '';
        }
        if (preg_match('/[\s#"\']/', $value)) {
            return '"'.str_replace('"', '\\"', $value).'"';
        }
        return $value;
    }
}
