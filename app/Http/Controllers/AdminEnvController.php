<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Artisan;

class AdminEnvController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.session');

        $this->middleware(function ($request, $next) {
            $user = Session::get('user');
            $roleId = $user['user17025'] ?? null;
            if ($roleId !== 1) {
                abort(403);
            }
            return $next($request);
        });
    }

    public function index()
    {
        $envPath = base_path('.env');
        $db = env('DB_DATABASE');
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with($line, 'DB_DATABASE=')) {
                    $db = substr($line, strlen('DB_DATABASE='));
                    $db = trim($db, "\"\'");
                    break;
                }
            }
        }

        return view('admin.ambiente', ['db' => $db]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'db_name' => 'required|string',
        ]);

        $name = $request->input('db_name');
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return redirect()->back()->withErrors(['env' => '.env not found']);
        }

        $contents = file_get_contents($envPath);
        if (preg_match('/^DB_DATABASE=.*/m', $contents)) {
            $contents = preg_replace('/^DB_DATABASE=.*/m', 'DB_DATABASE='. $name, $contents);
        } else {
            $contents .= "\nDB_DATABASE={$name}\n";
        }

        file_put_contents($envPath, $contents);

        // clear config cache so new env is used
        try {
            Artisan::call('config:clear');
        } catch (\Exception $e) {
            // ignore
        }

        return redirect()->back()->with('success', 'DB_DATABASE aggiornato');
    }
}
