<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    public string $baseURL = '';
    public array $allowedHostnames = [];
    public string $indexPage = '';
    public string $uriProtocol = 'PATH_INFO';
    public string $defaultLocale = 'en';
    public bool $negotiateLocale = false;
    public array $supportedLocales = ['en'];
    public string $appTimezone = 'Asia/Jakarta';
    public string $charset = 'UTF-8';
    public string $permittedURIChars = 'a-z 0-9~%.:_\\-';
    public bool $forceGlobalSecureRequests = false;
    public array $proxyIPs = [];
    public bool $CSPEnabled = false;

    public function __construct()
    {
        parent::__construct();

        // CLI commands (including php spark serve bootstrap) do not have a browser host.
        if (is_cli() || ! isset($_SERVER['HTTP_HOST'])) {
            $this->baseURL = 'http://127.0.0.1:8080/';
            return;
        }

        $host = $_SERVER['HTTP_HOST'];
        $lowerHost = strtolower($host);

        // Respect the original browser/proxy scheme. Cloudflare Tunnel normally
        // forwards the HTTPS scheme even though the origin is HTTP.
        $forwardedProto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
        $cfVisitor = $_SERVER['HTTP_CF_VISITOR'] ?? '';
        $cfScheme = '';
        if ($cfVisitor !== '') {
            $visitor = json_decode($cfVisitor, true);
            if (is_array($visitor)) {
                $cfScheme = strtolower((string) ($visitor['scheme'] ?? ''));
            }
        }

        if ($forwardedProto === 'https' || $cfScheme === 'https' || (! empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) === 'on')) {
            $protocol = 'https';
        } else {
            $protocol = 'http';
        }

        // php spark serve / Cloudflare Tunnel -> project is served from public/,
        // therefore there is NO /absen prefix.
        $isBuiltinServer = str_contains($lowerHost, ':8080') || str_ends_with($lowerHost, '.trycloudflare.com');

        if ($isBuiltinServer) {
            $basePath = '';
        } elseif (preg_match('/^(localhost|127\\.0\\.0\\.1)(?::\\d+)?$/', $lowerHost) === 1) {
            // XAMPP/Apache installation at C:/xampp/htdocs/absen.
            $basePath = '/absen';
        } else {
            // Custom vhost or reverse proxy. Only add /absen when the browser URL
            // is actually serving the app below that path.
            $requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
            $basePath = str_starts_with($requestPath, '/absen/') || $requestPath === '/absen' ? '/absen' : '';
        }

        $this->baseURL = $protocol . '://' . $host . $basePath . '/';
    }
}
