<?php

declare(strict_types=1);

namespace App\Http;

use InvalidArgumentException;
use JsonException;
use OutOfBoundsException;
use Throwable;

class Router
{
    public function __construct(private ReportController $reportController)
    {
    }

    public function dispatch(): void
    {
        $this->sendCorsHeaders();
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'OPTIONS') {
            http_response_code(204);
            return;
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if (!is_string($path)) {
            $this->respond(['error' => 'Invalid request path.'], 400);
            return;
        }

        $path = rtrim($path, '/') ?: '/';

        try {
            if ($path === '/api/students') {
                if ($method !== 'GET') {
                    $this->respond(['error' => 'Method not allowed.'], 405);
                    return;
                }

                $this->respond($this->reportController->getStudents());
                return;
            }

            if ($path === '/api/report') {
                if ($method !== 'POST') {
                    $this->respond(['error' => 'Method not allowed.'], 405);
                    return;
                }

                $this->respond($this->reportController->getReport($this->readJsonBody()));
                return;
            }

            $this->respond(['error' => 'Route not found.'], 404);
        } catch (InvalidArgumentException $exception) {
            $this->respond(['error' => $exception->getMessage()], 400);
        } catch (OutOfBoundsException $exception) {
            $this->respond(['error' => $exception->getMessage()], 404);
        } catch (Throwable $exception) {
            error_log($exception->__toString());
            $this->respond(['error' => 'An internal server error occurred.'], 500);
        }
    }

    private function readJsonBody(): array
    {
        $body = file_get_contents('php://input');
        if ($body === false) {
            throw new InvalidArgumentException('Unable to read the request body.');
        }

        try {
            $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('The request body must be valid JSON.', 0, $exception);
        }
        if (!is_array($payload)) {
            throw new InvalidArgumentException('The request body must be a JSON object.');
        }

        return $payload;
    }

    private function sendCorsHeaders(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Accept');
    }

    private function respond(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
