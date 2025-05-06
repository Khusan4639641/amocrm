<?php

class AmoCrmApi {
    private $config;
    private $configFile;
    private $logFile = 'hook_log.txt';

    public function __construct(string $configFile = 'amo_config.json') {
        $this->configFile = $configFile;
        $this->loadConfig();
    }
    
    public function getConfig(): array {
        return $this->config;
    }
    
    private function loadConfig() {
        if (!file_exists($this->configFile)) {
            throw new Exception("Файл конфигурации {$this->configFile} отсутствует.");
        }

        $configContent = file_get_contents($this->configFile);
        $this->config = json_decode($configContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Ошибка в формате JSON: " . json_last_error_msg());
        }

        if (empty($this->config['access_token']) || empty($this->config['subdomain'])) {
            throw new Exception("Отсутствуют необходимые данные в конфигурации (access_token или subdomain).");
        }
    }

    private function saveConfig() {
        file_put_contents($this->configFile, json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function handleAuthCode() {
        if (!isset($_GET['code'])) {
            $this->redirectToAuth();
        } else {
            $this->saveAuthCode($_GET['code']);
        }
    }

    private function redirectToAuth() {
        $clientId = $this->config['clientId'] ?? null;
        $redirectUri = $this->config['redirectUri'] ?? null;

        if (!$clientId || !$redirectUri) {
            throw new Exception("Отсутствуют clientId или redirectUri в конфигурации.");
        }

        $url = "https://www.amocrm.ru/oauth/?mode=popup&origin=$redirectUri&client_id=$clientId";
        header("Location: $url");
        exit;
    }

    private function saveAuthCode(string $code) {
        $this->config['auth_code'] = $code;
        $this->saveConfig();
        echo "Код авторизации успешно сохранен: $code";
    }

    public function request(string $method, string $uri, array $argData = []) {
        $link = 'https://' . $this->config['subdomain'] . '.amocrm.ru' . $uri;

        $headers = [
            'Authorization: Bearer ' . $this->config['access_token'],
            'Content-Type: application/json',
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
        curl_setopt($curl, CURLOPT_URL, $link);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

        if (!empty($argData) && in_array(strtoupper($method), ['POST', 'PUT'])) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($argData));
        }

        $response = curl_exec($curl);

        if ($response === false) {
            throw new Exception('Ошибка cURL: ' . curl_error($curl));
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode < 200 || $httpCode > 204) {
            throw new Exception("HTTP ошибка: {$httpCode}. Ответ: " . $response);
        }

        return json_decode($response, true);
    }

    public function updateAccessToken(string $authCode = '', string $refreshToken = ''): void {
        $link = 'https://' . $this->config['subdomain'] . '.amocrm.ru/oauth2/access_token';

        $data = [];
        if ($authCode) {
            $data = [
                'client_id' => $this->config['clientId'],
                'client_secret' => $this->config['clientSecret'],
                'grant_type' => 'authorization_code',
                'code' => $authCode,
                'redirect_uri' => $this->config['redirectUri'],
            ];
        } elseif ($refreshToken) {
            $data = [
                'client_id' => $this->config['clientId'],
                'client_secret' => $this->config['clientSecret'],
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'redirect_uri' => $this->config['redirectUri'],
            ];
        } else {
            throw new Exception("Необходимо указать authCode или refreshToken для обновления токена.");
        }

        $response = $this->request('POST', '/oauth2/access_token', $data);

        $this->config['access_token'] = $response['access_token'];
        $this->config['refresh_token'] = $response['refresh_token'];
        $this->config['update_date'] = date('d-m-Y H:i:s');

        if ($authCode) {
            $this->config['auth_code'] = '';
        }

        $this->saveConfig();
    }
    
    public function logRequest(): void {
        $logEntry = [
            'time' => date('Y-m-d H:i:s'),
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'request_source' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'request_params' => $_REQUEST,
            'http_code' => http_response_code()
        ];

        $logContent = "===========================\n";
        $logContent .= "Time: " . $logEntry['time'] . "\n";
        $logContent .= "Method: " . $logEntry['method'] . "\n";
        $logContent .= "URI: " . $logEntry['uri'] . "\n";
        $logContent .= "Source: " . $logEntry['request_source'] . "\n";
        $logContent .= "Request Params: " . json_encode($logEntry['request_params'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        $logContent .= "HTTP Code: " . $logEntry['http_code'] . "\n";
        $logContent .= "===========================\n\n";

        file_put_contents($this->logFile, $logContent, FILE_APPEND);
    }

}